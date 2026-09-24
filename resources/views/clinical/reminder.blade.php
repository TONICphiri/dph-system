<x-layouts.app title="Add reminder">
    <x-page-header :title="'Add a reminder for '.$patient->full_name" description="The patient receives the reminder in the portal on the due date, and again after each repeat interval." >
        <x-slot:breadcrumb><a href="{{ route('patients.show', $patient) }}" class="hover:text-brand-700">{{ $patient->full_name }}</a> <x-icon name="chevron-right" class="h-3.5 w-3.5" /> Reminder</x-slot:breadcrumb>
    </x-page-header>
    <form method="POST" action="{{ route('reminders.store', $patient) }}" class="panel max-w-3xl">
        @csrf
        <div class="panel-body grid gap-5 sm:grid-cols-2">
            <x-field.select name="category" label="Type" :options="$categories" required />
            <x-field.input name="title" label="Title" required placeholder="For example, Collect monthly medication" />
            <x-field.textarea name="message" label="Message to the patient" rows="3" class="sm:col-span-2" required />
            <x-field.input name="due_on" label="First reminder date" type="date" :min="today()->toDateString()" required />
            <x-field.input name="repeat_every_days" label="Repeat every (days)" type="number" min="1" max="365" help="Leave empty for a single reminder. Use 30 for monthly medication collection." />
            <x-field.checkbox name="is_confidential" label="Confidential" class="sm:col-span-2" help="Use for sensitive treatment such as HIV medication. The notification then gives a general message without naming the treatment." />
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-5 py-3">
            <a href="{{ route('patients.show', $patient) }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Save reminder</button>
        </div>
    </form>
</x-layouts.app>
