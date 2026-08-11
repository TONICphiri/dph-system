<x-error-page
    code="429"
    title="Too Many Requests"
    message="You have made too many requests to this page. Please try again later."
    @if(isset($reference)) reference="{{ $reference }}" @endif
/>