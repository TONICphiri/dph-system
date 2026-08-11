<x-error-page
    code="401"
    title="Unauthorized"
    message="You need to sign in to access this page. Please log in and try again."
    @if(isset($reference)) reference="{{ $reference }}" @endif
/>