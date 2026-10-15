{{--
    Shared error page body. Expects: $code, $heading, $text.
    Does not use session-dependent partials (flash), because some errors
    (e.g. 404 for unknown URLs) render without the web middleware.
--}}
@extends('layouts.app')

@section('title', $heading)

@section('content')
    <x-page-header
        :kicker="'Error ' . $code"
        icon="fa-circle-exclamation"
        :title="$heading"
        :description="$text"
    >
        <a href="/" class="button button-primary">
            <i class="fa-solid fa-house" aria-hidden="true"></i>
            Back to Home
        </a>
    </x-page-header>

    <div class="page-body">
        <div class="container">
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fa-solid fa-basketball" aria-hidden="true"></i>
                </div>
                <h3>{{ $code }}</h3>
                <p>{{ $text }}</p>
            </div>
        </div>
    </div>
@endsection
