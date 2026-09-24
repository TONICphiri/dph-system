@props(['title' => null])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' | ' : '' }}{{ $systemName }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <div class="grid min-h-screen lg:grid-cols-[1fr_minmax(0,560px)]">
        <section class="hidden flex-col justify-between bg-brand-900 p-12 text-brand-100 lg:flex">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo.png') }}" alt="" class="h-12 w-12 object-contain">
                <div>
                    <p class="text-lg font-semibold text-white">{{ $systemName }}</p>
                    <p class="text-sm text-brand-300">Republic of Malawi, Ministry of Health</p>
                </div>
            </div>
            <div class="max-w-lg">
                <p class="text-3xl font-semibold leading-tight text-white">One health record for every patient, at every facility.</p>
                <p class="mt-4 text-brand-200">Registration, consultations, admissions, prescriptions and vaccinations are kept in one secure record that follows the patient from facility to facility.</p>
                <dl class="mt-10 grid grid-cols-3 border-t border-brand-700 pt-6 text-sm">
                    <div><dt class="text-brand-300">Access</dt><dd class="mt-1 font-medium text-white">By role</dd></div>
                    <div><dt class="text-brand-300">Identity</dt><dd class="mt-1 font-medium text-white">National ID and QR card</dd></div>
                    <div><dt class="text-brand-300">Records</dt><dd class="mt-1 font-medium text-white">Shared nationally</dd></div>
                </dl>
            </div>
            <p class="text-[13px] text-brand-400">Unauthorised access to patient information is an offence.</p>
        </section>

        <main class="flex items-center justify-center px-6 py-12">
            <div class="w-full max-w-sm">
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <img src="{{ asset('images/logo.png') }}" alt="" class="h-10 w-10 object-contain">
                    <p class="font-semibold">{{ $systemName }}</p>
                </div>
                <x-flash />
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
