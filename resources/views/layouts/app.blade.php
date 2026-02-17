<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow-sm sticky top-16 z-40 border-b border-gray-200">
                    <div class="max-w-7xl mx-auto py-3 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
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
                        <form id="undoForm" action="{{ route('services.restore', session('undo_id')) }}" method="POST" class="inline">
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
                                fetch("{{ route('services.force-delete', session('undo_id')) }}", {
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
                                fetch("{{ route('services.force-delete', session('undo_id')) }}", {
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
        </script>
    </body>
</html>
