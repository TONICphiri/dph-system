<x-error-page
    code="503"
    title="Service Unavailable"
    message="The server is currently unavailable. Please try again later."
    @if(isset($reference)) reference="{{ $reference }}" @endif
/>