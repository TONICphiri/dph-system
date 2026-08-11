<x-error-page
    code="400"
    title="Bad Request"
    message="Your request could not be understood by the server. Please verify your input and try again."
    @if(isset($reference)) reference="{{ $reference }}" @endif
/>