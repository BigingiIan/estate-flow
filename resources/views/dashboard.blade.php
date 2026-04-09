@php use Illuminate\Support\Str; @endphp

<x-app-layout>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-10">

        {{-- Rent Collected --}}
        <div class="rounded-xl p-6" style="background-color:#FFFFFF;">
            <p class="font-inter text-xs font-medium uppercase tracking-widest"
                style="color:#9BABB3; letter-spacing:0.08em;">
                Total Rent Collected This Month
            </p>
            <div class="mt-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center"
                    style="background-color:#E7EFF3;">
                    <svg class="w-5 h-5" style="color:#585E6C;"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
            <p class="font-manrope mt-3 font-bold" style="font-size:2rem; color:#22863a;">
                KES {{ number_format($monthlyCollected, 0) }}
            </p>
        </div>

        {{-- Pending Arrears --}}
        <div class="rounded-xl p-6" style="background-color:#FFFFFF;">
            <p class="font-inter text-xs font-medium uppercase tracking-widest"
                style="color:#9BABB3; letter-spacing:0.08em;">
                Pending Arrears
            </p>
            <div class="mt-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center"
                    style="background-color:#FDECEA;">
                    <svg class="w-5 h-5" style="color:#9F403D;"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
            <p class="font-manrope mt-3 font-bold" style="font-size:2rem; color:#9F403D;">
                KES {{ number_format($pendingArrears, 0) }}
            </p>
        </div>

        {{-- Occupancy Rate --}}
        <div class="rounded-xl p-6" style="background-color:#FFFFFF;">
            <p class="font-inter text-xs font-medium uppercase tracking-widest"
                style="color:#9BABB3; letter-spacing:0.08em;">
                Occupancy Rate
            </p>
            <div class="mt-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center"
                    style="background-color:#E7EFF3;">
                    <svg class="w-5 h-5" style="color:#585E6C;"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
            <p class="font-manrope mt-3 font-bold" style="font-size:2rem; color:#283439;">
                {{ $occupancyRate }}%
            </p>
        </div>

    </div>

    {{-- Priority Arrears --}}
    <div class="rounded-xl" style="background-color:#FFFFFF;">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-5">
            <div>
                <p class="font-manrope text-base font-semibold" style="color:#283439;">
                    Priority Arrears
                </p>
                <p class="font-inter text-xs mt-0.5 uppercase tracking-widest"
                    style="color:#9BABB3; letter-spacing:0.08em;">
                    Top 5 overdue
                </p>
            </div>
            <a href="{{ route('properties.create') }}" wire:navigate
                class="inline-flex items-center gap-1.5 font-inter text-xs font-semibold
                    text-white px-4 py-2 rounded-md transition-opacity hover:opacity-90"
                style="background: linear-gradient(135deg, #585E6C, #4C5260);">
                + Add Property
            </a>
        </div>

        {{-- List --}}
        @forelse($priorityArrears as $arrear)
        <div class="flex items-center justify-between px-6 py-4 transition-colors hover:rounded-xl"
            style="background-color: transparent;"
            onmouseenter="this.style.backgroundColor='#EFF4F7'"
            onmouseleave="this.style.backgroundColor='transparent'">

            {{-- Avatar + Name --}}
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
                    <p class="font-inter text-xs" style="color:#9BABB3;">
                        Unit {{ $arrear['unit'] }}
                    </p>
                </div>
            </div>

            {{-- Amount + WhatsApp --}}
            <div class="flex items-center gap-6">
                <div class="text-right">
                    <p class="font-manrope text-sm font-bold" style="color:#9F403D;">
                        KES {{ number_format($arrear['amount'], 0) }}
                    </p>
                    <p class="font-inter text-xs" style="color:#9BABB3;">
                        {{ $arrear['days_overdue'] }} {{ Str::plural('day', $arrear['days_overdue']) }} overdue
                    </p>
                </div>

                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $arrear['phone']) }}?text={{ urlencode('Hi ' . $arrear['name'] . ', this is a friendly reminder that your rent of KES ' . number_format($arrear['amount'], 0) . ' is overdue. Please arrange payment at your earliest convenience. Thank you.') }}"
                    target="_blank"
                    class="inline-flex items-center gap-2 font-inter text-xs font-medium
                        px-3 py-2 rounded-md transition-opacity hover:opacity-80"
                    style="background-color:#E7EFF3; color:#585E6C;">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    WhatsApp Nudge
                </a>
            </div>
        </div>
        @empty
        <div class="px-6 py-12 text-center">
            <p class="font-inter text-sm" style="color:#9BABB3;">
                No overdue arrears. All tenants are up to date.
            </p>
        </div>
        @endforelse

    </div>

</div>
</x-app-layout>