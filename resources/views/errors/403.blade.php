@php
    // Privacy-safe reason: technical permission messages stay hidden.
    $reason = isset($exception) ? $exception->getMessage() : '';
    $showReason = $reason !== ''
        && $reason !== 'This action is unauthorized.'
        && ! ($exception instanceof \Spatie\Permission\Exceptions\UnauthorizedException);

    // Recovery always points at a route this account may open, never at
    // browser history or a fixed dashboard that could loop back here.
    $role = auth()->user()?->role;
    $recovery = match ($role) {
        \App\Enums\DhpRole::Citizen => ['Go to My Passport', route('dhp.citizen.dashboard')],
        \App\Enums\DhpRole::Issuer => ['Go to Issuer Dashboard', route('dhp.issuer.dashboard')],
        \App\Enums\DhpRole::Verifier => ['Go to Verify Certificate', route('dhp.verifier.dashboard')],
        \App\Enums\DhpRole::Admin => ['Go to Administration', route('dhp.admin.dashboard')],
        default => ['Go to Login', route('login')],
    };
@endphp
<x-dhp.layout title="No access" :nav="[]">
    <div class="panel mx-auto max-w-lg">
        <div class="panel-header">
            <h1 class="panel-title">You do not have access to this page</h1>
        </div>
        <div class="panel-body">
            <p class="text-sm text-muted">
                {{ $showReason ? $reason : 'Your role does not allow you to open this page.' }}
                If you need access, ask your administrator.
            </p>
            <div class="mt-4">
                <a href="{{ $recovery[1] }}" class="btn-primary">{{ $recovery[0] }}</a>
            </div>
        </div>
    </div>
</x-dhp.layout>
