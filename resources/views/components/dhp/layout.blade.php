@props(['title' => null, 'nav' => []])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' | ' : '' }}Digital Health Passport</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-2 focus:top-2 focus:z-50 focus:bg-white focus:px-3 focus:py-2">Skip to main content</a>

    <header class="no-print border-b border-line bg-brand-900 text-brand-100">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <img src="{{ asset('images/logo.png') }}" alt="" class="h-9 w-9 object-contain">
                <span class="leading-tight">
                    <span class="block text-[15px] font-semibold text-white">Digital Health Passport</span>
                    <span class="block text-[12px] text-brand-300">Secure, verifiable health credentials · Republic of Malawi</span>
                </span>
            </a>
            <div class="flex items-center gap-3 text-sm">
                @auth
                    <span class="hidden sm:inline">{{ auth()->user()->name }} · {{ auth()->user()->role?->label() ?? '' }}</span>
                    @if ($nav !== [])
                        <button type="button" id="dhp-menu-button" class="border border-brand-700 px-3 py-1.5 sm:hidden"
                            aria-expanded="false" aria-controls="dhp-menu" aria-label="Open passport menu">Menu</button>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="underline underline-offset-2 hover:text-white">Logout</button>
                    </form>
                @endauth
            </div>
        </div>
        @if ($nav !== [])
            <nav class="border-t border-brand-800" aria-label="Passport pages">
                <ul id="dhp-menu" class="mx-auto hidden max-w-5xl flex-col gap-1 px-4 py-2 sm:flex sm:flex-row sm:flex-wrap sm:px-6">
                    @foreach ($nav as $item)
                        <li>
                            <a href="{{ $item['url'] }}" @if (request()->url() === $item['url']) aria-current="page" @endif
                                class="block px-3 py-2 text-sm sm:py-1.5 {{ request()->url() === $item['url'] ? 'bg-brand-800 font-medium text-white' : 'text-brand-100 hover:bg-brand-800/60 hover:text-white' }}">{{ $item['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif
    </header>

    <main id="main" class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
        <div aria-live="polite"><x-flash /></div>
        {{ $slot }}
    </main>

    <script>
        (function () {
            var button = document.getElementById('dhp-menu-button');
            var menu = document.getElementById('dhp-menu');
            if (!button || !menu) return;
            button.addEventListener('click', function () {
                var wasHidden = menu.classList.contains('hidden');
                menu.classList.toggle('hidden', !wasHidden);
                menu.classList.toggle('flex', wasHidden);
                button.setAttribute('aria-expanded', wasHidden ? 'true' : 'false');
                button.setAttribute('aria-label', wasHidden ? 'Close passport menu' : 'Open passport menu');
            });
        })();
    </script>
</body>
</html>
