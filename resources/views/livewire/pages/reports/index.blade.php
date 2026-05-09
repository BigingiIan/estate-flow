<?php

use App\Models\Lease;
use App\Models\Property;
use App\Models\Transaction;
use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use function Livewire\Volt\{computed, state};

state(['period' => '6']);

$reportData = computed(function () {
    $userId = Auth::id();
    $months = in_array((int) $this->period, [3, 6, 12], true) ? (int) $this->period : 6;
    $periodStart = now()->startOfMonth()->subMonths($months - 1);
    $periodEnd = now()->endOfMonth();
    $previousStart = $periodStart->copy()->subMonths($months);
    $previousEnd = $periodStart->copy()->subDay()->endOfDay();
    $currentMonthStart = now()->startOfMonth();
    $currentMonthEnd = now()->endOfMonth();

    $propertyIds = Property::where('user_id', $userId)->pluck('id');
    $unitIds = Unit::whereIn('property_id', $propertyIds)->pluck('id');
    $leaseIds = Lease::whereIn('unit_id', $unitIds)->pluck('id');

    $rentInPeriod = fn ($query) => $query
        ->where('type', 'rent')
        ->whereBetween('paid_at', [$periodStart, $periodEnd]);

    $monthlyData = [];
    $totalRevenue = 0.0;

    for ($i = $months - 1; $i >= 0; $i--) {
        $month = now()->startOfMonth()->subMonths($i);
        $amount = Transaction::whereIn('lease_id', $leaseIds)
            ->where('type', 'rent')
            ->whereBetween('paid_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->sum('amount');

        $monthlyData[] = [
            'month' => $month->format('M Y'),
            'amount' => (float) $amount,
        ];

        $totalRevenue += (float) $amount;
    }

    $previousRevenue = Transaction::whereIn('lease_id', $leaseIds)
        ->where('type', 'rent')
        ->whereBetween('paid_at', [$previousStart, $previousEnd])
        ->sum('amount');

    $revenueChangePct = $previousRevenue > 0
        ? round((($totalRevenue - $previousRevenue) / $previousRevenue) * 100, 1)
        : null;

    $activeLeases = Lease::whereIn('unit_id', $unitIds)
        ->where('status', 'active')
        ->with(['tenant', 'unit.property'])
        ->get();

    $expectedRent = (float) $activeLeases->sum('rent_amount');
    $collectedRent = (float) Transaction::whereIn('lease_id', $activeLeases->pluck('id'))
        ->where('type', 'rent')
        ->whereBetween('paid_at', [$currentMonthStart, $currentMonthEnd])
        ->sum('amount');

    $collectionRate = $expectedRent > 0 ? round(($collectedRent / $expectedRent) * 100, 1) : 0;

    $arrears = $activeLeases->map(function ($lease) use ($currentMonthStart, $currentMonthEnd) {
        $paid = (float) $lease->transactions()
            ->where('type', 'rent')
            ->whereBetween('paid_at', [$currentMonthStart, $currentMonthEnd])
            ->sum('amount');

        return [
            'lease' => $lease,
            'amount' => max(0, (float) $lease->rent_amount - $paid),
        ];
    })->filter(fn ($row) => $row['amount'] > 0)->values();

    $arrearsAmount = (float) $arrears->sum('amount');
    $arrearsCount = $arrears->count();

    $totalUnits = Unit::whereIn('property_id', $propertyIds)->count();
    $occupiedUnits = Unit::whereIn('property_id', $propertyIds)->where('status', 'occupied')->count();
    $vacantUnits = Unit::whereIn('property_id', $propertyIds)->where('status', 'vacant')->count();
    $maintenanceUnits = Unit::whereIn('property_id', $propertyIds)->where('status', 'maintenance')->count();
    $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0;
    $vacancyCost = (float) Unit::whereIn('property_id', $propertyIds)
        ->where('status', 'vacant')
        ->sum('base_rent');

    $paymentMethods = Transaction::whereIn('lease_id', $leaseIds)
        ->tap($rentInPeriod)
        ->whereNotNull('payment_method')
        ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
        ->groupBy('payment_method')
        ->orderByDesc('total')
        ->get()
        ->map(fn ($row) => [
            'method' => ucfirst(str_replace('_', ' ', $row->payment_method)),
            'count' => (int) $row->count,
            'total' => (float) $row->total,
        ]);

    $typeBreakdown = Transaction::whereIn('lease_id', $leaseIds)
        ->whereBetween('paid_at', [$periodStart, $periodEnd])
        ->selectRaw('type, COUNT(*) as count, SUM(amount) as total')
        ->groupBy('type')
        ->orderByDesc('total')
        ->get()
        ->map(fn ($row) => [
            'type' => ucfirst($row->type),
            'count' => (int) $row->count,
            'total' => (float) $row->total,
        ]);

    $propertyRevenue = Property::where('user_id', $userId)
        ->with(['units.leases.transactions'])
        ->get()
        ->map(function ($property) use ($periodStart, $periodEnd, $currentMonthStart, $currentMonthEnd) {
            $unitIds = $property->units->pluck('id');
            $leaseIds = Lease::whereIn('unit_id', $unitIds)->pluck('id');
            $revenue = (float) Transaction::whereIn('lease_id', $leaseIds)
                ->where('type', 'rent')
                ->whereBetween('paid_at', [$periodStart, $periodEnd])
                ->sum('amount');
            $expected = (float) Lease::whereIn('unit_id', $unitIds)->where('status', 'active')->sum('rent_amount');
            $collected = (float) Transaction::whereIn('lease_id', $leaseIds)
                ->where('type', 'rent')
                ->whereBetween('paid_at', [$currentMonthStart, $currentMonthEnd])
                ->sum('amount');
            $units = $property->units->count();
            $occupied = $property->units->where('status', 'occupied')->count();

            return [
                'name' => $property->name,
                'revenue' => $revenue,
                'units' => $units,
                'occupied' => $occupied,
                'occupancy_rate' => $units > 0 ? round(($occupied / $units) * 100, 1) : 0,
                'collection_rate' => $expected > 0 ? round(($collected / $expected) * 100, 1) : 0,
            ];
        })
        ->sortByDesc('revenue')
        ->values();

    $topTenants = Transaction::whereIn('lease_id', $leaseIds)
        ->tap($rentInPeriod)
        ->with('lease.tenant')
        ->get()
        ->groupBy('lease_id')
        ->map(fn ($payments) => [
            'name' => $payments->first()->lease->tenant->full_name,
            'total' => (float) $payments->sum('amount'),
            'count' => $payments->count(),
        ])
        ->sortByDesc('total')
        ->take(6)
        ->values();

    $avgDaysToPay = Transaction::whereIn('lease_id', $leaseIds)
        ->tap($rentInPeriod)
        ->whereNotNull('paid_at')
        ->get()
        ->avg(fn ($transaction) => \Carbon\Carbon::parse($transaction->paid_at)->day);

    $expiringLeases = Lease::whereIn('unit_id', $unitIds)
        ->where('status', 'active')
        ->whereNotNull('end_date')
        ->whereBetween('end_date', [now(), now()->addDays(60)])
        ->with(['tenant', 'unit.property'])
        ->orderBy('end_date')
        ->take(8)
        ->get();

    $unitMix = Unit::whereIn('property_id', $propertyIds)
        ->selectRaw('unit_type, COUNT(*) as count, AVG(base_rent) as avg_rent')
        ->groupBy('unit_type')
        ->orderByDesc('count')
        ->get()
        ->map(fn ($row) => [
            'type' => ucfirst(str_replace('_', ' ', $row->unit_type ?? 'apartment')),
            'count' => (int) $row->count,
            'avg_rent' => (float) round($row->avg_rent, 0),
        ]);

    $averageLeaseMonths = Lease::whereIn('unit_id', $unitIds)
        ->whereNotNull('end_date')
        ->whereIn('status', ['active', 'terminated', 'expired'])
        ->get()
        ->avg(fn ($lease) => \Carbon\Carbon::parse($lease->start_date)->diffInMonths($lease->end_date));

    $endedLastYear = Lease::whereIn('unit_id', $unitIds)
        ->whereIn('status', ['terminated', 'expired'])
        ->where('end_date', '>=', now()->subYear())
        ->count();

    $turnoverRate = $activeLeases->count() > 0 ? round(($endedLastYear / $activeLeases->count()) * 100, 1) : 0;
    $netOperatingSignal = max(0, $totalRevenue - $arrearsAmount);

    $chartPayload = [
        'months' => $monthlyData,
        'methods' => $paymentMethods->values(),
        'types' => $typeBreakdown->values(),
    ];

    return compact(
        'months',
        'periodStart',
        'periodEnd',
        'monthlyData',
        'totalRevenue',
        'previousRevenue',
        'revenueChangePct',
        'expectedRent',
        'collectedRent',
        'collectionRate',
        'arrearsAmount',
        'arrearsCount',
        'totalUnits',
        'occupiedUnits',
        'vacantUnits',
        'maintenanceUnits',
        'occupancyRate',
        'vacancyCost',
        'paymentMethods',
        'typeBreakdown',
        'propertyRevenue',
        'topTenants',
        'avgDaysToPay',
        'expiringLeases',
        'unitMix',
        'averageLeaseMonths',
        'turnoverRate',
        'netOperatingSignal',
        'chartPayload'
    );
});

?>

@php
    $data = $this->reportData;
    $maxPropertyRevenue = $data['propertyRevenue']->max('revenue') ?: 1;
    $methodTotal = $data['paymentMethods']->sum('total') ?: 1;
    $typeTotal = $data['typeBreakdown']->sum('total') ?: 1;
    $unitMixTotal = $data['unitMix']->sum('count') ?: 1;
    $trendColor = is_null($data['revenueChangePct'])
        ? '#667085'
        : ($data['revenueChangePct'] >= 0 ? '#1F7A4D' : '#9F403D');
@endphp

<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between print:hidden">
            <div>
                <p class="font-inter text-xs font-semibold uppercase" style="color:#7B8794; letter-spacing:0.08em;">
                    Portfolio intelligence
                </p>
                <h1 class="font-manrope text-2xl font-semibold mt-1" style="color:#24313A;">
                    Reports
                </h1>
                <p class="font-inter text-sm mt-1" style="color:#7B8794;">
                    @appdate($data['periodStart']) to @appdate($data['periodEnd'])
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <select wire:model.live="period"
                    class="font-inter text-sm px-4 py-2 rounded-lg border-0 focus:outline-none focus:ring-2"
                    style="background-color:#FFFFFF; color:#24313A; box-shadow:0 0 0 1px #D9E1E7;">
                    <option value="3">Last 3 months</option>
                    <option value="6">Last 6 months</option>
                    <option value="12">Last 12 months</option>
                </select>
                <button onclick="window.print()"
                    class="font-inter text-xs font-semibold text-white px-4 py-2.5 rounded-lg transition-opacity hover:opacity-90"
                    style="background-color:#35424D;">
                    Print report
                </button>
            </div>
        </div>

        <div class="hidden print:block mb-4">
            <h1 class="font-manrope text-2xl font-semibold" style="color:#24313A;">EstateFlow Portfolio Report</h1>
            <p class="font-inter text-sm" style="color:#7B8794;">
                @appdate($data['periodStart']) to @appdate($data['periodEnd'])
            </p>
        </div>

        <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="rounded-lg p-5" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                <p class="font-inter text-xs font-semibold uppercase" style="color:#7B8794; letter-spacing:0.06em;">Collected rent</p>
                <p class="font-manrope text-2xl font-bold mt-2" style="color:#24313A;">@money($data['totalRevenue'])</p>
                <p class="font-inter text-xs mt-2" style="color:{{ $trendColor }};">
                    @if(is_null($data['revenueChangePct']))
                        No previous period baseline
                    @else
                        {{ $data['revenueChangePct'] >= 0 ? '+' : '' }}{{ $data['revenueChangePct'] }}% vs previous {{ $data['months'] }} months
                    @endif
                </p>
            </div>

            <div class="rounded-lg p-5" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                <p class="font-inter text-xs font-semibold uppercase" style="color:#7B8794; letter-spacing:0.06em;">Collection rate</p>
                <p class="font-manrope text-2xl font-bold mt-2"
                    style="color:{{ $data['collectionRate'] >= 85 ? '#1F7A4D' : ($data['collectionRate'] >= 65 ? '#8A5A12' : '#9F403D') }};">
                    {{ $data['collectionRate'] }}%
                </p>
                <p class="font-inter text-xs mt-2" style="color:#7B8794;">
                    @money($data['collectedRent']) of @money($data['expectedRent']) this month
                </p>
            </div>

            <div class="rounded-lg p-5" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                <p class="font-inter text-xs font-semibold uppercase" style="color:#7B8794; letter-spacing:0.06em;">Open arrears</p>
                <p class="font-manrope text-2xl font-bold mt-2" style="color:#9F403D;">@money($data['arrearsAmount'])</p>
                <p class="font-inter text-xs mt-2" style="color:#7B8794;">
                    {{ $data['arrearsCount'] }} active {{ $data['arrearsCount'] === 1 ? 'lease' : 'leases' }} behind this month
                </p>
            </div>

            <div class="rounded-lg p-5" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                <p class="font-inter text-xs font-semibold uppercase" style="color:#7B8794; letter-spacing:0.06em;">Occupancy</p>
                <p class="font-manrope text-2xl font-bold mt-2" style="color:#24313A;">{{ $data['occupancyRate'] }}%</p>
                <p class="font-inter text-xs mt-2" style="color:#7B8794;">
                    {{ $data['occupiedUnits'] }} occupied, {{ $data['vacantUnits'] }} vacant, {{ $data['maintenanceUnits'] }} maintenance
                </p>
            </div>
        </section>

        <section
            id="reportChartPayload"
            data-chart-payload='@json($data['chartPayload'])'
            class="grid grid-cols-1 xl:grid-cols-3 gap-6"
            wire:key="report-charts-{{ $period }}">
            <div class="xl:col-span-2 rounded-lg p-6" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between mb-5">
                    <div>
                        <p class="font-manrope text-base font-semibold" style="color:#24313A;">Monthly revenue trend</p>
                        <p class="font-inter text-xs mt-1" style="color:#7B8794;">Changes with the selected reporting window.</p>
                    </div>
                    <p class="font-manrope text-lg font-bold" style="color:#24313A;">@money($data['totalRevenue'])</p>
                </div>
                <div class="relative h-[280px]">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <div class="rounded-lg p-6" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                <p class="font-manrope text-base font-semibold" style="color:#24313A;">Operating signals</p>
                <div class="mt-5 space-y-5">
                    <div>
                        <div class="flex justify-between text-xs font-inter mb-2" style="color:#7B8794;">
                            <span>Collection progress</span>
                            <span>{{ min($data['collectionRate'], 100) }}%</span>
                        </div>
                        <div class="h-2 rounded-full overflow-hidden" style="background:#E9EEF2;">
                            <div class="h-full rounded-full" style="width:{{ min($data['collectionRate'], 100) }}%; background:#1F7A4D;"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg p-3" style="background:#F7FAFC;">
                            <p class="font-inter text-xs" style="color:#7B8794;">Avg monthly rent</p>
                            <p class="font-manrope text-lg font-bold mt-1" style="color:#24313A;">@money($data['months'] > 0 ? $data['totalRevenue'] / $data['months'] : 0)</p>
                        </div>
                        <div class="rounded-lg p-3" style="background:#F7FAFC;">
                            <p class="font-inter text-xs" style="color:#7B8794;">Vacancy exposure</p>
                            <p class="font-manrope text-lg font-bold mt-1" style="color:#24313A;">@money($data['vacancyCost'])</p>
                        </div>
                    </div>

                    <div class="rounded-lg p-4" style="background:#FBF7F2;">
                        <p class="font-inter text-xs font-semibold uppercase" style="color:#8A5A12; letter-spacing:0.06em;">Lease risk</p>
                        <p class="font-manrope text-xl font-bold mt-2" style="color:#24313A;">{{ $data['expiringLeases']->count() }} expiring soon</p>
                        <p class="font-inter text-xs mt-1" style="color:#7B8794;">Next 60 days across active leases.</p>
                    </div>

                    <div class="rounded-lg p-4" style="background:#F7FAFC;">
                        <p class="font-inter text-xs" style="color:#7B8794;">Average lease term</p>
                        <p class="font-manrope text-xl font-bold mt-1" style="color:#24313A;">{{ round($data['averageLeaseMonths'] ?? 0, 1) }} months</p>
                        <p class="font-inter text-xs mt-1" style="color:#7B8794;">Turnover rate: {{ $data['turnoverRate'] }}%</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="rounded-lg p-6" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                <p class="font-manrope text-base font-semibold" style="color:#24313A;">Payment methods</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-5">
                    <div class="relative h-[210px]">
                        <canvas id="methodChart"></canvas>
                    </div>
                    <div class="space-y-4">
                        @forelse($data['paymentMethods'] as $method)
                            @php $pct = round(($method['total'] / $methodTotal) * 100); @endphp
                            <div>
                                <div class="flex justify-between gap-3">
                                    <p class="font-inter text-sm" style="color:#24313A;">{{ $method['method'] }}</p>
                                    <p class="font-manrope text-sm font-bold" style="color:#24313A;">{{ $pct }}%</p>
                                </div>
                                <p class="font-inter text-xs mt-1" style="color:#7B8794;">{{ $method['count'] }} payments, @money($method['total'])</p>
                            </div>
                        @empty
                            <p class="font-inter text-sm" style="color:#7B8794;">No rent payment method data in this period.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="rounded-lg p-6" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                <p class="font-manrope text-base font-semibold" style="color:#24313A;">Transaction mix</p>
                <div class="mt-5 space-y-4">
                    @forelse($data['typeBreakdown'] as $type)
                        @php
                            $pct = round(($type['total'] / $typeTotal) * 100);
                            $color = match($type['type']) {
                                'Rent' => '#35424D',
                                'Deposit' => '#1F7A4D',
                                'Penalty' => '#9F403D',
                                'Refund' => '#7B8794',
                                default => '#60717D',
                            };
                        @endphp
                        <div>
                            <div class="flex justify-between gap-3 mb-2">
                                <p class="font-inter text-sm" style="color:#24313A;">{{ $type['type'] }}</p>
                                <p class="font-manrope text-sm font-bold" style="color:#24313A;">@money($type['total'])</p>
                            </div>
                            <div class="h-2 rounded-full overflow-hidden" style="background:#E9EEF2;">
                                <div class="h-full rounded-full" style="width:{{ $pct }}%; background:{{ $color }};"></div>
                            </div>
                            <p class="font-inter text-xs mt-1" style="color:#7B8794;">{{ $type['count'] }} transactions, {{ $pct }}% of period value</p>
                        </div>
                    @empty
                        <p class="font-inter text-sm" style="color:#7B8794;">No transactions in this period.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="rounded-lg overflow-hidden" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
            <div class="px-6 py-5 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between" style="border-bottom:1px solid #E5EBEF;">
                <div>
                    <p class="font-manrope text-base font-semibold" style="color:#24313A;">Property performance</p>
                    <p class="font-inter text-xs mt-1" style="color:#7B8794;">Ranked by rent collected in the selected period.</p>
                </div>
                <p class="font-inter text-xs" style="color:#7B8794;">{{ $data['propertyRevenue']->count() }} properties</p>
            </div>

            <div class="hidden md:grid grid-cols-12 px-6 py-3 font-inter text-xs font-semibold uppercase" style="color:#7B8794; background:#F7FAFC; letter-spacing:0.06em;">
                <p class="col-span-4">Property</p>
                <p class="col-span-2">Revenue</p>
                <p class="col-span-2">Collection</p>
                <p class="col-span-2">Occupancy</p>
                <p class="col-span-2">Units</p>
            </div>

            @forelse($data['propertyRevenue'] as $property)
                @php $width = round(($property['revenue'] / $maxPropertyRevenue) * 100); @endphp
                <div class="px-6 py-4" style="border-top:1px solid #EFF3F6;">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 md:items-center">
                        <div class="md:col-span-4">
                            <p class="font-inter text-sm font-semibold" style="color:#24313A;">{{ $property['name'] }}</p>
                            <div class="h-1.5 rounded-full overflow-hidden mt-2" style="background:#E9EEF2;">
                                <div class="h-full rounded-full" style="width:{{ $width }}%; background:#35424D;"></div>
                            </div>
                        </div>
                        <p class="font-manrope text-sm font-bold md:col-span-2" style="color:#24313A;">@money($property['revenue'])</p>
                        <p class="font-inter text-sm md:col-span-2" style="color:#24313A;">{{ $property['collection_rate'] }}%</p>
                        <p class="font-inter text-sm md:col-span-2" style="color:#24313A;">{{ $property['occupancy_rate'] }}%</p>
                        <p class="font-inter text-sm md:col-span-2" style="color:#7B8794;">{{ $property['occupied'] }}/{{ $property['units'] }}</p>
                    </div>
                </div>
            @empty
                <div class="px-6 py-8">
                    <p class="font-inter text-sm" style="color:#7B8794;">No property revenue data yet.</p>
                </div>
            @endforelse
        </section>

        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="rounded-lg p-6" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                <p class="font-manrope text-base font-semibold" style="color:#24313A;">Top tenants</p>
                <div class="mt-4 space-y-4">
                    @forelse($data['topTenants'] as $index => $tenant)
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="font-manrope text-xs font-bold w-6 h-6 rounded-full flex items-center justify-center shrink-0" style="background:#E9EEF2; color:#35424D;">
                                    {{ $index + 1 }}
                                </span>
                                <div class="min-w-0">
                                    <p class="font-inter text-sm truncate" style="color:#24313A;">{{ $tenant['name'] }}</p>
                                    <p class="font-inter text-xs" style="color:#7B8794;">{{ $tenant['count'] }} payments</p>
                                </div>
                            </div>
                            <p class="font-manrope text-sm font-bold shrink-0" style="color:#24313A;">@money($tenant['total'])</p>
                        </div>
                    @empty
                        <p class="font-inter text-sm" style="color:#7B8794;">No tenant payment data for this period.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-lg p-6" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                <p class="font-manrope text-base font-semibold" style="color:#24313A;">Lease expiries</p>
                <div class="mt-4 space-y-4">
                    @forelse($data['expiringLeases'] as $lease)
                        @php $daysLeft = (int) now()->diffInDays($lease->end_date, false); @endphp
                        <div class="flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <p class="font-inter text-sm truncate" style="color:#24313A;">{{ $lease->tenant->full_name }}</p>
                                <p class="font-inter text-xs truncate" style="color:#7B8794;">{{ $lease->unit->property->name }} / Unit {{ $lease->unit->unit_number }}</p>
                            </div>
                            <span class="font-inter text-xs font-semibold px-2.5 py-1 rounded-full shrink-0"
                                style="background:{{ $daysLeft <= 14 ? '#FDECEA' : '#E9EEF2' }}; color:{{ $daysLeft <= 14 ? '#9F403D' : '#35424D' }};">
                                {{ $daysLeft }}d
                            </span>
                        </div>
                    @empty
                        <p class="font-inter text-sm" style="color:#7B8794;">No active leases expiring in the next 60 days.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-lg p-6" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                <p class="font-manrope text-base font-semibold" style="color:#24313A;">Unit mix</p>
                <div class="mt-4 space-y-4">
                    @forelse($data['unitMix'] as $unit)
                        @php $pct = round(($unit['count'] / $unitMixTotal) * 100); @endphp
                        <div>
                            <div class="flex justify-between gap-3 mb-2">
                                <p class="font-inter text-sm" style="color:#24313A;">{{ $unit['type'] }}</p>
                                <p class="font-inter text-sm" style="color:#24313A;">{{ $unit['count'] }}</p>
                            </div>
                            <div class="h-2 rounded-full overflow-hidden" style="background:#E9EEF2;">
                                <div class="h-full rounded-full" style="width:{{ $pct }}%; background:#60717D;"></div>
                            </div>
                            <p class="font-inter text-xs mt-1" style="color:#7B8794;">Avg rent @money($unit['avg_rent'])</p>
                        </div>
                    @empty
                        <p class="font-inter text-sm" style="color:#7B8794;">No unit data available.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        function estateFlowReportPayload() {
            const source = document.getElementById('reportChartPayload');
            if (!source) return { months: [], methods: [], types: [] };

            try {
                return JSON.parse(source.dataset.chartPayload || '{}');
            } catch (error) {
                return { months: [], methods: [], types: [] };
            }
        }

        function estateFlowMoney(value) {
            return 'KES ' + Number(value || 0).toLocaleString();
        }

        function renderEstateFlowReportCharts() {
            if (!window.Chart) return;

            const payload = estateFlowReportPayload();
            const revenueCtx = document.getElementById('revenueChart');
            const methodCtx = document.getElementById('methodChart');

            if (revenueCtx) {
                if (window.estateFlowRevenueChart) window.estateFlowRevenueChart.destroy();

                const months = payload.months || [];
                const maxValue = Math.max(0, ...months.map((item) => Number(item.amount || 0)));

                window.estateFlowRevenueChart = new Chart(revenueCtx, {
                    type: 'bar',
                    data: {
                        labels: months.map((item) => item.month),
                        datasets: [{
                            data: months.map((item) => item.amount),
                            backgroundColor: months.map((item) => Number(item.amount || 0) === maxValue ? '#35424D' : '#B7C4CC'),
                            borderRadius: 6,
                            borderSkipped: false,
                            maxBarThickness: 46,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: (context) => estateFlowMoney(context.raw),
                                },
                            },
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: '#7B8794', font: { family: 'Inter', size: 11 } },
                                border: { display: false },
                            },
                            y: {
                                grid: { color: '#EEF2F5' },
                                ticks: {
                                    color: '#7B8794',
                                    font: { family: 'Inter', size: 11 },
                                    callback: (value) => 'KES ' + (Number(value) / 1000).toFixed(0) + 'k',
                                },
                                border: { display: false },
                            },
                        },
                    },
                });
            }

            if (methodCtx) {
                if (window.estateFlowMethodChart) window.estateFlowMethodChart.destroy();

                const methods = payload.methods || [];

                window.estateFlowMethodChart = new Chart(methodCtx, {
                    type: 'doughnut',
                    data: {
                        labels: methods.map((item) => item.method),
                        datasets: [{
                            data: methods.map((item) => item.total),
                            backgroundColor: ['#35424D', '#1F7A4D', '#9F403D', '#8A5A12', '#7B8794'],
                            borderWidth: 0,
                            hoverOffset: 4,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '68%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: (context) => context.label + ': ' + estateFlowMoney(context.raw),
                                },
                            },
                        },
                    },
                });
            }
        }

        document.addEventListener('DOMContentLoaded', renderEstateFlowReportCharts);
        document.addEventListener('livewire:navigated', renderEstateFlowReportCharts);

        function hookEstateFlowReportCharts() {
            if (!window.Livewire || window.estateFlowReportChartsHooked) return;

            window.estateFlowReportChartsHooked = true;
            Livewire.hook('commit', ({ succeed }) => {
                succeed(() => requestAnimationFrame(renderEstateFlowReportCharts));
            });
        }

        hookEstateFlowReportCharts();
        document.addEventListener('livewire:init', hookEstateFlowReportCharts);
    </script>

    <style>
        @media print {
            nav, select, button, .print\:hidden { display: none !important; }
            body { background: white !important; }
            .rounded-lg { box-shadow: none !important; border: 1px solid #E5EBEF; break-inside: avoid; }
            @page { margin: 1.4cm; }
        }
    </style>
</div>
