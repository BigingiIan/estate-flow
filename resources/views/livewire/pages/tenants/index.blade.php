<?php

use function Livewire\Volt\{state, computed, uses};
use App\Models\Tenant;
use App\Models\Lease;
use Livewire\WithPagination;

uses(WithPagination::class);

state(['search' => '']);

$tenants = computed(function () {
    return Tenant::whereHas('leases.unit.property', function ($query) {
            $query->where('user_id', auth()->id());
        })
        ->with([
            'leases' => fn($q) => $q->where('status', 'active')
                ->with(['unit', 'transactions' => fn($q) => $q
                    ->where('type', 'rent')
                    ->whereMonth('paid_at', now()->month)
                    ->whereYear('paid_at', now()->year)
                ])
        ])
        ->when($this->search, fn($q) => $q
            ->where('full_name', 'like', "%{$this->search}%")
            ->orWhere('phone', 'like', "%{$this->search}%"))
        ->latest()
        ->paginate(15);
});

$summary = computed(function () {
    $userId = auth()->id();

    $total = Tenant::whereHas('leases.unit.property', function ($q) use ($userId) {
        $q->where('user_id', $userId);
    })->count();

    $activeLeases = Lease::whereHas('unit.property', function ($q) use ($userId) {
        $q->where('user_id', $userId);
    })->where('status', 'active')->count();

    $renewalsDue = Lease::whereHas('unit.property', function ($q) use ($userId) {
        $q->where('user_id', $userId);
    })->where('status', 'active')
        ->whereNotNull('end_date')
        ->whereBetween('end_date', [now(), now()->addDays(30)])
        ->count();

    return compact('total', 'activeLeases', 'renewalsDue');
});

?>

<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        {{-- Page Header --}}
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="font-inter text-xs font-semibold uppercase" style="color:#7B8794; letter-spacing:0.08em;">
                    Tenant management
                </p>
                <h1 class="font-manrope text-2xl font-semibold mt-1" style="color:#24313A;">
                    Tenants Directory
                </h1>
                <p class="font-inter text-sm mt-1" style="color:#7B8794;">
                    Manage and track residency status across your portfolio.
                </p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2" style="color:#7B8794;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input wire:model.live="search" type="text" placeholder="Search tenants..."
                        class="font-inter text-sm pl-10 pr-4 py-2.5 rounded-lg border-0 focus:outline-none focus:ring-2 w-full sm:w-64"
                        style="background-color:#FFFFFF; color:#24313A; box-shadow:0 0 0 1px #D9E1E7;" />
                </div>
                <a href="{{ route('tenants.create') }}" wire:navigate
                    class="inline-flex items-center justify-center gap-2 font-inter text-xs font-semibold
                        text-white px-5 py-2.5 rounded-lg transition-opacity hover:opacity-90"
                    style="background-color:#35424D;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Tenant
                </a>
            </div>
        </div>

        {{-- KPI Cards --}}
        <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-lg p-5" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                <div class="flex items-center justify-between">
                    <p class="font-inter text-xs font-semibold uppercase" style="color:#7B8794; letter-spacing:0.06em;">Total Tenants</p>
                    <div class="w-9 h-9 rounded-full flex items-center justify-center" style="background-color:#E9EEF2;">
                        <svg class="w-4 h-4" style="color:#35424D;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="font-manrope text-2xl font-bold mt-3" style="color:#24313A;">{{ $this->summary['total'] }}</p>
                <p class="font-inter text-xs mt-2" style="color:#7B8794;">Across all your properties</p>
            </div>

            <div class="rounded-lg p-5" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                <div class="flex items-center justify-between">
                    <p class="font-inter text-xs font-semibold uppercase" style="color:#7B8794; letter-spacing:0.06em;">Active Leases</p>
                    <div class="w-9 h-9 rounded-full flex items-center justify-center" style="background-color:#E9EEF2;">
                        <svg class="w-4 h-4" style="color:#35424D;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
                <p class="font-manrope text-2xl font-bold mt-3" style="color:#24313A;">{{ $this->summary['activeLeases'] }}</p>
                <div class="mt-2 h-1.5 rounded-full overflow-hidden" style="background:#E9EEF2;">
                    @php $leasePct = $this->summary['total'] > 0 ? min(100, round(($this->summary['activeLeases'] / $this->summary['total']) * 100)) : 0; @endphp
                    <div class="h-full rounded-full" style="width:{{ $leasePct }}%; background:#35424D;"></div>
                </div>
                <p class="font-inter text-xs mt-1.5" style="color:#7B8794;">{{ $leasePct }}% of tenants have active leases</p>
            </div>

            <div class="rounded-lg p-5" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
                <div class="flex items-center justify-between">
                    <p class="font-inter text-xs font-semibold uppercase" style="color:#7B8794; letter-spacing:0.06em;">Renewals Due</p>
                    <div class="w-9 h-9 rounded-full flex items-center justify-center"
                        style="background-color:{{ $this->summary['renewalsDue'] > 0 ? '#FBF7F2' : '#E9EEF2' }};">
                        <svg class="w-4 h-4" style="color:{{ $this->summary['renewalsDue'] > 0 ? '#8A5A12' : '#35424D' }};" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="font-manrope text-2xl font-bold mt-3"
                    style="color:{{ $this->summary['renewalsDue'] > 0 ? '#8A5A12' : '#24313A' }};">
                    {{ $this->summary['renewalsDue'] }}
                </p>
                <p class="font-inter text-xs mt-2" style="color:#7B8794;">Leases expiring within 30 days</p>
            </div>
        </section>

        {{-- Tenants Table --}}
        <section class="rounded-lg overflow-hidden" style="background:#FFFFFF; box-shadow:0 0 0 1px #E5EBEF;">
            <div class="px-6 py-5 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between" style="border-bottom:1px solid #E5EBEF;">
                <div>
                    <p class="font-manrope text-base font-semibold" style="color:#24313A;">All Tenants</p>
                    <p class="font-inter text-xs mt-1" style="color:#7B8794;">Rent status reflects current month payments.</p>
                </div>
                <p class="font-inter text-xs" style="color:#7B8794;">{{ $this->tenants->total() }} tenants</p>
            </div>

            {{-- Table Header --}}
            <div class="hidden md:grid grid-cols-12 px-6 py-3 font-inter text-xs font-semibold uppercase" style="color:#7B8794; background:#F7FAFC; letter-spacing:0.06em;">
                <p class="col-span-4">Tenant</p>
                <p class="col-span-2">Unit</p>
                <p class="col-span-2">Rent Status</p>
                <p class="col-span-4 text-right">Actions</p>
            </div>

            @forelse($this->tenants as $tenant)
            @php $activeLease = $tenant->leases->first(); @endphp
            <div class="px-6 py-4 transition-colors" style="border-top:1px solid #EFF3F6;"
                onmouseenter="this.style.backgroundColor='#F7FAFC'"
                onmouseleave="this.style.backgroundColor='transparent'">

                <div class="grid grid-cols-1 md:grid-cols-12 gap-3 md:items-center">

                    {{-- Tenant Info --}}
                    <div class="md:col-span-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-manrope text-sm font-bold shrink-0"
                            style="background-color:#E9EEF2; color:#35424D;">
                            {{ strtoupper(substr($tenant->full_name, 0, 1)) }}{{ strtoupper(substr(strstr($tenant->full_name, ' '), 1, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-inter text-sm font-semibold truncate" style="color:#24313A;">{{ $tenant->full_name }}</p>
                            <p class="font-inter text-xs mt-0.5 truncate" style="color:#7B8794;">{{ $tenant->phone }}</p>
                        </div>
                    </div>

                    {{-- Unit --}}
                    <div class="md:col-span-2">
                        <p class="font-inter text-xs md:hidden mb-0.5" style="color:#7B8794;">Unit</p>
                        @if($activeLease)
                            <p class="font-inter text-sm font-semibold" style="color:#24313A;">{{ $activeLease->unit->unit_number }}</p>
                            <p class="font-inter text-xs mt-0.5 truncate" style="color:#7B8794;">@money($activeLease->rent_amount)/mo</p>
                        @else
                            <span class="font-inter text-xs" style="color:#7B8794;">No active lease</span>
                        @endif
                    </div>

                    {{-- Rent Status --}}
                    <div class="md:col-span-2">
                        <p class="font-inter text-xs md:hidden mb-0.5" style="color:#7B8794;">Rent Status</p>
                        @if($activeLease)
                            @php $paid = $activeLease->transactions->isNotEmpty(); @endphp
                            <span class="font-inter text-xs font-semibold px-2.5 py-1 rounded-full"
                                style="background-color:{{ $paid ? '#E9EEF2' : '#FDECEA' }}; color:{{ $paid ? '#35424D' : '#9F403D' }};">
                                {{ $paid ? 'Paid' : 'Pending' }}
                            </span>
                        @else
                            <span class="font-inter text-xs" style="color:#7B8794;">—</span>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="md:col-span-4 flex items-center gap-2 md:justify-end flex-wrap">
                        <a href="{{ route('tenants.show', $tenant) }}" wire:navigate
                            class="font-inter text-xs font-semibold text-white px-4 py-2 rounded-lg transition-opacity hover:opacity-90"
                            style="background-color:#35424D;">
                            Details
                        </a>
                        <a href="{{ route('tenants.edit', $tenant) }}" wire:navigate
                            class="font-inter text-xs font-medium px-3 py-2 rounded-lg transition-opacity hover:opacity-80"
                            style="background-color:#F7FAFC; color:#60717D; box-shadow:0 0 0 1px #E5EBEF;">
                            Edit
                        </a>
                        @if($activeLease)
                        <a href="{{ route('transactions.create', ['lease_id' => $activeLease->id]) }}" wire:navigate
                            class="font-inter text-xs font-medium px-3 py-2 rounded-lg transition-opacity hover:opacity-80"
                            style="background-color:#F7FAFC; color:#60717D; box-shadow:0 0 0 1px #E5EBEF;">
                            + Payment
                        </a>
                        @if(!$activeLease->transactions->isNotEmpty())
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $tenant->phone) }}?text={{ urlencode('Dear ' . $tenant->full_name . ', this is a friendly reminder that your rent of KES ' . number_format($activeLease->rent_amount, 0) . ' is overdue. Please arrange payment at your earliest convenience. Thank you. - EstateFlow') }}"
                            target="_blank" rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 font-inter text-xs font-medium px-3 py-2 rounded-lg transition-opacity hover:opacity-80"
                            style="background-color:#E9EEF2; color:#35424D;">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            WhatsApp
                        </a>
                        @endif
                        @endif
                    </div>

                </div>
            </div>
            @empty
            <div class="px-6 py-16 text-center">
                <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4"
                    style="background-color:#E9EEF2;">
                    <svg class="w-7 h-7" style="color:#60717D;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <p class="font-manrope text-base font-semibold mb-1" style="color:#24313A;">No tenants yet</p>
                <p class="font-inter text-sm mb-4" style="color:#7B8794;">
                    Add your first tenant to get started.
                </p>
                <a href="{{ route('tenants.create') }}" wire:navigate
                    class="inline-flex items-center gap-2 font-inter text-xs font-semibold text-white
                        px-5 py-2.5 rounded-lg transition-opacity hover:opacity-90"
                    style="background-color:#35424D;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Tenant
                </a>
            </div>
            @endforelse
        </section>

        {{-- Pagination --}}
        @if($this->tenants->hasPages())
        <div class="px-2 py-4">
            {{ $this->tenants->links() }}
        </div>
        @endif

    </div>
</div>