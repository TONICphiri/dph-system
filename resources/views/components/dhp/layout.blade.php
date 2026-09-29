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

    <header class="border-b border-line bg-brand-900 text-brand-100">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <img src="{{ asset('images/logo.png') }}" alt="" class="h-9 w-9 object-contain">
                <span class="leading-tight">
                    <span class="block text-[15px] font-semibold text-white">Digital Health Passport</span>
                    <span class="block text-[12px] text-brand-300">Republic of Malawi</span>
                </span>
            </a>
            <div class="flex items-center gap-3 text-sm">
                <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="underline underline-offset-2 hover:text-white">Sign out</button>
                </form>
            </div>
        </div>
        @if ($nav !== [])
            <nav class="border-t border-brand-800" aria-label="Passport pages">
                <ul class="mx-auto flex max-w-5xl flex-wrap gap-1 px-4 py-2 sm:px-6">
                    @foreach ($nav as $item)
                        <li>
                            <a href="{{ $item['url'] }}" @if (request()->url() === $item['url']) aria-current="page" @endif
                                class="block px-3 py-1.5 text-sm {{ request()->url() === $item['url'] ? 'bg-brand-800 font-medium text-white' : 'text-brand-100 hover:bg-brand-800/60 hover:text-white' }}">{{ $item['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif
    </header>

    <main id="main" class="mx-auto max-w-5xl px-4 py-6 sm:px-6">
        <x-flash />
        {{ $slot }}
    </main>
</body>
</html>
