@props([
    'kicker' => null,
    'icon' => 'fa-basketball',
    'title',
    'highlight' => null,
    'description' => null,
])

{{-- Compact version of the homepage hero, used at the top of every app page. --}}
<section class="page-hero">
    <div class="container">
        <div class="page-hero-inner">
            <div class="page-hero-copy">
                @if ($kicker)
                    <div class="page-hero-label">
                        <i class="fa-solid {{ $icon }}" aria-hidden="true"></i>
                        {{ $kicker }}
                    </div>
                @endif

                <h1>{{ $title }}@if ($highlight) <span>{{ $highlight }}</span>@endif</h1>

                @if ($description)
                    <p class="page-hero-description">{{ $description }}</p>
                @endif

                @isset($meta)
                    <div class="page-hero-meta">
                        {{ $meta }}
                    </div>
                @endisset
            </div>

            @if (trim($slot) !== '')
                <div class="page-hero-actions">
                    {{ $slot }}
                </div>
            @endif
        </div>
    </div>
</section>
