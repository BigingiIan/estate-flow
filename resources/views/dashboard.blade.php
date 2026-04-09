<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Stat Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-10">

            {{-- Total Rent Collected --}}
            <div class="bg-white border border-gray-100 rounded-lg p-6">
                <p class="text-xs font-semibold tracking-widest text-slate-400 uppercase">
                    Total Rent Collected This Month
                </p>
                <div class="mt-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-md bg-green-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="mt-3 text-2xl font-bold text-green-500">
                    KES {{ number_format($monthlyCollected, 0) }}
                </p>
            </div>

            {{-- Pending Arrears --}}
            <div class="bg-white border border-gray-100 rounded-lg p-6">
                <p class="text-xs font-semibold tracking-widest text-slate-400 uppercase">
                    Pending Arrears
                </p>
                <div class="mt-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-md bg-red-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                </div>
                <p class="mt-3 text-2xl font-bold text-red-500">
                    KES {{ number_format($pendingArrears, 0) }}
                </p>
            </div>

            {{-- Occupancy Rate --}}
            <div class="bg-white border border-gray-100 rounded-lg p-6">
                <p class="text-xs font-semibold tracking-widest text-slate-400 uppercase">
                    Occupancy Rate
                </p>
                <div class="mt-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-md bg-slate-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                </div>
                <p class="mt-3 text-2xl font-bold text-slate-700">
                    {{ $occupancyRate }}%
                </p>
            </div>

        </div>

        {{-- Priority Arrears --}}
        <div class="bg-white border border-gray-100 rounded-lg">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-slate-700">Priority Arrears</h2>
                <div class="flex items-center gap-3">
                    <span class="text-xs font-semibold tracking-widest text-slate-400 uppercase">Top 5 Overdue</span>
                    <a href="{{ route('properties.create') }}" wire:navigate
                        class="inline-flex items-center gap-1 bg-slate-700 hover:bg-slate-800 text-white text-xs font-semibold px-3 py-2 rounded-md transition-colors">
                        + Add Property
                    </a>
                </div>
            </div>

            @forelse($priorityArrears as $arrear)
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-50 last:border-0">
                <div class="flex items-center gap-3">
                    {{-- Initials avatar --}}
                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-sm font-semibold text-slate-500">
                        {{ strtoupper(substr($arrear['name'], 0, 1)) }}{{ strtoupper(substr(strstr($arrear['name'], ' '), 1, 1)) }}
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-700">{{ $arrear['name'] }}</p>
                        <p class="text-xs text-slate-400">Unit {{ $arrear['unit'] }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <div class="text-right">
                        <p class="text-sm font-bold text-red-500">KES {{ number_format($arrear['amount'], 0) }}</p>
                        <p class="text-xs text-slate-400">{{ $arrear['days_overdue'] }} {{ Str::plural('day', $arrear['days_overdue']) }} overdue</p>
                    </div>

                    {{-- WhatsApp Nudge --}}
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $arrear['phone']) }}?text={{ urlencode('Hi ' . $arrear['name'] . ', this is a reminder that your rent of KES ' . number_format($arrear['amount'], 0) . ' is overdue. Please arrange payment at your earliest convenience.') }}"
                        target="_blank"
                        class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-medium px-3 py-2 rounded-md transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        WhatsApp Nudge
                    </a>
                </div>
            </div>
            @empty
            <div class="px-6 py-8 text-center text-sm text-slate-400">
                No overdue arrears. All tenants are up to date.
            </div>
            @endforelse
        </div>

    </div>
</x-app-layout>