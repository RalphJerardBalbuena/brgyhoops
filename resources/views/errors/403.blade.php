@include('errors._page', [
    'code' => 403,
    'heading' => 'Access Denied',
    'text' => ($exception?->getMessage() ?: 'You do not have permission to open this page.'),
])
