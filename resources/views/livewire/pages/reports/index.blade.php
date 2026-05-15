<?php

use App\Models\Lease;
use App\Models\Property;
use App\Models\Transaction;
use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use function Livewire\Volt\{computed, state};

state([
    'period'       => '6',
    'basis'        => 'cash',      // cash | accrual
    'show_vacant'  => false,       // drill-down toggle
]);

$reportData = computed(function () {
    $userId      = Auth::id();
    $months      = in_array((int) $this->period, [3, 6, 12], true) ? (int) $this->period : 6;
    $periodStart = now()->startOfMonth()->subMonths($months - 1);
    $periodEnd   = now()->endOfMonth();
    $previousStart = $periodStart->copy()->subMonths($months);
    $previousEnd   = $periodStart->copy()->subDay()->endOfDay();
    $currentMonthStart = now()->startOfMonth();
    $currentMonthEnd   = now()->endOfMonth();

    $propertyIds = Property::where('user_id', $userId)->pluck('id');
    $unitIds     = Unit::whereIn('property_id', $propertyIds)->pluck('id');
    $leaseIds    = Lease::whereIn('unit_id', $unitIds)->pluck('id');
    $activeLeases = Lease::whereIn('unit_id', $unitIds)
        ->where('status', 'active')
        ->with(['tenant', 'unit.property'])
        ->get();

    $rentInPeriod = fn($query) => $query
        ->where('type', 'rent')
        ->whereBetween('paid_at', [$periodStart, $periodEnd]);

    // --- Monthly revenue (cash basis) ---
    $monthlyData  = [];
    $totalRevenue = 0.0;
    for ($i = $months - 1; $i >= 0; $i--) {
        $month  = now()->startOfMonth()->subMonths($i);
        $amount = Transaction::whereIn('lease_id', $leaseIds)
            ->where('type', 'rent')
            ->whereBetween('paid_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->sum('amount');
        $monthlyData[] = [
            'month'  => $month->format('M Y'),
            'amount' => (float) $amount,
        ];
        $totalRevenue += (float) $amount;
    }

    // Accrual basis — expected rent per month
    $monthlyDataAccrual = [];
    $totalRevenueAccrual = 0.0;
    for ($i = $months - 1; $i >= 0; $i--) {
        $month    = now()->startOfMonth()->subMonths($i);
        $expected = (float) Lease::whereIn('unit_id', $unitIds)
            ->where('status', 'active')
            ->orWhere(function ($q) use ($month) {
                $q->whereIn('status', ['terminated', 'expired'])
                    ->where('end_date', '>=', $month->copy()->startOfMonth());
            })
            ->sum('rent_amount');
        $monthlyDataAccrual[] = [
            'month'  => $month->format('M Y'),
            'amount' => $expected,
        ];
        $totalRevenueAccrual += $expected;
    }

    $previousRevenue = Transaction::whereIn('lease_id', $leaseIds)
        ->where('type', 'rent')
        ->whereBetween('paid_at', [$previousStart, $previousEnd])
        ->sum('amount');

    $revenueChangePct = $previousRevenue > 0
        ? round((($totalRevenue - $previousRevenue) / $previousRevenue) * 100, 1)
        : null;

    $expectedRent  = (float) $activeLeases->sum('rent_amount');
    $collectedRent = (float) Transaction::whereIn('lease_id', $activeLeases->pluck('id'))
        ->where('type', 'rent')
        ->whereBetween('paid_at', [$currentMonthStart, $currentMonthEnd])
        ->sum('amount');

    $collectionRate = $expectedRent > 0
        ? round(($collectedRent / $expectedRent) * 100, 1) : 0;

    // Previous month collection rate for comparison
    $prevMonthStart = now()->subMonth()->startOfMonth();
    $prevMonthEnd   = now()->subMonth()->endOfMonth();
    $prevCollected  = (float) Transaction::whereIn('lease_id', $activeLeases->pluck('id'))
        ->where('type', 'rent')
        ->whereBetween('paid_at', [$prevMonthStart, $prevMonthEnd])
        ->sum('amount');
    $prevCollectionRate = $expectedRent > 0
        ? round(($prevCollected / $expectedRent) * 100, 1) : 0;
    $collectionRateChange = $collectionRate - $prevCollectionRate;

    $arrears = $activeLeases->map(function ($lease) use ($currentMonthStart, $currentMonthEnd) {
        $paid = (float) $lease->transactions()
            ->where('type', 'rent')
            ->whereBetween('paid_at', [$currentMonthStart, $currentMonthEnd])
            ->sum('amount');
        return [
            'lease'  => $lease,
            'amount' => max(0, (float) $lease->rent_amount - $paid),
        ];
    })->filter(fn($row) => $row['amount'] > 0)->values();

    $arrearsAmount = (float) $arrears->sum('amount');
    $arrearsCount  = $arrears->count();

    $totalUnits       = Unit::whereIn('property_id', $propertyIds)->count();
    $occupiedUnits    = Unit::whereIn('property_id', $propertyIds)->where('status', 'occupied')->count();
    $vacantUnits      = Unit::whereIn('property_id', $propertyIds)->where('status', 'vacant')->count();
    $maintenanceUnits = Unit::whereIn('property_id', $propertyIds)->where('status', 'maintenance')->count();
    $occupancyRate    = $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0;
    $vacancyCost      = (float) Unit::whereIn('property_id', $propertyIds)
        ->where('status', 'vacant')->sum('base_rent');

    // Vacant units detail for drill-down
    $vacantUnitsList = Unit::whereIn('property_id', $propertyIds)
        ->where('status', 'vacant')
        ->with('property')
        ->get();

    // Payment heatmap — count payments by day of month
    $heatmap = Transaction::whereIn('lease_id', $leaseIds)
        ->where('type', 'rent')
        ->whereBetween('paid_at', [$periodStart, $periodEnd])
        ->whereNotNull('paid_at')
        ->get()
        ->groupBy(fn($t) => (int) \Carbon\Carbon::parse($t->paid_at)->format('j'))
        ->map(fn($group) => $group->count())
        ->toArray();

    $maxHeatmapCount = max(1, max($heatmap ?: [1]));

    $paymentMethods = Transaction::whereIn('lease_id', $leaseIds)
        ->tap($rentInPeriod)
        ->whereNotNull('payment_method')
        ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
        ->groupBy('payment_method')
        ->orderByDesc('total')
        ->get()
        ->map(fn($row) => [
            'method' => ucfirst(str_replace('_', ' ', $row->payment_method)),
            'count'  => (int) $row->count,
            'total'  => (float) $row->total,
        ]);

    $typeBreakdown = Transaction::whereIn('lease_id', $leaseIds)
        ->whereBetween('paid_at', [$periodStart, $periodEnd])
        ->selectRaw('type, COUNT(*) as count, SUM(amount) as total')
        ->groupBy('type')
        ->orderByDesc('total')
        ->get()
        ->map(fn($row) => [
            'type'  => ucfirst($row->type),
            'count' => (int) $row->count,
            'total' => (float) $row->total,
        ]);

    $propertyRevenue = Property::where('user_id', $userId)
        ->with(['units'])
        ->get()
        ->map(function ($property) use ($periodStart, $periodEnd, $currentMonthStart, $currentMonthEnd) {
            $pUnitIds  = $property->units->pluck('id');
            $pLeaseIds = Lease::whereIn('unit_id', $pUnitIds)->pluck('id');
            $revenue   = (float) Transaction::whereIn('lease_id', $pLeaseIds)
                ->where('type', 'rent')
                ->whereBetween('paid_at', [$periodStart, $periodEnd])
                ->sum('amount');
            $expected  = (float) Lease::whereIn('unit_id', $pUnitIds)->where('status', 'active')->sum('rent_amount');
            $collected = (float) Transaction::whereIn('lease_id', $pLeaseIds)
                ->where('type', 'rent')
                ->whereBetween('paid_at', [$currentMonthStart, $currentMonthEnd])
                ->sum('amount');
            $units    = $property->units->count();
            $occupied = $property->units->where('status', 'occupied')->count();
            return [
                'name'            => $property->name,
                'revenue'         => $revenue,
                'units'           => $units,
                'occupied'        => $occupied,
                'occupancy_rate'  => $units > 0 ? round(($occupied / $units) * 100, 1) : 0,
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
        ->map(fn($payments) => [
            'name'  => $payments->first()->lease->tenant->full_name,
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
        ->avg(fn($t) => \Carbon\Carbon::parse($t->paid_at)->day);

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
        ->map(fn($row) => [
            'type'     => ucfirst(str_replace('_', ' ', $row->unit_type ?? 'apartment')),
            'count'    => (int) $row->count,
            'avg_rent' => (float) round($row->avg_rent, 0),
        ]);

    $averageLeaseMonths = Lease::whereIn('unit_id', $unitIds)
        ->whereNotNull('end_date')
        ->whereIn('status', ['active', 'terminated', 'expired'])
        ->get()
        ->avg(fn($l) => \Carbon\Carbon::parse($l->start_date)->diffInMonths($l->end_date));

    $endedLastYear  = Lease::whereIn('unit_id', $unitIds)
        ->whereIn('status', ['terminated', 'expired'])
        ->where('end_date', '>=', now()->subYear())
        ->count();
    $turnoverRate   = $activeLeases->count() > 0
        ? round(($endedLastYear / $activeLeases->count()) * 100, 1) : 0;

    // Smart insights — max 3, only show if condition is met
    $insights = [];

    if (!is_null($revenueChangePct)) {
        $dir = $revenueChangePct >= 0 ? 'up' : 'down';
        $insights[] = [
            'type'  => $revenueChangePct >= 0 ? 'positive' : 'warning',
            'icon'  => $revenueChangePct >= 0 ? '📈' : '📉',
            'text'  => 'Revenue is ' . ($revenueChangePct >= 0 ? 'up' : 'down') . ' '
                . abs($revenueChangePct) . '% compared to the previous '
                . $months . ' months.',
            'link'  => null,
        ];
    }

    if ($collectionRateChange < -5) {
        $insights[] = [
            'type'  => 'warning',
            'icon'  => '⚠️',
            'text'  => 'Collection rate dropped ' . abs($collectionRateChange)
                . '% vs last month. ' . $arrearsCount . ' '
                . ($arrearsCount === 1 ? 'tenant is' : 'tenants are')
                . ' behind on rent.',
            'link'  => route('tenants.index'),
            'label' => 'View tenants',
        ];
    } elseif ($collectionRateChange > 5) {
        $insights[] = [
            'type'  => 'positive',
            'icon'  => '✅',
            'text'  => 'Collection rate improved ' . abs($collectionRateChange)
                . '% vs last month. Great progress.',
            'link'  => null,
        ];
    }

    if ($expiringLeases->count() > 0) {
        $expiringRevenue = $expiringLeases->sum('rent_amount');
        $pctOfTotal      = $expectedRent > 0
            ? round(($expiringRevenue / $expectedRent) * 100) : 0;
        $insights[] = [
            'type'  => $pctOfTotal >= 30 ? 'danger' : 'warning',
            'icon'  => '🔔',
            'text'  => $expiringLeases->count() . ' '
                . ($expiringLeases->count() === 1 ? 'lease expires' : 'leases expire')
                . ' in the next 60 days, representing '
                . $pctOfTotal . '% of your monthly revenue.',
            'link'  => route('leases.index'),
            'label' => 'View expiring leases',
        ];
    }

    if ($vacantUnits > 0 && $expectedRent > 0) {
        $vacancyPct = round(($vacancyCost / ($expectedRent + $vacancyCost)) * 100);
        $insights[] = [
            'type'  => 'warning',
            'icon'  => '🏠',
            'text'  => $vacantUnits . ' vacant '
                . ($vacantUnits === 1 ? 'unit is' : 'units are')
                . ' costing ' . $vacancyPct . '% of potential revenue — '
                . '@money(' . $vacancyCost . ')' . '/month in lost income.',
            'link'  => null,
            'label' => null,
        ];
    }

    $insights = array_slice($insights, 0, 3);

    $chartPayload = [
        'months'         => $monthlyData,
        'monthsAccrual'  => $monthlyDataAccrual,
        'methods'        => $paymentMethods->values(),
        'types'          => $typeBreakdown->values(),
    ];

    return compact(
        'months',
        'periodStart',
        'periodEnd',
        'monthlyData',
        'monthlyDataAccrual',
        'totalRevenue',
        'totalRevenueAccrual',
        'previousRevenue',
        'revenueChangePct',
        'expectedRent',
        'collectedRent',
        'collectionRate',
        'collectionRateChange',
        'prevCollectionRate',
        'arrearsAmount',
        'arrearsCount',
        'totalUnits',
        'occupiedUnits',
        'vacantUnits',
        'maintenanceUnits',
        'occupancyRate',
        'vacancyCost',
        'vacantUnitsList',
        'heatmap',
        'maxHeatmapCount',
        'paymentMethods',
        'typeBreakdown',
        'propertyRevenue',
        'topTenants',
        'avgDaysToPay',
        'expiringLeases',
        'unitMix',
        'averageLeaseMonths',
        'turnoverRate',
        'insights',
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

$activeRevenue = $basis === 'accrual' ? $data['totalRevenueAccrual'] : $data['totalRevenue'];
$activeMonthly = $basis === 'accrual' ? $data['monthlyDataAccrual'] : $data['monthlyData'];
@endphp

<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        {{-- Header --}}
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between print:hidden">
            <div>
                <p class="font-inter text-xs font-semibold uppercase"
                    style="color:#7B8794; letter-spacing:0.08em;">Portfolio intelligence</p>
                <h1 class="font-manrope text-2xl font-semibold mt-1" style="color:#24313A;">Reports</h1>
                <p class="font-inter text-sm mt-1" style="color:#7B8794;">
                    @appdate($data['periodStart']) to @appdate($data['periodEnd'])
                </p>
            </div>
            <div class="flex flex-wrap gap-3 items-center">

                {{-- Cash / Accrual toggle --}}
                <div class="flex rounded-lg overflow-hidden"
                    style="box-shadow:0 0 0 1px #D9E1E7;">
                    <button wire:click="$set('basis', 'cash')"
                        class="font-inter text-xs font-semibold px-3 py-2 transition-colors"
                        style="background: {{ $basis === 'cash' ? '#35424D' : '#FFFFFF' }};
                           color: {{ $basis === 'cash' ? '#FFFFFF' : '#7B8794' }};">
                        Cash
                    </button>
                    <button wire:click="$set('basis', 'accrual')"
                        class="font-inter text-xs font-semibold px-3 py-2 transition-colors"
                        style="background: {{ $basis === 'accrual' ? '#35424D' : '#FFFFFF' }};
                           color: {{ $basis === 'accrual' ? '#FFFFFF' : '#7B8794' }};">
                        Accrual
                    </button>
                </div>

                <select wire:model.live="period"
                    class="font-inter text-sm px-4 py-2 rounded-lg border-0 focus:outline-none"
                    style="background:#FFFFFF; color:#24313A; box-shadow:0 0 0 1px #D9E1E7;">
                    <option value="3">Last 3 months</option>
                    <option value="6">Last 6 months</option>
                    <option value="12">Last 12 months</option>
                </select>

                <a href="{{ route('transactions.export') }}"
                    class="font-inter text-xs font-semibold px-4 py-2.5 rounded-lg transition-opacity hover:opacity-80"
                    style="background:#FFFFFF; color:#35424D; box-shadow:0 0 0 1px #D9E1E7;">
                    ↓ Export CSV
                </a>

                <button onclick="window.print()"
                    class="font-inter text-xs font-semibold text-white px-4 py-2.5 rounded-lg transition-opacity hover:opacity-90"
                    style="background:#35424D;">
                    Print
                </button>
            </div>
        </div>

        {{-- Basis label --}}
        @if($basis === 'accrual')
        <div class="px-4 py-2.5 rounded-lg font-inter text-xs"
            style="background:#FBF7F2; color:#8A5A12; box-shadow:0 0 0 1px #EDD9B0;">
            <strong>Accrual basis:</strong> showing expected rent invoiced, not cash actually received.
            Switch to Cash to see collected amounts.
        </div>
        @endif

        {{-- Smart Insights pulse row --}}
        @if(!empty($data['insights']))
        <div class="space-y-2">
            <p class="font-inter text-xs font-semibold uppercase"
                style="color:#7B8794; letter-spacing:0.08em;">Smart insights</p>
            <div class="grid grid-cols-1 md:grid-cols-{{ min(count($data['insights']), 3) }} gap-3">
                @foreach($data['insights'] as $insight)
                @php
                $bgColor = match($insight['type']) {
                'positive' => '#F0FAF4',
                'warning' => '#FBF7F2',
                'danger' => '#FDF2F2',
                default => '#F7FAFC',
                };
                $borderColor = match($insight['type']) {
                'positive' => '#A8DFC0',
                'warning' => '#EDD9B0',
                'danger' => '#F5C0BC',
                default => '#D9E1E7',
                };
                $textColor = match($insight['type']) {
                'positive' => '#1F7A4D',
                'warning' => '#8A5A12',
                'danger' => '#9F403D',
                default => '#7B8794',
                };
                @endphp
                <div class="flex items-start gap-3 px-4 py-3 rounded-lg"
                    style="background:{{ $bgColor }}; box-shadow:0 0 0 1px {{ $borderColor }};">
                    <span class="text-base leading-none mt-0.5">{{ $insight['icon'] }}</span>
                    <div class="min-w-0">
                        <p class="font-inter text-sm leading-relaxed" style="color:{{ $textColor }};">
                            {{ $insight['text'] }}
                        </p>
                        @if(!empty($insight['link']))
                        <a href="{{ $insight['link'] }}" wire:navigate
                            class="font-inter text-xs font-semibold mt-1 inline-block hover:opacity-70"
                            style="color:{{ $textColor }};">
                            {{ $insight['label'] ?? 'View' }} →
                        </a>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- KPI cards with skeleton loader + micro-interactions --}}
        <section wire:loading.remove>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">

                {{-- Collected rent --}}
                <div class="rounded-lg p-5 transition-all duration-300 hover:-translate-y-1 cursor-default"
                    style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                    <p class="font-inter text-xs font-semibold uppercase"
                        style="color:#7B8794; letter-spacing:0.06em;">
                        {{ $basis === 'accrual' ? 'Expected rent' : 'Collected rent' }}
                    </p>
                    <p class="font-manrope text-2xl font-bold mt-2" style="color:#24313A;">
                        @money($activeRevenue)
                    </p>
                    <p class="font-inter text-xs mt-2" style="color:{{ $trendColor }};">
                        @if(is_null($data['revenueChangePct']))
                        No prior period data
                        @else
                        {{ $data['revenueChangePct'] >= 0 ? '+' : '' }}{{ $data['revenueChangePct'] }}%
                        vs previous {{ $data['months'] }} months
                        @endif
                    </p>
                </div>

                {{-- Collection rate --}}
                <div class="rounded-lg p-5 transition-all duration-300 hover:-translate-y-1 cursor-default"
                    style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                    <p class="font-inter text-xs font-semibold uppercase"
                        style="color:#7B8794; letter-spacing:0.06em;">Collection rate</p>
                    <p class="font-manrope text-2xl font-bold mt-2"
                        style="color:{{ $data['collectionRate'] >= 85 ? '#1F7A4D' : ($data['collectionRate'] >= 65 ? '#8A5A12' : '#9F403D') }};">
                        {{ $data['collectionRate'] }}%
                    </p>
                    <p class="font-inter text-xs mt-2"
                        style="color:{{ $data['collectionRateChange'] >= 0 ? '#1F7A4D' : '#9F403D' }};">
                        {{ $data['collectionRateChange'] >= 0 ? '↑' : '↓' }}
                        {{ abs($data['collectionRateChange']) }}% vs last month
                    </p>
                </div>

                {{-- Arrears --}}
                <div class="rounded-lg p-5 transition-all duration-300 hover:-translate-y-1 cursor-default"
                    style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                    <p class="font-inter text-xs font-semibold uppercase"
                        style="color:#7B8794; letter-spacing:0.06em;">Open arrears</p>
                    <p class="font-manrope text-2xl font-bold mt-2" style="color:#9F403D;">
                        @money($data['arrearsAmount'])
                    </p>
                    <p class="font-inter text-xs mt-2" style="color:#7B8794;">
                        {{ $data['arrearsCount'] }} active {{ $data['arrearsCount'] === 1 ? 'lease' : 'leases' }} behind
                    </p>
                </div>

                {{-- Occupancy — clickable drill-down --}}
                <div wire:click="$toggle('show_vacant')"
                    class="rounded-lg p-5 transition-all duration-300 hover:-translate-y-1 cursor-pointer"
                    style="background:#FFFFFF; box-shadow:0 0 0 1px {{ $show_vacant ? '#35424D' : '#E5EBEF' }};">
                    <div class="flex items-start justify-between">
                        <p class="font-inter text-xs font-semibold uppercase"
                            style="color:#7B8794; letter-spacing:0.06em;">Occupancy</p>
                        <span class="font-inter text-xs px-2 py-0.5 rounded-full"
                            style="background:#E9EEF2; color:#35424D;">
                            {{ $show_vacant ? '▲ hide' : '▼ units' }}
                        </span>
                    </div>
                    <p class="font-manrope text-2xl font-bold mt-2" style="color:#24313A;">
                        {{ $data['occupancyRate'] }}%
                    </p>
                    <p class="font-inter text-xs mt-2" style="color:#7B8794;">
                        {{ $data['occupiedUnits'] }} occupied ·
                        <span style="color:{{ $data['vacantUnits'] > 0 ? '#9F403D' : '#1F7A4D' }};">
                            {{ $data['vacantUnits'] }} vacant
                        </span>
                        · {{ $data['maintenanceUnits'] }} maintenance
                    </p>
                </div>

            </div>
        </section>

        {{-- Skeleton loader while computing --}}
        <section wire:loading>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                @foreach(range(1,4) as $i)
                <div class="rounded-lg p-5 animate-pulse" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                    <div class="h-3 rounded w-24 mb-3" style="background:#E9EEF2;"></div>
                    <div class="h-8 rounded w-32 mb-2" style="background:#E9EEF2;"></div>
                    <div class="h-3 rounded w-20" style="background:#E9EEF2;"></div>
                </div>
                @endforeach
            </div>
        </section>

        {{-- Vacant units drill-down --}}
        @if($show_vacant && $data['vacantUnits'] > 0)
        <div class="rounded-lg overflow-hidden" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
            <div class="px-5 py-4 flex items-center justify-between"
                style="border-bottom:1px solid #EEF2F5;">
                <p class="font-manrope text-sm font-semibold" style="color:#24313A;">
                    Vacant Units — {{ $data['vacantUnits'] }} available
                </p>
                <p class="font-inter text-xs" style="color:#9F403D;">
                    @money($data['vacancyCost'])/month in lost revenue
                </p>
            </div>
            <div class="grid grid-cols-3 px-5 py-3 font-inter text-xs font-semibold uppercase"
                style="color:#7B8794; background:#F7FAFC; letter-spacing:0.06em;">
                <p>Unit</p>
                <p>Property</p>
                <p>Base Rent</p>
            </div>
            @foreach($data['vacantUnitsList'] as $unit)
            <div class="grid grid-cols-3 px-5 py-3 font-inter text-sm items-center"
                style="border-top:1px solid #EEF2F5; color:#24313A;">
                <p class="font-medium">Unit {{ $unit->unit_number }}</p>
                <p style="color:#7B8794;">{{ $unit->property->name }}</p>
                <div class="flex items-center justify-between">
                    <p>@money($unit->base_rent)</p>
                    <a href="{{ route('leases.create') }}?unit_id={{ $unit->id }}" wire:navigate
                        class="font-inter text-xs font-semibold px-3 py-1 rounded-md"
                        style="background:#E9EEF2; color:#35424D;">
                        + Lease
                    </a>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Revenue chart + operating signals --}}
        <section
            id="reportChartPayload"
            data-chart-payload='@json($data["chartPayload"])'
            data-basis="{{ $basis }}"
            class="grid grid-cols-1 xl:grid-cols-3 gap-6"
            wire:key="report-charts-{{ $period }}-{{ $basis }}">

            <div class="xl:col-span-2 rounded-lg p-6" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between mb-5">
                    <div>
                        <p class="font-manrope text-base font-semibold" style="color:#24313A;">
                            Monthly {{ $basis === 'accrual' ? 'expected' : 'collected' }} rent
                        </p>
                        <p class="font-inter text-xs mt-1" style="color:#7B8794;">
                            {{ $basis === 'accrual' ? 'Invoiced amounts' : 'Cash received' }}
                            over the selected window.
                        </p>
                    </div>
                    <p class="font-manrope text-lg font-bold" style="color:#24313A;">
                        @money($activeRevenue)
                    </p>
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
                            <div class="h-full rounded-full transition-all duration-700"
                                style="width:{{ min($data['collectionRate'], 100) }}%; background:#1F7A4D;">
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg p-3" style="background:#F7FAFC;">
                            <p class="font-inter text-xs" style="color:#7B8794;">Avg monthly</p>
                            <p class="font-manrope text-base font-bold mt-1" style="color:#24313A;">
                                @money($data['months'] > 0 ? $activeRevenue / $data['months'] : 0)
                            </p>
                        </div>
                        <div class="rounded-lg p-3" style="background:#F7FAFC;">
                            <p class="font-inter text-xs" style="color:#7B8794;">Avg pay day</p>
                            <p class="font-manrope text-base font-bold mt-1" style="color:#24313A;">
                                Day {{ round($data['avgDaysToPay'] ?? 0) }}
                            </p>
                        </div>
                    </div>

                    <div class="rounded-lg p-4" style="background:#FBF7F2;">
                        <p class="font-inter text-xs font-semibold uppercase"
                            style="color:#8A5A12; letter-spacing:0.06em;">Lease risk</p>
                        <p class="font-manrope text-xl font-bold mt-2" style="color:#24313A;">
                            {{ $data['expiringLeases']->count() }} expiring
                        </p>
                        <p class="font-inter text-xs mt-1" style="color:#7B8794;">Next 60 days.</p>
                    </div>

                    <div class="rounded-lg p-4" style="background:#F7FAFC;">
                        <p class="font-inter text-xs" style="color:#7B8794;">Average lease term</p>
                        <p class="font-manrope text-xl font-bold mt-1" style="color:#24313A;">
                            {{ round($data['averageLeaseMonths'] ?? 0, 1) }} months
                        </p>
                        <p class="font-inter text-xs mt-1" style="color:#7B8794;">
                            Turnover {{ $data['turnoverRate'] }}%
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Payment heatmap --}}
        <section class="rounded-lg p-6" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
            <div class="flex items-start justify-between mb-5">
                <div>
                    <p class="font-manrope text-base font-semibold" style="color:#24313A;">
                        Payment heatmap
                    </p>
                    <p class="font-inter text-xs mt-1" style="color:#7B8794;">
                        Which days of the month tenants actually pay.
                        Darker = more payments on that day.
                    </p>
                </div>
                @if($data['avgDaysToPay'])
                <div class="text-right">
                    <p class="font-inter text-xs" style="color:#7B8794;">Peak payment day</p>
                    <p class="font-manrope text-lg font-bold" style="color:#24313A;">
                        Day {{ round($data['avgDaysToPay']) }}
                    </p>
                </div>
                @endif
            </div>

            <div class="grid gap-2" style="grid-template-columns: repeat(31, minmax(0, 1fr));">
                @for($day = 1; $day <= 31; $day++)
                    @php
                    $count=$data['heatmap'][$day] ?? 0;
                    $intensity=$data['maxHeatmapCount']> 0
                    ? $count / $data['maxHeatmapCount'] : 0;
                    $alpha = $count > 0
                    ? max(0.22, $intensity)
                    : 0.06;
                    $isAvg = round($data['avgDaysToPay'] ?? 0) === $day;
                    @endphp
                    <div class="flex flex-col items-center gap-1">
                        <div class="w-full rounded"
                            style="height:44px;
                           background: rgba(53, 66, 77, {{ $alpha }});
                           border: 1px solid rgba(53, 66, 77, 0.08);
                           transition:all ..15s ease;
                           {{ $isAvg ? 'box-shadow:0 0 0 2px #35424D;' : '' }}"
                            title="Day {{ $day }}: {{ $count }} payment{{ $count !== 1 ? 's' : '' }}">
                        </div>
                        <p class="font-inter text-center"
                            style="font-size:9px; color:{{ $isAvg ? '#24313A' : '#9BABB3' }};
                           font-weight:{{ $isAvg ? '700' : '400' }};">
                            {{ $day }}
                        </p>
                    </div>
                    @endfor
            </div>

            <div class="flex items-center gap-3 mt-4">
                <p class="font-inter text-xs" style="color:#7B8794;">Less</p>
                @foreach([0.08, 0.25, 0.5, 0.75, 1.0] as $a)
                <div class="w-5 h-3 rounded"
                    style="background: rgba(53, 66, 77, {{ $a }});"></div>
                @endforeach
                <p class="font-inter text-xs" style="color:#7B8794;">More</p>
            </div>
        </section>

        {{-- Payment methods + transaction mix --}}
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
                            <p class="font-inter text-xs mt-1" style="color:#7B8794;">
                                {{ $method['count'] }} payments · @money($method['total'])
                            </p>
                        </div>
                        @empty
                        <p class="font-inter text-sm" style="color:#7B8794;">No payment method data.</p>
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
                            <p class="font-manrope text-sm font-bold" style="color:#24313A;">
                                @money($type['total'])
                            </p>
                        </div>
                        <div class="h-2 rounded-full overflow-hidden" style="background:#E9EEF2;">
                            <div class="h-full rounded-full"
                                style="width:{{ $pct }}%; background:{{ $color }};"></div>
                        </div>
                        <p class="font-inter text-xs mt-1" style="color:#7B8794;">
                            {{ $type['count'] }} transactions · {{ $pct }}% of period value
                        </p>
                    </div>
                    @empty
                    <p class="font-inter text-sm" style="color:#7B8794;">No transactions in this period.</p>
                    @endforelse
                </div>
            </div>
        </section>

        {{-- Property performance table --}}
        <section class="rounded-lg overflow-hidden" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
            <div class="px-6 py-5 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between"
                style="border-bottom:1px solid #E5EBEF;">
                <div>
                    <p class="font-manrope text-base font-semibold" style="color:#24313A;">
                        Property performance
                    </p>
                    <p class="font-inter text-xs mt-1" style="color:#7B8794;">
                        Ranked by rent collected in the selected period.
                    </p>
                </div>
                <p class="font-inter text-xs" style="color:#7B8794;">
                    {{ $data['propertyRevenue']->count() }} properties
                </p>
            </div>

            <div class="hidden md:grid grid-cols-12 px-6 py-3 font-inter text-xs font-semibold uppercase"
                style="color:#7B8794; background:#F7FAFC; letter-spacing:0.06em;">
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
                        <p class="font-inter text-sm font-semibold" style="color:#24313A;">
                            {{ $property['name'] }}
                        </p>
                        <div class="h-1.5 rounded-full overflow-hidden mt-2" style="background:#E9EEF2;">
                            <div class="h-full rounded-full"
                                style="width:{{ $width }}%; background:#35424D;"></div>
                        </div>
                    </div>
                    <p class="font-manrope text-sm font-bold md:col-span-2" style="color:#24313A;">
                        @money($property['revenue'])
                    </p>
                    <p class="font-inter text-sm md:col-span-2"
                        style="color:{{ $property['collection_rate'] >= 85 ? '#1F7A4D' : ($property['collection_rate'] >= 65 ? '#8A5A12' : '#9F403D') }};">
                        {{ $property['collection_rate'] }}%
                    </p>
                    <p class="font-inter text-sm md:col-span-2" style="color:#24313A;">
                        {{ $property['occupancy_rate'] }}%
                    </p>
                    <p class="font-inter text-sm md:col-span-2" style="color:#7B8794;">
                        {{ $property['occupied'] }}/{{ $property['units'] }}
                    </p>
                </div>
            </div>
            @empty
            <div class="px-6 py-8">
                <p class="font-inter text-sm" style="color:#7B8794;">No property revenue data yet.</p>
            </div>
            @endforelse
        </section>

        {{-- Bottom row: top tenants + lease expiries + unit mix --}}
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="rounded-lg p-6" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                <p class="font-manrope text-base font-semibold" style="color:#24313A;">Top tenants</p>
                <div class="mt-4 space-y-4">
                    @forelse($data['topTenants'] as $index => $tenant)
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="font-manrope text-xs font-bold w-6 h-6 rounded-full
                            flex items-center justify-center shrink-0"
                                style="background:#E9EEF2; color:#35424D;">
                                {{ $index + 1 }}
                            </span>
                            <div class="min-w-0">
                                <p class="font-inter text-sm truncate" style="color:#24313A;">
                                    {{ $tenant['name'] }}
                                </p>
                                <p class="font-inter text-xs" style="color:#7B8794;">
                                    {{ $tenant['count'] }} payments
                                </p>
                            </div>
                        </div>
                        <p class="font-manrope text-sm font-bold shrink-0" style="color:#24313A;">
                            @money($tenant['total'])
                        </p>
                    </div>
                    @empty
                    <p class="font-inter text-sm" style="color:#7B8794;">No tenant data.</p>
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
                            <p class="font-inter text-sm truncate" style="color:#24313A;">
                                {{ $lease->tenant->full_name }}
                            </p>
                            <p class="font-inter text-xs truncate" style="color:#7B8794;">
                                {{ $lease->unit->property->name }} / Unit {{ $lease->unit->unit_number }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="font-inter text-xs font-semibold px-2.5 py-1 rounded-full"
                                style="background:{{ $daysLeft <= 14 ? '#FDECEA' : '#E9EEF2' }};
                                   color:{{ $daysLeft <= 14 ? '#9F403D' : '#35424D' }};">
                                {{ $daysLeft }}d
                            </span>
                            <a href="{{ route('leases.renew', $lease) }}" wire:navigate
                                class="font-inter text-xs font-semibold px-2.5 py-1 rounded-full"
                                style="background:#E9EEF2; color:#35424D;">
                                Renew
                            </a>
                        </div>
                    </div>
                    @empty
                    <p class="font-inter text-sm" style="color:#7B8794;">
                        No leases expiring in 60 days.
                    </p>
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
                            <p class="font-inter text-sm" style="color:#24313A;">
                                {{ $unit['count'] }} · {{ $pct }}%
                            </p>
                        </div>
                        <div class="h-2 rounded-full overflow-hidden" style="background:#E9EEF2;">
                            <div class="h-full rounded-full"
                                style="width:{{ $pct }}%; background:#60717D;"></div>
                        </div>
                        <p class="font-inter text-xs mt-1" style="color:#7B8794;">
                            Avg @money($unit['avg_rent'])/mo
                        </p>
                    </div>
                    @empty
                    <p class="font-inter text-sm" style="color:#7B8794;">No unit data.</p>
                    @endforelse
                </div>
            </div>

        </section>

    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        function estateFlowReportPayload() {
            const source = document.getElementById('reportChartPayload');
            if (!source) return {
                months: [],
                methods: [],
                types: []
            };
            try {
                return JSON.parse(source.dataset.chartPayload || '{}');
            } catch (e) {
                return {
                    months: [],
                    methods: [],
                    types: []
                };
            }
        }

        function estateFlowMoney(value) {
            return 'KES ' + Number(value || 0).toLocaleString();
        }

        function renderEstateFlowReportCharts() {
            if (!window.Chart) return;

            const payload = estateFlowReportPayload();
            const source = document.getElementById('reportChartPayload');
            const basis = source ? source.dataset.basis : 'cash';
            const months = basis === 'accrual' ?
                (payload.monthsAccrual || []) :
                (payload.months || []);

            const revenueCtx = document.getElementById('revenueChart');
            const methodCtx = document.getElementById('methodChart');

            if (revenueCtx) {
                if (window.estateFlowRevenueChart) window.estateFlowRevenueChart.destroy();

                const maxValue = Math.max(0, ...months.map(item => Number(item.amount || 0)));

                window.estateFlowRevenueChart = new Chart(revenueCtx, {
                    type: 'bar',
                    data: {
                        labels: months.map(item => item.month),
                        datasets: [{
                            data: months.map(item => item.amount),
                            backgroundColor: months.map(item =>
                                Number(item.amount || 0) === maxValue ? '#35424D' : '#B7C4CC'
                            ),
                            borderRadius: 6,
                            borderSkipped: false,
                            maxBarThickness: 46,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: ctx => estateFlowMoney(ctx.raw),
                                },
                            },
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: '#7B8794',
                                    font: {
                                        family: 'Inter',
                                        size: 11
                                    }
                                },
                                border: {
                                    display: false
                                },
                            },
                            y: {
                                grid: {
                                    color: '#EEF2F5'
                                },
                                ticks: {
                                    color: '#7B8794',
                                    font: {
                                        family: 'Inter',
                                        size: 11
                                    },
                                    callback: value =>
                                        'KES ' + (Number(value) / 1000).toFixed(0) + 'k',
                                },
                                border: {
                                    display: false
                                },
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
                        labels: methods.map(item => item.method),
                        datasets: [{
                            data: methods.map(item => item.total),
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
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: ctx => ctx.label + ': ' + estateFlowMoney(ctx.raw),
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
            Livewire.hook('commit', ({
                succeed
            }) => {
                succeed(() => requestAnimationFrame(renderEstateFlowReportCharts));
            });
        }
        hookEstateFlowReportCharts();
        document.addEventListener('livewire:init', hookEstateFlowReportCharts);
    </script>

    <style>
        @media print {

            nav,
            select,
            button,
            .print\:hidden {
                display: none !important;
            }

            body {
                background: white !important;
            }

            .rounded-lg {
                box-shadow: none !important;
                border: 1px solid #E5EBEF;
                break-inside: avoid;
            }

            @page {
                margin: 1.4cm;
            }
        }
    </style>
</div>