@php use Illuminate\Support\Str; @endphp

<x-app-layout>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Sandbox banner --}}
    @php $prefs = session('estateflow_prefs', []); @endphp
    @if($prefs['show_sandbox_banner'] ?? true)
    <div class="mb-6 px-4 py-3 rounded-md font-inter text-xs font-medium flex items-center justify-between"
        style="background-color:#FEF3C7; color:#92400E;">
        <span>⚠ Sandbox mode — this is demo data. Go to Settings to reset or disable this banner.</span>
        <a href="{{ route('settings') }}" wire:navigate class="underline">Settings</a>
    </div>
    @endif

    {{-- Welcome line --}}
    <div class="mb-8">
        <h1 class="font-manrope text-2xl font-semibold" style="color:#283439;">
            Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }},
            {{ explode(' ', auth()->user()->name)[0] }}
        </h1>
        <p class="font-inter text-sm mt-1" style="color:#9BABB3;">
            {{ now()->format('l, d F Y') }} · Here's your portfolio at a glance.
        </p>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">

        <div class="rounded-xl p-6" style="background-color:#FFFFFF;">
            <p class="font-inter text-xs font-medium uppercase tracking-widest"
                style="color:#9BABB3; letter-spacing:0.08em;">Total Rent Collected This Month</p>
            <div class="mt-4 w-10 h-10 rounded-full flex items-center justify-center"
                style="background-color:#E7EFF3;">
                <svg class="w-5 h-5" style="color:#585E6C;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <p class="font-manrope mt-3 font-bold" style="font-size:2rem; color:#22863a;">
                KES {{ number_format($monthlyCollected, 0) }}
            </p>
            <div class="mt-3 rounded-full overflow-hidden" style="background-color:#EFF4F7; height:4px;">
                @php $collectionPct = $pendingArrears > 0 ? min(100, round(($monthlyCollected / ($monthlyCollected + $pendingArrears)) * 100)) : 100; @endphp
                <div class="h-full rounded-full" style="width:{{ $collectionPct }}%; background-color:#22863a;"></div>
            </div>
            <p class="font-inter text-xs mt-1" style="color:#9BABB3;">
                {{ $collectionPct }}% of expected rent collected
            </p>
        </div>

        <div class="rounded-xl p-6" style="background-color:#FFFFFF;">
            <p class="font-inter text-xs font-medium uppercase tracking-widest"
                style="color:#9BABB3; letter-spacing:0.08em;">Pending Arrears</p>
            <div class="mt-4 w-10 h-10 rounded-full flex items-center justify-center"
                style="background-color:#FDECEA;">
                <svg class="w-5 h-5" style="color:#9F403D;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <p class="font-manrope mt-3 font-bold" style="font-size:2rem; color:#9F403D;">
                KES {{ number_format($pendingArrears, 0) }}
            </p>
            <p class="font-inter text-xs mt-2" style="color:#9BABB3;">
                {{ $priorityArrears->count() }} tenant{{ $priorityArrears->count() !== 1 ? 's' : '' }} overdue
            </p>
        </div>

        <div class="rounded-xl p-6" style="background-color:#FFFFFF;">
            <p class="font-inter text-xs font-medium uppercase tracking-widest"
                style="color:#9BABB3; letter-spacing:0.08em;">Occupancy Rate</p>
            <div class="mt-4 w-10 h-10 rounded-full flex items-center justify-center"
                style="background-color:#E7EFF3;">
                <svg class="w-5 h-5" style="color:#585E6C;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <p class="font-manrope mt-3 font-bold" style="font-size:2rem; color:#283439;">
                {{ $occupancyRate }}%
            </p>
            <div class="mt-3 rounded-full overflow-hidden" style="background-color:#EFF4F7; height:4px;">
                <div class="h-full rounded-full"
                    style="width:{{ $occupancyRate }}%;
                           background: linear-gradient(135deg, #585E6C, #4C5260);">
                </div>
            </div>
            <p class="font-inter text-xs mt-1" style="color:#9BABB3;">
                {{ $occupiedUnits }} of {{ $totalUnits }} units occupied
            </p>
        </div>

    </div>

    {{-- Vacancy cost tracker (game-changing feature #1) --}}
    @if($vacancyCost > 0)
    <div class="rounded-xl p-5 mb-6 flex items-center justify-between"
        style="background-color:#FDECEA;">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full flex items-center justify-center"
                style="background-color:#F8D5D4;">
                <svg class="w-5 h-5" style="color:#9F403D;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                </svg>
            </div>
            <div>
                <p class="font-inter text-sm font-semibold" style="color:#9F403D;">
                    Vacancy is costing you KES {{ number_format($vacancyCost, 0) }}/day
                </p>
                <p class="font-inter text-xs mt-0.5" style="color:#9F403D; opacity:0.8;">
                    {{ $vacantCount }} vacant unit{{ $vacantCount !== 1 ? 's' : '' }} ·
                    KES {{ number_format($vacancyCost * 30, 0) }} lost this month if unfilled
                </p>
            </div>
        </div>
        <a href="{{ route('units.index') }}" wire:navigate
            class="font-inter text-xs font-semibold px-4 py-2 rounded-md transition-opacity hover:opacity-90"
            style="background-color:#9F403D; color:#FFFFFF;">
            View Vacant Units
        </a>
    </div>
    @endif

    {{-- Quick actions --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-8">
        <a href="{{ route('properties.create') }}" wire:navigate
            class="flex items-center gap-2 px-4 py-3 rounded-xl font-inter text-sm font-medium
                transition-opacity hover:opacity-80"
            style="background-color:#FFFFFF; color:#283439;">
            <span style="color:#585E6C;">+</span> Add Property
        </a>
        <a href="{{ route('tenants.create') }}" wire:navigate
            class="flex items-center gap-2 px-4 py-3 rounded-xl font-inter text-sm font-medium
                transition-opacity hover:opacity-80"
            style="background-color:#FFFFFF; color:#283439;">
            <span style="color:#585E6C;">+</span> Add Tenant
        </a>
        <a href="{{ route('leases.create') }}" wire:navigate
            class="flex items-center gap-2 px-4 py-3 rounded-xl font-inter text-sm font-medium
                transition-opacity hover:opacity-80"
            style="background-color:#FFFFFF; color:#283439;">
            <span style="color:#585E6C;">+</span> New Lease
        </a>
        <a href="{{ route('transactions.create') }}" wire:navigate
            class="flex items-center gap-2 px-4 py-3 rounded-xl font-inter text-sm font-medium
                transition-opacity hover:opacity-80"
            style="background-color:#FFFFFF; color:#283439;">
            <span style="color:#585E6C;">+</span> Record Payment
        </a>
    </div>

    {{-- Priority Arrears --}}
    <div class="rounded-xl mb-6" style="background-color:#FFFFFF;">
        <div class="flex items-center justify-between px-6 py-5">
            <div>
                <p class="font-manrope text-base font-semibold" style="color:#283439;">Priority Arrears</p>
                <p class="font-inter text-xs mt-0.5 uppercase tracking-widest"
                    style="color:#9BABB3; letter-spacing:0.08em;">Top 5 overdue</p>
            </div>
            <a href="{{ route('transactions.index') }}" wire:navigate
                class="font-inter text-xs font-medium px-3 py-1.5 rounded-md"
                style="background-color:#E7EFF3; color:#585E6C;">
                All Transactions →
            </a>
        </div>

        @forelse($priorityArrears as $arrear)
        <div class="flex items-center justify-between px-6 py-4 transition-colors"
            onmouseenter="this.style.backgroundColor='#EFF4F7'"
            onmouseleave="this.style.backgroundColor='transparent'">

            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center
                    font-manrope text-sm font-bold"
                    style="background-color:#E7EFF3; color:#585E6C;">
                    {{ strtoupper(substr($arrear['name'], 0, 1)) }}{{ strtoupper(substr(strstr($arrear['name'], ' '), 1, 1)) }}
                </div>
                <div>
                    <p class="font-inter text-sm font-medium" style="color:#283439;">
                        {{ $arrear['name'] }}
                    </p>
                    <p class="font-inter text-xs" style="color:#9BABB3;">Unit {{ $arrear['unit'] }}</p>
                </div>
            </div>

            <div class="flex items-center gap-6">
                <div class="text-right">
                    <p class="font-manrope text-sm font-bold" style="color:#9F403D;">
                        KES {{ number_format($arrear['amount'], 0) }}
                    </p>
                    <p class="font-inter text-xs" style="color:#9BABB3;">
                        {{ $arrear['days_overdue'] }} {{ Str::plural('day', $arrear['days_overdue']) }} overdue
                    </p>
                </div>

                {{-- Reliability score badge (game-changing feature #2) --}}
                <div class="text-center">
                    <p class="font-inter text-xs uppercase tracking-widest" style="color:#9BABB3;">Score</p>
                    @php
                        $score = $arrear['reliability_score'];
                        $scoreColor = $score >= 80 ? '#22863a' : ($score >= 50 ? '#92400E' : '#9F403D');
                        $scoreBg = $score >= 80 ? '#E7EFF3' : ($score >= 50 ? '#FEF3C7' : '#FDECEA');
                    @endphp
                    <span class="font-manrope text-xs font-bold px-2.5 py-1 rounded-full"
                        style="background-color:{{ $scoreBg }}; color:{{ $scoreColor }};">
                        {{ $score }}
                    </span>
                </div>

                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $arrear['phone']) }}?text={{ urlencode('Dear ' . $arrear['name'] . ', this is a friendly reminder that your rent of KES ' . number_format($arrear['amount'], 0) . ' is overdue. Please arrange payment at your earliest convenience. Thank you. - EstateFlow') }}"
                    target="_blank"
                    class="inline-flex items-center gap-2 font-inter text-xs font-medium
                        px-3 py-2 rounded-md transition-opacity hover:opacity-80"
                    style="background-color:#E7EFF3; color:#585E6C;">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    WhatsApp
                </a>
            </div>
        </div>
        @empty
        <div class="px-6 py-12 text-center">
            <p class="font-manrope text-base font-semibold mb-1" style="color:#283439;">
                All tenants are up to date 🎉
            </p>
            <p class="font-inter text-sm" style="color:#9BABB3;">
                No overdue rent this month.
            </p>
        </div>
        @endforelse
    </div>

    {{-- Recent transactions quick view --}}
    <div class="rounded-xl" style="background-color:#FFFFFF;">
        <div class="flex items-center justify-between px-6 py-5">
            <p class="font-manrope text-base font-semibold" style="color:#283439;">
                Recent Payments
            </p>
            <a href="{{ route('transactions.index') }}" wire:navigate
                class="font-inter text-xs font-medium" style="color:#9BABB3;">
                View all →
            </a>
        </div>
        @foreach($recentTransactions as $txn)
        <div class="flex items-center justify-between px-6 py-3 transition-colors"
            onmouseenter="this.style.backgroundColor='#EFF4F7'"
            onmouseleave="this.style.backgroundColor='transparent'">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center
                    font-manrope text-xs font-bold"
                    style="background-color:#E7EFF3; color:#585E6C;">
                    {{ strtoupper(substr($txn['tenant'], 0, 1)) }}{{ strtoupper(substr(strstr($txn['tenant'], ' '), 1, 1)) }}
                </div>
                <div>
                    <p class="font-inter text-sm" style="color:#283439;">{{ $txn['tenant'] }}</p>
                    <p class="font-inter text-xs" style="color:#9BABB3;">
                        {{ $txn['reference'] }} · {{ $txn['method'] }}
                    </p>
                </div>
            </div>
            <div class="text-right">
                <p class="font-manrope text-sm font-bold" style="color:#283439;">
                    KES {{ number_format($txn['amount'], 0) }}
                </p>
                <p class="font-inter text-xs" style="color:#9BABB3;">{{ $txn['date'] }}</p>
            </div>
        </div>
        @endforeach
    </div>

</div>
</x-app-layout>