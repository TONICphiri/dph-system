<x-error-page
    code="419"
    title="Page Expired"
    message="The page you are looking for has expired. Please refresh the page and try again."
    @if(isset($reference)) reference="{{ $reference }}" @endif
/>