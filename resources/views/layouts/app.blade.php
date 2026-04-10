<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'EstateFlow') }}</title>

        {{-- Fonts: Manrope for headings/KPIs, Inter for body --}}
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

            {{-- No page header slot — navigation handles branding --}}

            <main>
                @if(session('success'))
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                        <div class="font-inter text-sm px-4 py-3 rounded-md"
                            style="background-color:#E7EFF3; color:#585E6C;">
                            {{ session('success') }}
                        </div>
                    </div>
                @endif
                @if(session('error'))
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                        <div class="font-inter text-sm px-4 py-3 rounded-md"
                            style="background-color:#FDECEA; color:#9F403D;">
                            {{ session('error') }}
                        </div>
                    </div>
                @endif
                {{ $slot }}
            </main>
        </div>

    </body>
</html>