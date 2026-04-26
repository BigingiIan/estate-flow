<?php

use App\Models\Property;
use App\Models\Unit;
use App\Models\Lease;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use function Livewire\Volt\{state, computed};

state(['period' => '6']);

$reportData = computed(function () {
    $userId      = Auth::id();
    $propertyIds = Property::where('user_id', $userId)->pluck('id');
    $unitIds     = Unit::whereIn('property_id', $propertyIds)->pluck('id');
    $leaseIds    = Lease::whereIn('unit_id', $unitIds)->pluck('id');
    $months      = (int) $this->period;

    // --- Monthly revenue ---
    $monthlyData  = [];
    $totalRevenue = 0;
    for ($i = $months - 1; $i >= 0; $i--) {
        $date      = now()->subMonths($i);
        $collected = Transaction::whereIn('lease_id', $leaseIds)
            ->where('type', 'rent')
            ->whereMonth('paid_at', $date->month)
            ->whereYear('paid_at', $date->year)
            ->sum('amount');
        $monthlyData[] = ['month' => $date->format('M Y'), 'amount' => (float) $collected];
        $totalRevenue += $collected;
    }

    // --- Occupancy ---
    $totalUnits    = Unit::whereIn('property_id', $propertyIds)->count();
    $occupiedUnits = Unit::whereIn('property_id', $propertyIds)->where('status', 'occupied')->count();
    $occupancyRate = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0;

    // --- Collection rate ---
    $expectedRent = Lease::whereIn('unit_id', $unitIds)
        ->where('status', 'active')
        ->sum('rent_amount');
    $collectedRent = Transaction::whereIn('lease_id', $leaseIds)
        ->where('type', 'rent')
        ->whereMonth('paid_at', now()->month)
        ->whereYear('paid_at', now()->year)
        ->sum('amount');
    $collectionRate = $expectedRent > 0
        ? round(($collectedRent / $expectedRent) * 100, 1)
        : 0;

    // --- Payment methods ---
    $paymentMethods = Transaction::whereIn('lease_id', $leaseIds)
        ->where('type', 'rent')
        ->whereNotNull('payment_method')
        ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
        ->groupBy('payment_method')
        ->orderByDesc('total')
        ->get()
        ->map(fn($r) => [
            'method' => ucfirst(str_replace('_', ' ', $r->payment_method)),
            'count'  => $r->count,
            'total'  => (float) $r->total,
        ]);

    // --- Transaction types ---
    $typeBreakdown = Transaction::whereIn('lease_id', $leaseIds)
        ->selectRaw('type, COUNT(*) as count, SUM(amount) as total')
        ->groupBy('type')
        ->orderByDesc('total')
        ->get()
        ->map(fn($r) => [
            'type'  => ucfirst($r->type),
            'count' => $r->count,
            'total' => (float) $r->total,
        ]);

    // --- Per-property revenue ---
    $propertyRevenue = Property::where('user_id', $userId)
        ->with('units.leases.transactions')
        ->get()
        ->map(function ($property) use ($months) {
            $pLeaseIds = Lease::whereIn('unit_id', $property->units->pluck('id'))->pluck('id');
            $revenue   = Transaction::whereIn('lease_id', $pLeaseIds)
                ->where('type', 'rent')
                ->where('paid_at', '>=', now()->subMonths($months))
                ->sum('amount');
            $units     = $property->units->count();
            $occupied  = $property->units->where('status', 'occupied')->count();
            return [
                'name'          => $property->name,
                'revenue'       => (float) $revenue,
                'units'         => $units,
                'occupied'      => $occupied,
                'occupancy_rate' => $units > 0 ? round(($occupied / $units) * 100) : 0,
            ];
        })
        ->sortByDesc('revenue')
        ->values();

    // --- Average days to pay ---
    $avgDaysToPay = Transaction::whereIn('lease_id', $leaseIds)
        ->where('type', 'rent')
        ->whereNotNull('paid_at')
        ->get()
        ->avg(fn($t) => \Carbon\Carbon::parse($t->paid_at)->day);

    // --- Top tenants ---
    $topTenants = Transaction::whereIn('lease_id', $leaseIds)
        ->where('type', 'rent')
        ->where('paid_at', '>=', now()->subMonths($months))
        ->with('lease.tenant')
        ->get()
        ->groupBy('lease_id')
        ->map(fn($txns) => [
            'name'   => $txns->first()->lease->tenant->full_name,
            'total'  => $txns->sum('amount'),
            'count'  => $txns->count(),
        ])
        ->sortByDesc('total')
        ->take(5)
        ->values();

    // --- Arrears count ---
    $arrearsCount = Lease::whereIn('unit_id', $unitIds)
        ->where('status', 'active')
        ->get()
        ->filter(function ($lease) {
            return !$lease->transactions()
                ->where('type', 'rent')
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->exists();
        })->count();

    // --- Expiring leases ---
    $expiringLeases = Lease::whereIn('unit_id', $unitIds)
        ->where('status', 'active')
        ->whereNotNull('end_date')
        ->whereBetween('end_date', [now(), now()->addDays(30)])
        ->with(['tenant', 'unit.property'])
        ->orderBy('end_date')
        ->get();

    // ---------- NEW ANALYTICS ----------
    // 1. Popular unit sizes by bedrooms
    $bedroomStats = Unit::whereIn('property_id', $propertyIds)
        ->selectRaw('bedrooms, COUNT(*) as count, AVG(base_rent) as avg_rent')
        ->groupBy('bedrooms')
        ->orderBy('bedrooms')
        ->get()
        ->map(fn($u) => [
            'bedrooms' => $u->bedrooms,
            'count'    => $u->count,
            'avg_rent' => round($u->avg_rent, 0),
        ]);

    // 2. Popular unit sizes by bathrooms
    $bathroomStats = Unit::whereIn('property_id', $propertyIds)
        ->selectRaw('bathrooms, COUNT(*) as count, AVG(base_rent) as avg_rent')
        ->groupBy('bathrooms')
        ->orderBy('bathrooms')
        ->get()
        ->map(fn($u) => [
            'bathrooms' => $u->bathrooms,
            'count'     => $u->count,
            'avg_rent'  => round($u->avg_rent, 0),
        ]);

    // 3. Average lease duration (in months) for completed or active leases with known end_date
    $averageLeaseMonths = Lease::whereIn('unit_id', $unitIds)
        ->whereNotNull('end_date')
        ->where('status', 'active')
        ->orWhere('status', 'terminated')
        ->get()
        ->avg(fn($l) => \Carbon\Carbon::parse($l->start_date)->diffInMonths($l->end_date));

    $averageLeaseMonths = round($averageLeaseMonths ?? 0, 1);

    // 4. Turnover rate: number of leases ended in last 12 months / average active leases
    $leasesEndedLastYear = Lease::whereIn('unit_id', $unitIds)
        ->where('status', 'terminated')
        ->where('end_date', '>=', now()->subYear())
        ->count();
    $avgActiveLeases = Lease::whereIn('unit_id', $unitIds)
        ->where('status', 'active')
        ->count();
    $turnoverRate = $avgActiveLeases > 0 ? round(($leasesEndedLastYear / $avgActiveLeases) * 100, 1) : 0;

    // 5. Average rent by bedroom count (already in bedroomStats, but maybe separate)
    //    we'll reuse above.

    return compact(
        'monthlyData', 'totalRevenue', 'occupancyRate',
        'totalUnits', 'occupiedUnits', 'collectionRate',
        'expectedRent', 'collectedRent', 'paymentMethods',
        'typeBreakdown', 'propertyRevenue', 'avgDaysToPay',
        'topTenants', 'arrearsCount', 'expiringLeases',
        'bedroomStats', 'bathroomStats', 'averageLeaseMonths', 'turnoverRate'
    );
});

?>

<div>
    <!-- Main content -->
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
            <div class="flex items-center gap-3 print:hidden">
                <select wire:model.live="period"
                    class="font-inter text-sm px-4 py-2 rounded-md focus:outline-none"
                    style="background-color:#FFFFFF; color:#283439;">
                    <option value="3">Last 3 months</option>
                    <option value="6">Last 6 months</option>
                    <option value="12">Last 12 months</option>
                </select>
                <button onclick="window.print()"
                    class="font-inter text-xs font-semibold text-white px-4 py-2.5
                        rounded-md transition-opacity hover:opacity-90"
                    style="background: linear-gradient(135deg, #585E6C, #4C5260);">
                    Print Report
                </button>
            </div>
        </div>

        {{-- Row 1: KPI cards (same as before) --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-5 mb-6">
            <div class="rounded-xl p-5" style="background-color:#FFFFFF;">
                <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">
                    Total Revenue
                </p>
                <p class="font-manrope text-2xl font-bold mt-2" style="color:#283439;">
                    KES {{ number_format($this->reportData['totalRevenue'], 0) }}
                </p>
                <p class="font-inter text-xs mt-1" style="color:#9BABB3;">
                    Last {{ $period }} months
                </p>
            </div>
            <div class="rounded-xl p-5" style="background-color:#FFFFFF;">
                <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">
                    Collection Rate
                </p>
                <p class="font-manrope text-2xl font-bold mt-2"
                    style="color: {{ $this->reportData['collectionRate'] >= 80 ? '#22863a' : '#9F403D' }};">
                    {{ $this->reportData['collectionRate'] }}%
                </p>
                <p class="font-inter text-xs mt-1" style="color:#9BABB3;">
                    {{ $this->reportData['arrearsCount'] }} tenants unpaid this month
                </p>
            </div>
            <div class="rounded-xl p-5" style="background-color:#FFFFFF;">
                <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">
                    Occupancy Rate
                </p>
                <p class="font-manrope text-2xl font-bold mt-2" style="color:#283439;">
                    {{ $this->reportData['occupancyRate'] }}%
                </p>
                <p class="font-inter text-xs mt-1" style="color:#9BABB3;">
                    {{ $this->reportData['occupiedUnits'] }} of {{ $this->reportData['totalUnits'] }} units
                </p>
            </div>
            <div class="rounded-xl p-5" style="background-color:#FFFFFF;">
                <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">
                    Avg. Days to Pay
                </p>
                <p class="font-manrope text-2xl font-bold mt-2" style="color:#283439;">
                    Day {{ number_format($this->reportData['avgDaysToPay'], 0) }}
                </p>
                <p class="font-inter text-xs mt-1" style="color:#9BABB3;">
                    Average payment day of month
                </p>
            </div>
        </div>

        {{-- Row 2: Revenue chart + Collection progress (unchanged) --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

            <div class="lg:col-span-2 rounded-xl p-6" style="background-color:#FFFFFF;">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <p class="font-inter text-sm font-medium" style="color:#283439;">
                            Monthly Revenue
                        </p>
                        <p class="font-inter text-xs mt-0.5" style="color:#9BABB3;">
                            Rent collected per month
                        </p>
                    </div>
                    <p class="font-manrope text-lg font-bold" style="color:#283439;">
                        KES {{ number_format($this->reportData['totalRevenue'], 0) }}
                    </p>
                </div>
                <div style="position:relative; height:200px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <div class="space-y-5">
                <div class="rounded-xl p-6" style="background-color:#FFFFFF;">
                    <p class="font-inter text-xs uppercase tracking-widest mb-3"
                        style="color:#9BABB3;">This Month's Collection</p>
                    <p class="font-manrope text-3xl font-bold" style="color:#283439;">
                        {{ $this->reportData['collectionRate'] }}%
                    </p>
                    <div class="mt-3 rounded-full overflow-hidden" style="background-color:#EFF4F7; height:6px;">
                        <div class="h-full rounded-full"
                            style="width:{{ min($this->reportData['collectionRate'], 100) }}%;
                                   background: linear-gradient(135deg,
                                   {{ $this->reportData['collectionRate'] >= 80 ? '#22863a' : '#9F403D' }},
                                   {{ $this->reportData['collectionRate'] >= 80 ? '#196032' : '#7a2f2d' }});">
                        </div>
                    </div>
                    <div class="flex justify-between mt-2">
                        <p class="font-inter text-xs" style="color:#9BABB3;">
                            KES {{ number_format($this->reportData['collectedRent'], 0) }} collected
                        </p>
                        <p class="font-inter text-xs" style="color:#9BABB3;">
                            of KES {{ number_format($this->reportData['expectedRent'], 0) }}
                        </p>
                    </div>
                </div>
                <div class="rounded-xl p-6" style="background-color:#FFFFFF;">
                    <p class="font-inter text-xs uppercase tracking-widest mb-3"
                        style="color:#9BABB3;">Occupancy</p>
                    <p class="font-manrope text-3xl font-bold" style="color:#283439;">
                        {{ $this->reportData['occupancyRate'] }}%
                    </p>
                    <div class="mt-3 rounded-full overflow-hidden" style="background-color:#EFF4F7; height:6px;">
                        <div class="h-full rounded-full"
                            style="width:{{ $this->reportData['occupancyRate'] }}%;
                                   background: linear-gradient(135deg, #585E6C, #4C5260);">
                        </div>
                    </div>
                    <p class="font-inter text-xs mt-2" style="color:#9BABB3;">
                        {{ $this->reportData['occupiedUnits'] }} occupied ·
                        {{ $this->reportData['totalUnits'] - $this->reportData['occupiedUnits'] }} vacant
                    </p>
                </div>
            </div>
        </div>

        {{-- Row 3: Payment methods + Transaction types (unchanged) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Payment methods -->
            <div class="rounded-xl p-6" style="background-color:#FFFFFF;">
                <p class="font-manrope text-sm font-semibold mb-1" style="color:#283439;">
                    Payment Methods
                </p>
                <p class="font-inter text-xs mb-5" style="color:#9BABB3;">
                    How tenants prefer to pay
                </p>
                @php $methodTotal = $this->reportData['paymentMethods']->sum('total'); @endphp
                @forelse($this->reportData['paymentMethods'] as $method)
                @php $pct = $methodTotal > 0 ? round(($method['total'] / $methodTotal) * 100) : 0; @endphp
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-1">
                        <p class="font-inter text-sm" style="color:#283439;">{{ $method['method'] }}</p>
                        <div class="flex items-center gap-3">
                            <p class="font-inter text-xs" style="color:#9BABB3;">
                                {{ $method['count'] }} payments
                            </p>
                            <p class="font-manrope text-sm font-bold" style="color:#283439;">
                                {{ $pct }}%
                            </p>
                        </div>
                    </div>
                    <div class="rounded-full overflow-hidden" style="background-color:#EFF4F7; height:5px;">
                        <div class="h-full rounded-full"
                            style="width:{{ $pct }}%; background: linear-gradient(135deg, #585E6C, #4C5260);">
                        </div>
                    </div>
                    <p class="font-inter text-xs mt-1" style="color:#9BABB3;">
                        KES {{ number_format($method['total'], 0) }}
                    </p>
                </div>
                @empty
                <p class="font-inter text-sm" style="color:#9BABB3;">No payment data yet.</p>
                @endforelse
                <div class="mt-4" style="position:relative; height:160px;"><canvas id="methodChart"></canvas></div>
            </div>

            <!-- Transaction types -->
            <div class="rounded-xl p-6" style="background-color:#FFFFFF;">
                <p class="font-manrope text-sm font-semibold mb-1" style="color:#283439;">
                    Transaction Breakdown
                </p>
                <p class="font-inter text-xs mb-5" style="color:#9BABB3;">
                    Revenue by transaction type
                </p>
                @php
                    $typeTotal = $this->reportData['typeBreakdown']->sum('total');
                    $typeColors = ['Rent' => '#585E6C', 'Deposit' => '#4C5260', 'Penalty' => '#9F403D', 'Refund' => '#9BABB3'];
                @endphp
                @forelse($this->reportData['typeBreakdown'] as $type)
                @php $pct = $typeTotal > 0 ? round(($type['total'] / $typeTotal) * 100) : 0; $color = $typeColors[$type['type']] ?? '#585E6C'; @endphp
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-2">
                            <div class="w-2.5 h-2.5 rounded-full" style="background-color:{{ $color }};"></div>
                            <p class="font-inter text-sm" style="color:#283439;">{{ $type['type'] }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <p class="font-inter text-xs" style="color:#9BABB3;">
                                {{ $type['count'] }} txn{{ $type['count'] > 1 ? 's' : '' }}
                            </p>
                            <p class="font-manrope text-sm font-bold" style="color:#283439;">
                                KES {{ number_format($type['total'], 0) }}
                            </p>
                        </div>
                    </div>
                    <div class="rounded-full overflow-hidden" style="background-color:#EFF4F7; height:5px;">
                        <div class="h-full rounded-full" style="width:{{ $pct }}%; background-color:{{ $color }};"></div>
                    </div>
                </div>
                @empty
                <p class="font-inter text-sm" style="color:#9BABB3;">No transactions yet.</p>
                @endforelse
            </div>
        </div>

        {{-- NEW ROW: Popular unit sizes & Lease metrics --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Bedroom popularity -->
            <div class="rounded-xl p-6" style="background-color:#FFFFFF;">
                <p class="font-manrope text-sm font-semibold mb-1" style="color:#283439;">
                    Popular Unit Sizes
                </p>
                <p class="font-inter text-xs mb-4" style="color:#9BABB3;">
                    Distribution by bedroom count
                </p>
                <div class="space-y-3">
                    @foreach($this->reportData['bedroomStats'] as $b)
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <p class="font-inter text-sm" style="color:#283439;">
                                {{ $b['bedrooms'] }} {{ Str::plural('Bedroom', $b['bedrooms']) }}
                            </p>
                            <p class="font-inter text-sm font-medium" style="color:#283439;">
                                {{ $b['count'] }} units
                            </p>
                        </div>
                        <div class="rounded-full overflow-hidden mb-1" style="background-color:#EFF4F7; height:6px;">
                            <div class="h-full rounded-full"
                                style="width: {{ ($b['count'] / $this->reportData['totalUnits']) * 100 }}%;
                                       background: linear-gradient(135deg, #585E6C, #4C5260);">
                            </div>
                        </div>
                        <p class="font-inter text-xs" style="color:#9BABB3;">
                            Avg rent: KES {{ number_format($b['avg_rent'], 0) }}
                        </p>
                    </div>
                    @endforeach
                </div>
                @if($this->reportData['bedroomStats']->isEmpty())
                    <p class="font-inter text-sm" style="color:#9BABB3;">No unit data available.</p>
                @endif
            </div>

            <!-- Bathroom popularity + Lease metrics -->
            <div class="space-y-5">
                <div class="rounded-xl p-6" style="background-color:#FFFFFF;">
                    <p class="font-inter text-xs uppercase tracking-widest mb-2" style="color:#9BABB3;">
                        Bathroom Distribution
                    </p>
                    <div class="flex flex-wrap gap-3">
                        @foreach($this->reportData['bathroomStats'] as $b)
                        <div class="flex-1 min-w-[80px] text-center p-2 rounded-lg" style="background-color:#EFF4F7;">
                            <p class="font-manrope text-xl font-bold" style="color:#283439;">{{ $b['count'] }}</p>
                            <p class="font-inter text-xs" style="color:#9BABB3;">{{ $b['bathrooms'] }} Bath</p>
                            <p class="font-inter text-xs mt-1" style="color:#585E6C;">
                                KES {{ number_format($b['avg_rent'], 0) }}
                            </p>
                        </div>
                        @endforeach
                    </div>
                    @if($this->reportData['bathroomStats']->isEmpty())
                        <p class="font-inter text-sm" style="color:#9BABB3;">No bathroom data.</p>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div class="rounded-xl p-5" style="background-color:#FFFFFF;">
                        <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Avg Lease Term</p>
                        <p class="font-manrope text-2xl font-bold mt-1" style="color:#283439;">
                            {{ $this->reportData['averageLeaseMonths'] }} mo
                        </p>
                        <p class="font-inter text-xs mt-1" style="color:#9BABB3;">
                            Average contract length
                        </p>
                    </div>
                    <div class="rounded-xl p-5" style="background-color:#FFFFFF;">
                        <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Turnover Rate</p>
                        <p class="font-manrope text-2xl font-bold mt-1"
                            style="color: {{ $this->reportData['turnoverRate'] > 30 ? '#9F403D' : '#283439' }};">
                            {{ $this->reportData['turnoverRate'] }}%
                        </p>
                        <p class="font-inter text-xs mt-1" style="color:#9BABB3;">
                            Last 12 months
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Row 4: Per-property revenue (unchanged) --}}
        <div class="rounded-xl mb-6" style="background-color:#FFFFFF;">
            <div class="px-6 py-5" style="border-bottom: 1px solid #EFF4F7;">
                <p class="font-manrope text-base font-semibold" style="color:#283439;">
                    Revenue by Property
                </p>
                <p class="font-inter text-xs mt-0.5" style="color:#9BABB3;">
                    Last {{ $period }} months
                </p>
            </div>
            <div class="grid grid-cols-5 px-6 py-3" style="background-color:#EFF4F7;">
                <p class="font-inter text-xs font-medium uppercase tracking-widest col-span-2" style="color:#9BABB3;">Property</p>
                <p class="font-inter text-xs font-medium uppercase tracking-widest" style="color:#9BABB3;">Revenue</p>
                <p class="font-inter text-xs font-medium uppercase tracking-widest" style="color:#9BABB3;">Occupancy</p>
                <p class="font-inter text-xs font-medium uppercase tracking-widest" style="color:#9BABB3;">Units</p>
            </div>
            @php $maxRevenue = $this->reportData['propertyRevenue']->max('revenue') ?: 1; @endphp
            @forelse($this->reportData['propertyRevenue'] as $prop)
            <div class="px-6 py-4 transition-colors"
                onmouseenter="this.style.backgroundColor='#EFF4F7'"
                onmouseleave="this.style.backgroundColor='transparent'">
                <div class="grid grid-cols-5 items-center mb-2">
                    <div class="col-span-2"><p class="font-inter text-sm font-medium" style="color:#283439;">{{ $prop['name'] }}</p></div>
                    <p class="font-manrope text-sm font-bold" style="color:#283439;">KES {{ number_format($prop['revenue'], 0) }}</p>
                    <p class="font-inter text-sm" style="color:#283439;">{{ $prop['occupancy_rate'] }}%</p>
                    <p class="font-inter text-sm" style="color:#9BABB3;">{{ $prop['occupied'] }}/{{ $prop['units'] }}</p>
                </div>
                <div class="rounded-full overflow-hidden" style="background-color:#EFF4F7; height:4px;">
                    <div class="h-full rounded-full"
                        style="width:{{ round(($prop['revenue'] / $maxRevenue) * 100) }}%;
                               background: linear-gradient(135deg, #585E6C, #4C5260);">
                    </div>
                </div>
            </div>
            @empty
            <div class="px-6 py-8 text-center"><p class="font-inter text-sm" style="color:#9BABB3;">No revenue data yet.</p></div>
            @endforelse
        </div>

        {{-- Row 5: Top tenants + Expiring leases (unchanged) --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            <div class="rounded-xl" style="background-color:#FFFFFF;">
                <div class="px-6 py-4" style="border-bottom: 1px solid #EFF4F7;">
                    <p class="font-manrope text-sm font-semibold" style="color:#283439;">Top Paying Tenants</p>
                    <p class="font-inter text-xs mt-0.5" style="color:#9BABB3;">Last {{ $period }} months</p>
                </div>
                @forelse($this->reportData['topTenants'] as $i => $tenant)
                <div class="flex items-center justify-between px-6 py-3 transition-colors"
                    onmouseenter="this.style.backgroundColor='#EFF4F7'"
                    onmouseleave="this.style.backgroundColor='transparent'">
                    <div class="flex items-center gap-3">
                        <p class="font-manrope text-xs font-bold w-5 text-right" style="color:#9BABB3;">{{ $i + 1 }}</p>
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-manrope text-xs font-bold"
                            style="background-color:#E7EFF3; color:#585E6C;">
                            {{ strtoupper(substr($tenant['name'], 0, 1)) }}{{ strtoupper(substr(strstr($tenant['name'], ' '), 1, 1)) }}
                        </div>
                        <div>
                            <p class="font-inter text-sm" style="color:#283439;">{{ $tenant['name'] }}</p>
                            <p class="font-inter text-xs" style="color:#9BABB3;">{{ $tenant['count'] }} payment(s)</p>
                        </div>
                    </div>
                    <p class="font-manrope text-sm font-bold" style="color:#283439;">KES {{ number_format($tenant['total'], 0) }}</p>
                </div>
                @empty
                <div class="px-6 py-8 text-center"><p class="font-inter text-sm" style="color:#9BABB3;">No payment data yet.</p></div>
                @endforelse
            </div>

            <div class="rounded-xl" style="background-color:#FFFFFF;">
                <div class="px-6 py-4 flex items-center justify-between" style="border-bottom: 1px solid #EFF4F7;">
                    <div>
                        <p class="font-manrope text-sm font-semibold" style="color:#283439;">Leases Expiring Soon</p>
                        <p class="font-inter text-xs mt-0.5" style="color:#9BABB3;">Next 30 days</p>
                    </div>
                    <span class="font-inter text-xs font-medium px-3 py-1 rounded-full"
                        style="background-color:#FDECEA; color:#9F403D;">
                        {{ $this->reportData['expiringLeases']->count() }} expiring
                    </span>
                </div>
                @forelse($this->reportData['expiringLeases'] as $lease)
                @php $daysLeft = (int) now()->diffInDays($lease->end_date, false); @endphp
                <div class="flex items-center justify-between px-6 py-3 transition-colors"
                    onmouseenter="this.style.backgroundColor='#EFF4F7'"
                    onmouseleave="this.style.backgroundColor='transparent'">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-manrope text-xs font-bold"
                            style="background-color:#E7EFF3; color:#585E6C;">
                            {{ strtoupper(substr($lease->tenant->full_name, 0, 1)) }}{{ strtoupper(substr(strstr($lease->tenant->full_name, ' '), 1, 1)) }}
                        </div>
                        <div>
                            <p class="font-inter text-sm" style="color:#283439;">{{ $lease->tenant->full_name }}</p>
                            <p class="font-inter text-xs" style="color:#9BABB3;">{{ $lease->unit->property->name }} · Unit {{ $lease->unit->unit_number }}</p>
                        </div>
                    </div>
                    <span class="font-inter text-xs font-medium px-2.5 py-1 rounded-full"
                        style="background-color: {{ $daysLeft <= 7 ? '#FDECEA' : '#E7EFF3' }};
                               color: {{ $daysLeft <= 7 ? '#9F403D' : '#585E6C' }};">
                        {{ $daysLeft }}d left
                    </span>
                </div>
                @empty
                <div class="px-6 py-8 text-center"><p class="font-inter text-sm" style="color:#9BABB3;">No leases expiring soon.</p></div>
                @endforelse
            </div>
        </div>

    </div>

    <!-- Charts scripts (unchanged) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        const monthlyData   = @json($this->reportData['monthlyData']);
        const methodData    = @json($this->reportData['paymentMethods']);

        function renderCharts() {
            const revenueCtx = document.getElementById('revenueChart');
            if (revenueCtx) {
                if (window.revenueChartInstance) window.revenueChartInstance.destroy();
                const maxVal = Math.max(...monthlyData.map(d => d.amount));
                window.revenueChartInstance = new Chart(revenueCtx, {
                    type: 'bar',
                    data: {
                        labels: monthlyData.map(d => d.month),
                        datasets: [{
                            data: monthlyData.map(d => d.amount),
                            backgroundColor: monthlyData.map(d => d.amount === maxVal ? '#585E6C' : '#D5DCE3'),
                            borderRadius: 4,
                            borderSkipped: false,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => 'KES ' + ctx.raw.toLocaleString() } } },
                        scales: {
                            x: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 11 }, color: '#9BABB3' }, border: { display: false } },
                            y: { grid: { color: '#F0F4F7' }, ticks: { font: { family: 'Inter', size: 11 }, color: '#9BABB3', callback: v => 'KES ' + (v / 1000).toFixed(0) + 'k' }, border: { display: false } }
                        }
                    }
                });
            }
            const methodCtx = document.getElementById('methodChart');
            if (methodCtx && methodData.length > 0) {
                if (window.methodChartInstance) window.methodChartInstance.destroy();
                window.methodChartInstance = new Chart(methodCtx, {
                    type: 'doughnut',
                    data: {
                        labels: methodData.map(d => d.method),
                        datasets: [{ data: methodData.map(d => d.total), backgroundColor: ['#585E6C', '#8B9299', '#B5BBC0', '#D5DCE3'], borderWidth: 0, hoverOffset: 4 }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: { position: 'bottom', labels: { font: { family: 'Inter', size: 11 }, color: '#9BABB3', padding: 12, boxWidth: 10, boxHeight: 10 } },
                            tooltip: { callbacks: { label: ctx => ctx.label + ': KES ' + ctx.raw.toLocaleString() } }
                        }
                    }
                });
            }
        }

        document.addEventListener('DOMContentLoaded', renderCharts);
        document.addEventListener('livewire:navigated', renderCharts);
        Livewire.hook('commit', ({ succeed }) => { succeed(() => { requestAnimationFrame(renderCharts); }); });
    </script>

    <style>
        @media print {
            nav, select, button, .print\:hidden { display: none !important; }
            body { background: white !important; }
            .rounded-xl { box-shadow: none !important; }
            @page { margin: 1.5cm; }
        }
    </style>
</div>