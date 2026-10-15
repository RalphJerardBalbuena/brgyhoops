{{--
    Shared team form fields.
    Expects optionally $team (edit) — null when creating.
--}}
@php
    $team = $team ?? null;
@endphp

<h2 class="form-section-title">
    <i class="fa-solid fa-people-group" aria-hidden="true"></i>
    Team Details
</h2>

<div class="form-grid">
    <div class="field @error('name') has-error @enderror">
        <label for="name">Team Name</label>

        <input
            type="text"
            id="name"
            name="name"
            value="{{ old('name', $team?->name) }}"
            maxlength="255"
            placeholder="e.g. Barangay San Isidro Eagles"
            required
        >

        @error('name')
            <div class="field-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="field @error('short_name') has-error @enderror">
        <label for="short_name">Short Name <span class="optional">(optional)</span></label>

        <input
            type="text"
            id="short_name"
            name="short_name"
            value="{{ old('short_name', $team?->short_name) }}"
            maxlength="20"
            placeholder="e.g. SIE"
        >

        @error('short_name')
            <div class="field-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="field field-full @error('description') has-error @enderror">
        <label for="description">Description <span class="optional">(optional)</span></label>

        <textarea
            id="description"
            name="description"
            rows="5"
            placeholder="Tell players about your team..."
        >{{ old('description', $team?->description) }}</textarea>

        @error('description')
            <div class="field-error">{{ $message }}</div>
        @enderror
    </div>
</div>
