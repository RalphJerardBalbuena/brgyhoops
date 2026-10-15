@props(['status' => null])

@php
    $statusKey = strtolower((string) ($status ?: 'unknown'));
    $knownStatuses = ['upcoming', 'ongoing', 'completed', 'scheduled', 'cancelled', 'pending', 'approved', 'rejected', 'draft'];
@endphp

<span {{ $attributes->merge(['class' => 'badge' . (in_array($statusKey, $knownStatuses, true) ? ' badge-' . $statusKey : '')]) }}>
    {{ ucfirst($statusKey) }}
</span>
