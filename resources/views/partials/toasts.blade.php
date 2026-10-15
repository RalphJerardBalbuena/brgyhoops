{{--
    Toast notifications for session flash messages and validation feedback.

    Rendered server-side (visible without JavaScript, testable); public/js/brgyhoops-ui.js
    re-announces them through the aria-live region and adds dismiss/auto-dismiss.
    Only app-controlled messages are shown — never exception details.
--}}
@php
    $toastTitles = ['success' => 'Success', 'error' => 'Error', 'warning' => 'Warning', 'info' => 'Notice'];
    $toastIcons = [
        'success' => 'fa-circle-check',
        'error' => 'fa-circle-exclamation',
        'warning' => 'fa-triangle-exclamation',
        'info' => 'fa-circle-info',
    ];

    $toasts = collect();

    foreach (array_keys($toastTitles) as $toastType) {
        $toastMessage = session($toastType);

        if (is_string($toastMessage) && trim($toastMessage) !== '') {
            $toasts->push(['type' => $toastType, 'message' => $toastMessage]);
        }
    }

    // One summary toast for validation errors; field messages stay inline.
    $toastErrors = isset($errors) ? $errors : null;

    if ($toastErrors && $toastErrors->any()) {
        $toasts->push([
            'type' => 'error',
            'message' => $toastErrors->count() === 1
                ? $toastErrors->first()
                : 'Please fix the ' . $toastErrors->count() . ' highlighted fields and try again.',
        ]);
    }

    $toasts = $toasts->unique(fn ($toast) => $toast['type'] . '|' . $toast['message'])->values();
@endphp

<div class="bh-toast-region" data-toast-region aria-live="polite" aria-relevant="additions">
    @foreach ($toasts as $toast)
        <div
            class="bh-toast bh-toast-{{ $toast['type'] }}"
            data-toast="{{ $toast['type'] }}" data-toast-message="{{ $toast['message'] }}"
            role="{{ $toast['type'] === 'error' ? 'alert' : 'status' }}"
        >
            <i class="fa-solid {{ $toastIcons[$toast['type']] }} bh-toast-icon" aria-hidden="true"></i>
            <div class="bh-toast-body">
                <strong class="bh-toast-title">{{ $toastTitles[$toast['type']] }}</strong>
                <span>{{ $toast['message'] }}</span>
            </div>
        </div>
    @endforeach
</div>

{{-- Without JavaScript, keep toasts in the page flow instead of floating over content. --}}
<noscript>
    <style>
        .bh-toast-region { position: static; width: auto; margin: 16px; }
    </style>
</noscript>
