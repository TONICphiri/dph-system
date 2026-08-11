<x-error-page
    code="403"
    title="Access Denied"
    message="You do not have permission to access this page. If you believe you should have access, please contact your system administrator."
    @if(isset($reference)) reference="{{ $reference }}" @endif
/>