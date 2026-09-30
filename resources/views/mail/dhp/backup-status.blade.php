<x-mail::message>
# Digital Health Passport backup status

@if ($attached)
A new encrypted database backup completed and is attached to this email. Store it securely.
@else
A new encrypted database backup completed. It exceeded the configured email attachment limit, so it was not attached. It remains stored securely on the configured server backup storage.
@endif

<x-mail::button :url="config('app.url')">
Open the application
</x-mail::button>

This is an automated message. Do not reply.<br>
{{ config('app.name') }}
</x-mail::message>
