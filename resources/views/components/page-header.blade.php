@props(['title', 'subtitle' => null, 'actions' => null])

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="dhp-eyebrow">Digital Health Passport · Malawi</p>
        <h1 class="mt-1 text-2xl font-extrabold sm:text-3xl">{{ $title }}</h1>
        @if($subtitle)
            <p class="mt-1 max-w-2xl text-sm text-slate-500">{{ $subtitle }}</p>
        @endif
    </div>
    @if($actions || isset($actions))
        <div class="flex flex-wrap gap-2">{{ $actions ?? '' }}</div>
    @endif
    {{ $slot ?? '' }}
</div>
