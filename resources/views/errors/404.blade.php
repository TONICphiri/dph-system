<x-error-page
    code="404"
    title="Page Not Found"
    message="The page you are looking for could not be found. It may have been moved, removed, or the address may be incorrect."
    @if(isset($reference)) reference="{{ $reference }}" @endif
/>