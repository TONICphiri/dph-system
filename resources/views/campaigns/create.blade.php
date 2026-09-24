<x-layouts.app title="New campaign">
    <x-page-header title="New health campaign" :description="auth()->user()->facility ? 'The message is sent to patients registered at '.auth()->user()->facility->name.'.' : 'The message is sent to patients at every facility.'">
        <x-slot:breadcrumb><a href="{{ route('campaigns.index') }}" class="hover:text-brand-700">Health campaigns</a> <x-icon name="chevron-right" class="h-3.5 w-3.5" /> New</x-slot:breadcrumb>
    </x-page-header>
    <form method="POST" action="{{ route('campaigns.store') }}" class="panel max-w-3xl">
        @csrf
        <div class="panel-body grid gap-5 sm:grid-cols-2">
            <x-field.input name="title" label="Title" class="sm:col-span-2" required />
            <x-field.select name="category" label="Category" :options="$categories" required />
            <x-field.select name="audience" label="Send to" :options="$audiences" required />
            <x-field.textarea name="message" label="Message" rows="5" class="sm:col-span-2" help="Write in plain language. The message appears in the patient's notifications." required />
            <x-field.checkbox name="publish_now" label="Send immediately" help="Leave unticked to save as a draft and send later." class="sm:col-span-2" />
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-5 py-3">
            <a href="{{ route('campaigns.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Save campaign</button>
        </div>
    </form>
</x-layouts.app>
