@extends('layouts.app')

@section('title', 'Create a Tournament')

@section('content')
    <x-page-header
        kicker="Tournament Management"
        icon="fa-trophy"
        title="Create a"
        highlight="Tournament"
        description="Set up a competition and assign the team managers who can register teams and manage their tournament."
    >
        <a href="{{ route('admin.tournaments.index') }}" class="button button-outline">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            Back to tournaments
        </a>
    </x-page-header>

    <div class="page-body">
        <div class="container-narrow">
            @include('partials.flash')

            <form method="POST" action="{{ route('admin.tournaments.store') }}" class="card form-card">
                @csrf

                @include('admin.tournaments._fields', ['tournament' => null])

                <h2 class="form-section-title">
                    <i class="fa-solid fa-users" aria-hidden="true"></i>
                    Assign team managers
                </h2>
                <p class="field-hint" style="margin: -8px 0 16px;">
                    Select every manager who should be able to access this tournament. You can leave this empty and assign managers later.
                </p>

                @if ($managers->isNotEmpty())
                    <div class="check-list">
                        @foreach ($managers as $manager)
                            <label class="check-option" for="manager-{{ $manager->id }}">
                                <input
                                    id="manager-{{ $manager->id }}"
                                    type="checkbox"
                                    name="manager_ids[]"
                                    value="{{ $manager->id }}"
                                    @checked(in_array($manager->id, old('manager_ids', [])))
                                >
                                <span>
                                    <strong>{{ $manager->name }}</strong>
                                    <span>{{ $manager->email }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        <div>
                            No team manager accounts were found. You can create this tournament now, then return to assign managers after manager accounts are available.
                        </div>
                    </div>
                @endif

                @error('manager_ids') <div class="field-error">{{ $message }}</div> @enderror
                @error('manager_ids.*') <div class="field-error">{{ $message }}</div> @enderror

                <div class="form-actions">
                    <a href="{{ route('admin.tournaments.index') }}" class="button button-light">
                        Cancel
                    </a>
                    <button type="submit" class="button button-primary" data-loading-text="Creating tournament…">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        Create tournament
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
