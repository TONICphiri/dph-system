<x-error-page
    code="500"
    title="Server Error"
    message="We're sorry, but an unexpected error occurred on our server. Please try again later."
    @if(isset($reference)) reference="{{ $reference }}" @endif
/>