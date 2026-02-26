<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'MCRO sms notification system') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('logo/MCR TUBLAY LOGO..png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="fixed inset-0 -z-10">
            <div class="absolute inset-0" style="background:
                radial-gradient(1000px 500px at 100% 0%, rgba(14,165,233,0.10), transparent 70%),
                radial-gradient(800px 400px at 0% 100%, rgba(34,197,94,0.08), transparent 70%),
                linear-gradient(180deg, #f8fafc 0%, #eff6ff 100%)"></div>
            <div class="absolute inset-0 opacity-5" style="background: repeating-conic-gradient(from 45deg, rgba(30,58,138,0.06) 0deg 10deg, transparent 10deg 20deg)"></div>
            <div class="absolute top-[-8%] left-[-6%] w-[360px] h-[360px] rounded-full pointer-events-none" style="background: radial-gradient(circle, rgba(99,102,241,0.22) 0%, transparent 60%); filter: blur(16px);"></div>
            <div class="absolute bottom-[-6%] right-[-4%] w-[400px] h-[400px] rounded-full pointer-events-none" style="background: radial-gradient(circle, rgba(34,197,94,0.18) 0%, transparent 62%); filter: blur(16px);"></div>
            <div class="absolute bottom-8 right-8 pointer-events-none" style="opacity:.12">
                <img src="{{ asset('logo/LOGO1.png') }}" alt="Watermark" class="w-[360px] md:w-[500px] h-auto select-none">
            </div>
        </div>
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white/90 shadow-md overflow-hidden sm:rounded-lg" style="backdrop-filter: saturate(180%) blur(6px);">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
