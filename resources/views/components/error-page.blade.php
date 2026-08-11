@props([
    'code' => 'Error',
    'title' => 'Something went wrong',
    'message' => 'We were unable to complete your request.',
    'reference' => null,
])

@php
    $reference = $reference ?? 'DHP-ERR-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(5));
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>{{ $code }} - Digital Health Passport</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-vh-100 d-flex align-items-center justify-content-center bg-light">

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-12 col-md-8 col-lg-6">

            <div class="card border-0 shadow-sm rounded-4">

                <div class="card-body text-center p-4 p-md-5">

                    {{-- Hospital Logo --}}
                    <div class="mb-4">

                        <img
                            src="{{ asset('images/dhp-logo.png') }}"
                            alt="Digital Health Passport Logo"
                            class="img-fluid"
                            style="max-width: 110px;"
                        >

                    </div>

                    {{-- Error Code --}}
                    <div class="display-4 fw-bold text-primary mb-2">
                        {{ $code }}
                    </div>

                    {{-- Title --}}
                    <h1 class="h3 fw-bold mb-3">
                        {{ $title }}
                    </h1>

                    {{-- Friendly Message --}}
                    <p class="text-muted mb-4">
                        {{ $message }}
                    </p>

                    {{-- Error Reference --}}
                    <div class="bg-light border rounded-3 p-3 mb-4">

                        <small class="text-muted d-block mb-1">
                            Error Reference ID
                        </small>

                        <code class="fw-semibold">
                            {{ $reference }}
                        </code>

                    </div>

                    {{-- Return Button --}}
                    <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">

                        <a
                            href="{{ url()->previous() }}"
                            class="btn btn-outline-secondary px-4"
                        >
                            ← Go Back
                        </a>

                        <a
                            href="{{ url('/') }}"
                            class="btn btn-primary px-4"
                        >
                            Return Home
                        </a>

                    </div>

                </div>

            </div>

            {{-- Footer --}}
            <div class="text-center mt-4">

                <small class="text-muted">
                    Digital Health Passport System
                </small>

                <br>

                <small class="text-muted">
                    Please provide the reference ID when contacting support.
                </small>

            </div>

        </div>

    </div>

</div>

</body>
</html>