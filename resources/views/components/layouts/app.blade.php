<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'EstateFlow') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body { background-color: #F7FAFC; color: #283439; }
            .font-manrope { font-family: 'Manrope', sans-serif; }
            .font-inter { font-family: 'Inter', sans-serif; }
        </style>
    </head>
    <body class="font-inter antialiased" style="background-color: #F7FAFC;">
        <div class="min-h-screen">
            <livewire:layout.navigation />
            <main>
                {{ $slot }}
                {{-- Footer --}}
                <footer class="mt-16 pb-8" style="border-top: 0.5px solid #E7EFF3;">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
                        <div class="flex items-center justify-between flex-wrap gap-4">
                            <div>
                                <p class="font-manrope text-sm font-semibold" style="color:#283439;">EstateFlow</p>
                                <p class="font-inter text-xs mt-0.5" style="color:#9BABB3;">
                                    Property management, architecturally refined.
                                </p>
                            </div>
                            <div class="flex items-center gap-6">
                                <a href="{{ route('dashboard') }}" wire:navigate
                                    class="font-inter text-xs transition-opacity hover:opacity-70"
                                    style="color:#9BABB3;">Home</a>
                                <a href="{{ route('properties.index') }}" wire:navigate
                                    class="font-inter text-xs transition-opacity hover:opacity-70"
                                    style="color:#9BABB3;">Properties</a>
                                <a href="{{ route('tenants.index') }}" wire:navigate
                                    class="font-inter text-xs transition-opacity hover:opacity-70"
                                    style="color:#9BABB3;">Tenants</a>
                                <a href="{{ route('leases.index') }}" wire:navigate
                                    class="font-inter text-xs transition-opacity hover:opacity-70"
                                    style="color:#9BABB3;">Leases</a>
                                <a href="{{ route('reports.index') }}" wire:navigate
                                    class="font-inter text-xs transition-opacity hover:opacity-70"
                                    style="color:#9BABB3;">Reports</a>
                                <a href="{{ route('settings') }}" wire:navigate
                                    class="font-inter text-xs transition-opacity hover:opacity-70"
                                    style="color:#9BABB3;">Settings</a>
                            </div>
                            <p class="font-inter text-xs" style="color:#9BABB3;">
                                © {{ now()->year }} EstateFlow · Built for Kenyan landlords
                            </p>
                        </div>
                    </div>
                </footer>
            </main>
        </div>
    </body>
</html>