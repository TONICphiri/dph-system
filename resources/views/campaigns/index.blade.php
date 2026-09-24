<x-layouts.app title="Health campaigns">
    <x-page-header title="Health campaigns" description="Health messages sent to patients as notifications, for example dental checks, nutrition, maternal health and cancer screening.">
        <x-slot:actions><a href="{{ route('campaigns.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> New campaign</a></x-slot:actions>
    </x-page-header>

    <section class="panel">
        @if ($campaigns->isEmpty())
            <x-empty title="No campaigns yet" icon="megaphone" />
        @else
            @foreach ($campaigns as $campaign)
                <article class="border-b border-line px-5 py-4 last:border-b-0">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-semibold">{{ $campaign->title }}</h3>
                                <span class="badge-info">{{ $campaign->category }}</span>
                                <x-badge :tone="$campaign->isPublished() ? 'success' : 'neutral'">{{ $campaign->isPublished() ? 'Sent' : 'Draft' }}</x-badge>
                            </div>
                            <p class="mt-1 max-w-3xl text-sm text-muted">{{ $campaign->message }}</p>
                            <p class="mt-2 text-[13px] text-muted">
                                {{ $campaign->audience->label() }}, {{ $campaign->facility?->name ?? 'all facilities' }}.
                                Created by {{ $campaign->createdBy?->name }}.
                                @if ($campaign->isPublished()) Sent {{ $campaign->published_at->format('j M Y') }} to {{ $campaign->recipients_count }} patients. @endif
                            </p>
                        </div>
                        @unless ($campaign->isPublished())
                            <form method="POST" action="{{ route('campaigns.publish', $campaign) }}" onsubmit="return confirm('Send this campaign to patients now?')">
                                @csrf
                                <button type="submit" class="btn-primary btn-sm">Send now</button>
                            </form>
                        @endunless
                    </div>
                </article>
            @endforeach
            {{ $campaigns->links() }}
        @endif
    </section>
</x-layouts.app>
