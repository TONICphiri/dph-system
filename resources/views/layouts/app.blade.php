<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="Nationwide Digital Health Passport System for Malawi — registration, triage, consultation, pharmacy, ward and national sync.">

        <title>{{ config('app.name', 'Digital Health Passport') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        {{-- Non-blocking: pages render instantly with system fonts when
             offline; Figtree swaps in when the internet is available. --}}
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" media="print" onload="this.media='all'" />
        <noscript><link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" /></noscript>
        <link rel="manifest" href="/manifest.webmanifest">
        <meta name="theme-color" content="#0E7490">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <a href="#main-content" class="skip-link">Skip to main content</a>

        <div class="min-h-screen">
            @include('layouts.navigation')

            {{-- Browser offline: internet gone, but the local server still
                 answers, so work continues and syncs later. --}}
            <div id="browser-offline-banner" class="border-b border-dhp-700 bg-dhp-900" role="status" style="display:none">
                <div class="dhp-container py-2 text-sm text-white">
                    <p><strong>You are offline.</strong> The system keeps working locally — keep registering, treating and dispensing. Everything uploads when the internet returns.</p>
                </div>
            </div>

            {{-- Offline: records are queued locally and upload on their own
                 once the internet is restored. --}}
            @if (!empty($syncEndpointOn) && !empty($unsyncedCount) && $unsyncedCount > 0)
                <div class="border-b border-amber-300 bg-amber-50" role="status">
                    <div class="dhp-container flex flex-col gap-1 py-2 text-sm text-amber-900 sm:flex-row sm:items-center sm:justify-between">
                        <p><strong>No internet connection</strong> — {{ $unsyncedCount }} {{ Str::plural('record', $unsyncedCount) }} waiting to sync. They will upload automatically once connection is restored.</p>
                        <a href="{{ route('sync.status') }}" class="font-bold underline">View sync queue →</a>
                    </div>
                </div>
            @endif

            @isset($header)
                <header class="border-b border-dhp-900/10 bg-dhp-900 shadow-sm" aria-label="Page header">
                    <div class="dhp-container py-6">
                        <div class="text-white [&_h1]:text-white [&_h2]:text-white [&_p]:text-dhp-100 [&_a]:text-white">
                            {{ $header }}
                        </div>
                    </div>
                </header>
            @endisset

            <main id="main-content" class="dhp-page" tabindex="-1">
                <div class="dhp-container">
                    {{-- HCI: immediate, polite feedback for every action; never rely on color alone --}}
                    @foreach (['success' => 'success', 'error' => 'error', 'warning' => 'warning', 'info' => 'info'] as $key => $type)
                        @if (session($key))
                            <div class="mb-4" aria-live="polite">
                                <x-dhp-alert :type="$type">{{ session($key) }}</x-dhp-alert>
                            </div>
                        @endif
                    @endforeach

                    @if ($errors->any() && !isset($hideGlobalErrors))
                        <div class="mb-4" role="alert">
                            <x-dhp-alert type="error" title="Please review {{ $errors->count() }} {{ Str::plural('field', $errors->count()) }}.">
                                <ul class="mt-1 list-disc pl-5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </x-dhp-alert>
                        </div>
                    @endif

                    {{ $slot }}
                </div>
            </main>

            <footer class="mt-10 border-t border-[#DCE8E8] bg-white" aria-label="Footer">
                <div class="dhp-container flex flex-col gap-2 py-5 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                    <p><strong class="text-dhp-900">Digital Health Passport</strong> · Ministry of Health, Malawi · For clinical use only — protect patient privacy.</p>
                    <p class="tabular-nums">{{ Auth::user()?->facility?->name ?? 'National Registry' }} · {{ now()->format('d M Y') }}</p>
                </div>
            </footer>
        </div>

        <script>
        // Auto-dismiss success toasts after 6s, keep errors visible (HCI: user control).
        (function () {
            setTimeout(function () {
                document.querySelectorAll('.dhp-alert-success').forEach(function (el) {
                    el.style.transition = 'opacity .4s';
                    el.style.opacity = '0';
                    setTimeout(function () { el.remove(); }, 450);
                });
            }, 6000);
        })();

        // Offline operation: register the service worker (cached styles/images)
        // and show a banner when the browser itself loses internet. The local
        // server keeps answering, so work continues; queued records sync later.
        (function () {
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js').catch(function () {});
            }
            var banner = document.getElementById('browser-offline-banner');
            function paint() {
                if (banner) {
                    banner.style.display = navigator.onLine ? 'none' : 'block';
                }
            }
            window.addEventListener('online', paint);
            window.addEventListener('offline', paint);
            paint();
        })();
        </script>
    </body>
</html>
