<x-error-page
    code="503"
    title="Service Unavailable"
    message="The service is temporarily unavailable. Please try again in a few moments."
    @if(isset($reference)) reference="{{ $reference }}" @endif
/>