<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\Property;
use App\Models\Transaction;
use App\Models\Unit;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(protected TransactionService $transactionService) {}

    public function index(Request $request): JsonResponse
    {
        $propertyIds = Property::pluck('id');
        $unitIds     = Unit::whereIn('property_id', $propertyIds)->pluck('id');
        $leaseIds    = Lease::whereIn('unit_id', $unitIds)->pluck('id');

        $transactions = Transaction::whereIn('lease_id', $leaseIds)
            ->with(['lease.tenant:id,full_name', 'lease.unit:id,unit_number'])
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->latest('paid_at')
            ->paginate(20);

        return response()->json($transactions);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lease_id'       => 'required|exists:leases,id',
            'type'           => 'required|in:rent,deposit,penalty,refund',
            'amount'         => 'required|numeric|min:1',
            'payment_method' => 'required|string',
            'paid_at'        => 'required|date',
            'notes'          => 'nullable|string',
        ]);

        $lease = Lease::findOrFail($validated['lease_id']);

        try {
            $transaction = $this->transactionService->recordPayment($lease, $validated);
            return response()->json([
                'data'    => $transaction,
                'message' => 'Payment recorded.',
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(Transaction $transaction): JsonResponse
    {
        $transaction->load(['lease.tenant', 'lease.unit.property']);
        return response()->json(['data' => $transaction]);
    }

    public function export(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $propertyIds  = Property::pluck('id');
        $unitIds      = Unit::whereIn('property_id', $propertyIds)->pluck('id');
        $leaseIds     = Lease::whereIn('unit_id', $unitIds)->pluck('id');
        $transactions = Transaction::whereIn('lease_id', $leaseIds)
            ->with(['lease.tenant', 'lease.unit.property'])
            ->orderByDesc('paid_at')->get();

        $filename = 'transactions-' . now()->format('Y-m-d') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        return response()->stream(function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Reference', 'Date', 'Tenant', 'Property', 'Unit', 'Type', 'Amount', 'Method', 'Notes']);
            foreach ($transactions as $t) {
                fputcsv($handle, [
                    $t->reference_code,
                    $t->paid_at ? \Carbon\Carbon::parse($t->paid_at)->format('d M Y') : '',
                    $t->lease->tenant->full_name,
                    $t->lease->unit->property->name,
                    $t->lease->unit->unit_number,
                    ucfirst($t->type),
                    $t->amount,
                    $t->payment_method ?? '',
                    $t->notes ?? '',
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}