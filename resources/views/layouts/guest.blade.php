<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'EstateFlow') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-100">

        <div class="min-h-screen flex">

            {{-- Left branding panel --}}
            <div class="hidden lg:flex lg:w-1/2 bg-slate-100 flex-col justify-between p-12">
                <div>
                    <span class="text-xl font-semibold tracking-widest text-slate-500 uppercase">EstateFlow</span>
                    <div class="w-8 h-0.5 bg-slate-400 mt-2"></div>
                </div>

                <div>
                    <h1 class="text-5xl font-bold text-slate-700 leading-tight">
                        Smarter<br>Modern<br>
                        <span class="text-slate-500">Asset Management.</span>
                    </h1>
                    <p class="mt-6 text-slate-500 text-lg leading-relaxed max-w-md">
                        Built for property managers and owners who want both precision and ease.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex -space-x-2">
                        <div class="w-8 h-8 rounded-full bg-slate-400 border-2 border-white flex items-center justify-center text-white text-xs font-medium">A</div>
                        <div class="w-8 h-8 rounded-full bg-slate-500 border-2 border-white flex items-center justify-center text-white text-xs font-medium">B</div>
                        <div class="w-8 h-8 rounded-full bg-slate-600 border-2 border-white flex items-center justify-center text-white text-xs font-medium">C</div>
                    </div>
                    <span class="text-xs tracking-widest text-slate-400 uppercase">Made for Kenya</span>
                </div>
            </div>

            {{-- Right form panel --}}
            <div class="w-full lg:w-1/2 flex flex-col justify-between p-8 sm:p-12 bg-white">
                <div></div>

                <div class="w-full max-w-md mx-auto">
                    {{-- Validation error banner --}}
                    @if($errors->any())
                    <div class="mb-6 font-inter text-sm px-4 py-3 rounded-md"
                        style="background-color:#FDECEA; color:#9F403D;">
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                    @endif

                    @if(session('status'))
                    <div class="mb-6 font-inter text-sm px-4 py-3 rounded-md"
                        style="background-color:#E7EFF3; color:#585E6C;">
                        {{ session('status') }}
                    </div>
                    @endif

                    {{ $slot }}
                </div>

                <div class="flex gap-4 justify-center text-xs text-slate-400">
                    <a href="#" class="hover:text-slate-600">Privacy Policy</a>
                    <span>•</span>
                    <a href="#" class="hover:text-slate-600">Terms of Service</a>
                </div>
            </div>

        </div>

    </body>
</html>