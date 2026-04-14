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
    <body class="font-sans antialiased">
        <div class="fixed inset-0 -z-10">
            <div class="absolute inset-0" style="background:
                radial-gradient(1000px 500px at 100% 0%, rgba(14,165,233,0.12), transparent 70%),
                radial-gradient(800px 400px at 0% 100%, rgba(34,197,94,0.10), transparent 70%),
                linear-gradient(180deg, #eef2ff 0%, #e9efff 100%)"></div>
            <div class="absolute inset-0" style="background: linear-gradient(180deg, rgba(2,6,23,0.06) 0%, rgba(2,6,23,0.10) 100%);"></div>
            <div class="absolute inset-0 opacity-5" style="background: repeating-conic-gradient(from 45deg, rgba(30,58,138,0.06) 0deg 10deg, transparent 10deg 20deg)"></div>
            <div class="absolute top-[-10%] left-[-8%] w-[420px] h-[420px] rounded-full pointer-events-none" style="background: radial-gradient(circle, rgba(99,102,241,0.22) 0%, transparent 60%); filter: blur(18px);"></div>
            <div class="absolute bottom-[-8%] right-[-6%] w-[480px] h-[480px] rounded-full pointer-events-none" style="background: radial-gradient(circle, rgba(34,197,94,0.18) 0%, transparent 62%); filter: blur(18px);"></div>
            <div class="absolute bottom-10 right-10 pointer-events-none" style="opacity:.10">
                <img src="{{ asset('logo/LOGO1.png') }}" alt="Watermark" class="w-[420px] md:w-[560px] h-auto select-none">
            </div>
        </div>
        <div class="min-h-screen">
            @include('layouts.navigation')

            <!-- Page Content -->
            <main>
                @php
                    $provider = config('sms.provider', 'log');
                    $tb = config('sms.textbee', []);
                    $tbReady = ($tb['device_id'] ?? '') !== '' && ($tb['api_key'] ?? '') !== '';
                @endphp
                @if($provider === 'log')
                    <div class="px-4 py-2 bg-gray-900 text-white text-xs">
                        <div class="app-shell flex items-center justify-between">
                            <div>SMS Simulation Mode is active. Messages are not sent to recipients.</div>
                        </div>
                    </div>
                @elseif($provider === 'textbee' && !$tbReady)
                    <div class="px-4 py-2 bg-amber-600 text-white text-xs">
                        <div class="app-shell flex items-center justify-between">
                            <div>SMS Gateway not configured. Set TEXTBEE_DEVICE_ID and TEXTBEE_API_KEY.</div>
                        </div>
                    </div>
                @endif
                @isset($header)
                    <div class="app-shell py-3">
                        {{ $header }}
                    </div>
                @endisset
                {{ $slot }}
            </main>
        </div>

        <style>
            .tw-toast {
                transform: translateY(8px);
                opacity: 0;
                transition: transform 220ms ease, opacity 220ms ease;
                will-change: transform, opacity;
            }
            .tw-toast.tw-in {
                transform: translateY(0);
                opacity: 1;
            }
            .tw-toast.tw-out {
                transform: translateY(8px);
                opacity: 0;
            }
            .tw-pressable {
                transition: transform 140ms ease, box-shadow 140ms ease, background-color 140ms ease, color 140ms ease;
            }
            .tw-pressable:active {
                transform: scale(0.98);
            }
            tr.tw-row-out {
                opacity: 0;
                transform: translateY(6px);
                transition: opacity 170ms ease, transform 170ms ease;
            }
        </style>
        <div class="fixed bottom-4 right-4 space-y-2">
            @if(session('status'))
                <div id="twToast" class="tw-toast flex items-center gap-3 bg-blue-600 text-white rounded-md shadow px-4 py-3">
                    <div class="flex-1 text-sm">{{ session('status') }}</div>
                    @if(session('undo_id'))
                        @php $undoType = session('undo_type') ?? 'service'; @endphp
                        <form id="undoForm" action="{{ $undoType === 'user' ? route('admin.users.restore', session('undo_id')) : route('services.restore', session('undo_id')) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-blue-600 bg-white hover:bg-gray-100 rounded px-2 py-1 text-xs">Undo</button>
                        </form>
                    @endif
                    <button id="twToastClose" type="button" class="text-white/90 hover:text-white text-sm">✕</button>
                </div>
            @endif
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var toastEl = document.getElementById('twToast');
                var closeBtn = document.getElementById('twToastClose');
                var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                var undoForm = document.getElementById('undoForm');
                var undoClicked = false;
                if (undoForm) {
                    undoForm.addEventListener('submit', function(e){
                        undoClicked = true;
                        e.preventDefault();
                        if (!csrf) return;
                        fetch(undoForm.action, {
                            method: 'POST',
                            headers: { "X-CSRF-TOKEN": csrf, "Accept": "text/html" }
                        }).then(function(){
                            if (window.updateServicesTable) {
                                window.updateServicesTable();
                            } else {
                                location.href = "{{ route('services.index') }}";
                            }
                            var toastEl2 = document.getElementById('twToast');
                            if (toastEl2) {
                                toastEl2.classList.remove('tw-in');
                                toastEl2.classList.add('tw-out');
                                toastEl2.addEventListener('transitionend', function te(){
                                    toastEl2.removeEventListener('transitionend', te);
                                    toastEl2.style.display = 'none';
                                }, { once: true });
                            }
                        });
                    });
                }
                if (toastEl) {
                    requestAnimationFrame(function(){ toastEl.classList.add('tw-in'); });
                    var animateOut = function(cb){
                        toastEl.classList.remove('tw-in');
                        toastEl.classList.add('tw-out');
                        toastEl.addEventListener('transitionend', function te(){
                            toastEl.removeEventListener('transitionend', te);
                            toastEl.style.display = 'none';
                            if (typeof cb === 'function') cb();
                        }, { once: true });
                    };
                    if (closeBtn) closeBtn.addEventListener('click', function(){
                        animateOut(function(){
                            @if(session('undo_id'))
                            if (!undoClicked && csrf) {
                                fetch("{{ (session('undo_type') ?? 'service') === 'user' ? route('admin.users.force-delete', session('undo_id')) : route('services.force-delete', session('undo_id')) }}", {
                                    method: "POST",
                                    headers: {
                                        "X-CSRF-TOKEN": csrf,
                                        "Accept": "application/json"
                                    }
                                });
                            }
                            @endif
                        });
                    });
                    setTimeout(function(){
                        animateOut(function(){
                            @if(session('undo_id'))
                            if (!undoClicked && csrf) {
                                fetch("{{ (session('undo_type') ?? 'service') === 'user' ? route('admin.users.force-delete', session('undo_id')) : route('services.force-delete', session('undo_id')) }}", {
                                    method: "POST",
                                    headers: {
                                        "X-CSRF-TOKEN": csrf,
                                        "Accept": "application/json"
                                    }
                                });
                            }
                            @endif
                        });
                    }, 5000);
                }
            });
        </script>
        <script>
            window.twShowToast = function (msg) {
                var wrap = document.querySelector('.fixed.bottom-4.right-4.space-y-2');
                if (!wrap) {
                    wrap = document.createElement('div');
                    wrap.className = 'fixed bottom-4 right-4 space-y-2';
                    document.body.appendChild(wrap);
                }
                var el = document.createElement('div');
                el.className = 'tw-toast flex items-center gap-3 bg-blue-600 text-white rounded-md shadow px-4 py-3';
                var content = document.createElement('div');
                content.className = 'flex-1 text-sm';
                content.textContent = msg || '';
                var close = document.createElement('button');
                close.type = 'button';
                close.className = 'text-white/90 hover:text-white text-sm';
                close.textContent = '✕';
                el.appendChild(content);
                el.appendChild(close);
                wrap.appendChild(el);
                requestAnimationFrame(function(){ el.classList.add('tw-in'); });
                var hide = function(){
                    el.classList.remove('tw-in');
                    el.classList.add('tw-out');
                    el.addEventListener('transitionend', function te(){
                        el.removeEventListener('transitionend', te);
                        el.remove();
                    }, { once: true });
                };
                close.addEventListener('click', hide);
                setTimeout(hide, 5000);
            };
            window.twShowUndoToast = function (id, type, msg) {
                var wrap = document.querySelector('.fixed.bottom-4.right-4.space-y-2');
                if (!wrap) {
                    wrap = document.createElement('div');
                    wrap.className = 'fixed bottom-4 right-4 space-y-2';
                    document.body.appendChild(wrap);
                }
                var el = document.createElement('div');
                el.className = 'tw-toast flex items-center gap-3 bg-blue-600 text-white rounded-md shadow px-4 py-3';
                var content = document.createElement('div');
                content.className = 'flex-1 text-sm';
                content.textContent = msg || 'Item deleted';
                var undo = document.createElement('button');
                undo.type = 'button';
                undo.className = 'text-blue-600 bg-white hover:bg-gray-100 rounded px-2 py-1 text-xs';
                undo.textContent = 'Undo';
                var close = document.createElement('button');
                close.type = 'button';
                close.className = 'text-white/90 hover:text-white text-sm';
                close.textContent = '✕';
                el.appendChild(content);
                el.appendChild(undo);
                el.appendChild(close);
                wrap.appendChild(el);
                requestAnimationFrame(function(){ el.classList.add('tw-in'); });
                var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                var undone = false;
                var restoreUrl = type === 'user' ? "{{ route('admin.users.restore', '__ID__') }}".replace('__ID__', id) : "{{ route('services.restore', '__ID__') }}".replace('__ID__', id);
                var forceUrl = type === 'user' ? "{{ route('admin.users.force-delete', '__ID__') }}".replace('__ID__', id) : "{{ route('services.force-delete', '__ID__') }}".replace('__ID__', id);
                var hide = function(cb){
                    el.classList.remove('tw-in');
                    el.classList.add('tw-out');
                    el.addEventListener('transitionend', function te(){
                        el.removeEventListener('transitionend', te);
                        el.remove();
                        if (typeof cb === 'function') cb();
                    }, { once: true });
                };
                undo.addEventListener('click', function(){
                    undone = true;
                    if (!csrf) { hide(); return; }
                    fetch(restoreUrl, {
                        method: 'POST',
                        headers: { "X-CSRF-TOKEN": csrf, "Accept": "text/html" }
                    }).then(function(){
                        hide(function(){
                            if (window.updateServicesTable) {
                                window.updateServicesTable();
                            } else {
                                location.href = "{{ route('services.index') }}";
                            }
                        });
                    });
                });
                close.addEventListener('click', function(){
                    hide(function(){
                        if (!undone && csrf) {
                            fetch(forceUrl, {
                                method: 'POST',
                                headers: { "X-CSRF-TOKEN": csrf, "Accept": "application/json" }
                            });
                        }
                    });
                });
                setTimeout(function(){
                    hide(function(){
                        if (!undone && csrf) {
                            fetch(forceUrl, {
                                method: 'POST',
                                headers: { "X-CSRF-TOKEN": csrf, "Accept": "application/json" }
                            });
                        }
                    });
                }, 5000);
            };
        </script>
    </body>
</html>
