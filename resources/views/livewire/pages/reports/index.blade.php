<?php

use App\Models\Property;
use App\Models\Unit;
use App\Models\Lease;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use function Livewire\Volt\{state, computed};

state(['period' => '6']); // months to show

$reportData = computed(function () {
    $userId      = Auth::id();
    $propertyIds = Property::where('user_id', $userId)->pluck('id');
    $unitIds     = Unit::whereIn('property_id', $propertyIds)->pluck('id');
    $leaseIds    = Lease::whereIn('unit_id', $unitIds)->pluck('id');

    $months      = (int) $this->period;
    $monthlyData = [];
    $totalRevenue = 0;

    for ($i = $months - 1; $i >= 0; $i--) {
        $date     = now()->subMonths($i);
        $collected = Transaction::whereIn('lease_id', $leaseIds)
            ->where('type', 'rent')
            ->whereMonth('paid_at', $date->month)
            ->whereYear('paid_at', $date->year)
            ->sum('amount');

        $monthlyData[] = [
            'month'  => $date->format('M Y'),
            'amount' => (float) $collected,
        ];

        $totalRevenue += $collected;
    }

    // Occupancy
    $totalUnits    = Unit::whereIn('property_id', $propertyIds)->count();
    $occupiedUnits = Unit::whereIn('property_id', $propertyIds)
        ->where('status', 'occupied')->count();
    $occupancyRate = $totalUnits > 0
        ? round(($occupiedUnits / $totalUnits) * 100, 1)
        : 0;

    // Leases expiring in next 30 days
    $expiringLeases = Lease::whereIn('unit_id', $unitIds)
        ->where('status', 'active')
        ->whereNotNull('end_date')
        ->whereBetween('end_date', [now(), now()->addDays(30)])
        ->with(['tenant', 'unit.property'])
        ->orderBy('end_date')
        ->get();

    return compact(
        'monthlyData',
        'totalRevenue',
        'occupancyRate',
        'totalUnits',
        'occupiedUnits',
        'expiringLeases'
    );
});

?>

<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        {{-- Header --}}
        <div class="flex items-start justify-between mb-8">
            <div>
                <p class="font-inter text-xs font-medium uppercase tracking-widest"
                    style="color:#9BABB3; letter-spacing:0.08em;">Performance Overview</p>
                <h1 class="font-manrope text-2xl font-semibold mt-1" style="color:#283439;">
                    Analytical Reports
                </h1>
            </div>
            <div class="flex items-center gap-3">
                <select wire:model.live="period"
                    class="font-inter text-sm px-4 py-2 rounded-md focus:outline-none"
                    style="background-color:#FFFFFF; color:#283439;">
                    <option value="3">Last 3 months</option>
                    <option value="6">Last 6 months</option>
                    <option value="12">Last 12 months</option>
                </select>
                <button onclick="window.print()"
                    class="font-inter text-xs font-semibold text-white px-4 py-2.5
                        rounded-md transition-opacity hover:opacity-90 print:hidden"
                    style="background: linear-gradient(135deg, #585E6C, #4C5260);">
                    Print Report
                </button>
            </div>
        </div>

        {{-- Top Row: Chart + Occupancy --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

            {{-- Revenue Chart --}}
            <div class="lg:col-span-2 rounded-xl p-6" style="background-color:#FFFFFF;">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <p class="font-inter text-sm font-medium" style="color:#283439;">
                            Monthly Revenue Report
                        </p>
                        <p class="font-inter text-xs mt-0.5" style="color:#9BABB3;">
                            Gross income across your portfolio
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="font-inter text-xs uppercase tracking-widest"
                            style="color:#9BABB3; letter-spacing:0.08em;">Total Revenue</p>
                        <p class="font-manrope text-xl font-bold mt-1" style="color:#283439;">
                            KES {{ number_format($this->reportData['totalRevenue'], 0) }}
                        </p>
                    </div>
                </div>
                <div style="position:relative; height:240px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            {{-- Occupancy Card --}}
            <div class="flex flex-col gap-6">
                <div class="rounded-xl p-6 flex-1" style="background-color:#FFFFFF;">
                    <p class="font-inter text-xs font-medium uppercase tracking-widest"
                        style="color:#9BABB3; letter-spacing:0.08em;">Occupancy Efficiency</p>
                    <p class="font-manrope mt-4 font-bold" style="font-size:3rem; color:#283439; line-height:1;">
                        {{ $this->reportData['occupancyRate'] }}%
                    </p>
                    <p class="font-inter text-xs mt-2" style="color:#9BABB3;">
                        {{ $this->reportData['occupiedUnits'] }} of
                        {{ $this->reportData['totalUnits'] }} units occupied
                    </p>

                    {{-- Simple progress bar --}}
                    <div class="mt-4 rounded-full overflow-hidden" style="background-color:#EFF4F7; height:6px;">
                        <div class="h-full rounded-full transition-all"
                            style="width:{{ $this->reportData['occupancyRate'] }}%;
                                   background: linear-gradient(135deg, #585E6C, #4C5260);">
                        </div>
                    </div>
                </div>

                {{-- Quick stats --}}
                <div class="rounded-xl p-6" style="background-color:#FFFFFF;">
                    <p class="font-inter text-xs font-medium uppercase tracking-widest mb-4"
                        style="color:#9BABB3; letter-spacing:0.08em;">Portfolio Snapshot</p>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <p class="font-inter text-sm" style="color:#283439;">Total Units</p>
                            <p class="font-manrope text-sm font-bold" style="color:#283439;">
                                {{ $this->reportData['totalUnits'] }}
                            </p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="font-inter text-sm" style="color:#283439;">Occupied</p>
                            <p class="font-manrope text-sm font-bold" style="color:#585E6C;">
                                {{ $this->reportData['occupiedUnits'] }}
                            </p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="font-inter text-sm" style="color:#283439;">Vacant</p>
                            <p class="font-manrope text-sm font-bold" style="color:#9F403D;">
                                {{ $this->reportData['totalUnits'] - $this->reportData['occupiedUnits'] }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Expiring Leases Table --}}
        <div class="rounded-xl" style="background-color:#FFFFFF;">
            <div class="flex items-center justify-between px-6 py-5"
                style="border-bottom: 1px solid #EFF4F7;">
                <div>
                    <p class="font-manrope text-base font-semibold" style="color:#283439;">
                        Leases Expiring Soon
                    </p>
                    <p class="font-inter text-xs mt-0.5 uppercase tracking-widest"
                        style="color:#9BABB3; letter-spacing:0.08em;">
                        Next 30 days
                    </p>
                </div>
                <span class="font-inter text-xs font-medium px-3 py-1 rounded-full"
                    style="background-color:#FDECEA; color:#9F403D;">
                    {{ $this->reportData['expiringLeases']->count() }} expiring
                </span>
            </div>

            {{-- Table Header --}}
            <div class="grid grid-cols-4 px-6 py-3"
                style="background-color:#EFF4F7;">
                <p class="font-inter text-xs font-medium uppercase tracking-widest"
                    style="color:#9BABB3;">Tenant</p>
                <p class="font-inter text-xs font-medium uppercase tracking-widest"
                    style="color:#9BABB3;">Property / Unit</p>
                <p class="font-inter text-xs font-medium uppercase tracking-widest"
                    style="color:#9BABB3;">Expiry Date</p>
                <p class="font-inter text-xs font-medium uppercase tracking-widest"
                    style="color:#9BABB3;">Days Left</p>
            </div>

            @forelse($this->reportData['expiringLeases'] as $lease)
            <div class="grid grid-cols-4 items-center px-6 py-4 transition-colors"
                onmouseenter="this.style.backgroundColor='#EFF4F7'"
                onmouseleave="this.style.backgroundColor='transparent'">

                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center
                        font-manrope text-xs font-bold"
                        style="background-color:#E7EFF3; color:#585E6C;">
                        {{ strtoupper(substr($lease->tenant->full_name, 0, 1)) }}{{ strtoupper(substr(strstr($lease->tenant->full_name, ' '), 1, 1)) }}
                    </div>
                    <p class="font-inter text-sm" style="color:#283439;">
                        {{ $lease->tenant->full_name }}
                    </p>
                </div>

                <p class="font-inter text-sm" style="color:#283439;">
                    {{ $lease->unit->property->name }} — Unit {{ $lease->unit->unit_number }}
                </p>

                <p class="font-inter text-sm" style="color:#283439;">
                    {{ \Carbon\Carbon::parse($lease->end_date)->format('d M Y') }}
                </p>

                @php $daysLeft = now()->diffInDays($lease->end_date, false); @endphp
                <span class="font-inter text-xs font-medium px-2.5 py-1 rounded-full inline-flex w-fit"
                    style="background-color: {{ $daysLeft <= 7 ? '#FDECEA' : '#E7EFF3' }};
                           color: {{ $daysLeft <= 7 ? '#9F403D' : '#585E6C' }};">
                    {{ $daysLeft }} days
                </span>

            </div>
            @empty
            <div class="px-6 py-12 text-center">
                <p class="font-inter text-sm" style="color:#9BABB3;">
                    No leases expiring in the next 30 days.
                </p>
            </div>
            @endforelse

        </div>

    </div>

    {{-- Chart.js --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        const monthlyData = @json($this->reportData['monthlyData']);

        function renderChart() {
            const ctx = document.getElementById('revenueChart');
            if (!ctx) return;

            if (window.revenueChartInstance) {
                window.revenueChartInstance.destroy();
            }

            window.revenueChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: monthlyData.map(d => d.month),
                    datasets: [{
                        data: monthlyData.map(d => d.amount),
                        backgroundColor: monthlyData.map((d, i) => {
                            const max = Math.max(...monthlyData.map(m => m.amount));
                            return d.amount === max ? '#585E6C' : '#D5DCE3';
                        }),
                        borderRadius: 4,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => 'KES ' + ctx.raw.toLocaleString()
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                font: { family: 'Inter', size: 11 },
                                color: '#9BABB3'
                            },
                            border: { display: false }
                        },
                        y: {
                            grid: { color: '#F0F4F7' },
                            ticks: {
                                font: { family: 'Inter', size: 11 },
                                color: '#9BABB3',
                                callback: v => 'KES ' + (v / 1000).toFixed(0) + 'k'
                            },
                            border: { display: false }
                        }
                    }
                }
            });
        }

        // Render on load
        document.addEventListener('DOMContentLoaded', renderChart);

        // Re-render when Livewire updates the period filter
        document.addEventListener('livewire:navigated', renderChart);
        Livewire.hook('commit', ({ succeed }) => {
            succeed(() => {
                requestAnimationFrame(renderChart);
            });
        });
    </script>

    <style>
        @media print {
            nav, select, button, .print\:hidden { 
                display: none !important; 
            }
            body { 
                background: white !important; 
            }
            .rounded-xl { 
                box-shadow: none !important; 
            }
            @page { 
                margin: 1.5cm; 
            }
        }
    </style>
</div>