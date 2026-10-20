{{--
    Shared tournament detail fields for the admin create and edit forms.
    Expects: $venueSupported (bool), $prizePoolSupported (bool), $formatsSupported (bool), and optionally $tournament (edit) — null when creating.
--}}
@php
    $tournament = $tournament ?? null;
@endphp

<h2 class="form-section-title">
    <i class="fa-solid fa-trophy" aria-hidden="true"></i>
    Tournament details
</h2>

<div class="form-grid">
    <div class="field field-full @error('name') has-error @enderror">
        <label for="name"><i class="fa-solid fa-heading" aria-hidden="true"></i> Tournament name *</label>
        <input id="name" name="name" type="text" value="{{ old('name', $tournament?->name) }}" placeholder="e.g. 2026 Inter-Barangay Basketball League" maxlength="255" required>
        @error('name') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    @php
        $currentType = old('type', $tournament?->type ?? 'inter_barangay');
    @endphp

    <div class="field field-full @error('type') has-error @enderror">
        <label for="type"><i class="fa-solid fa-layer-group" aria-hidden="true"></i> Tournament type *</label>
        <select id="type" name="type" required>
            <option value="">Select tournament type</option>
            <option value="inter_zone" @selected($currentType === 'inter_zone')>Inter-Zone</option>
            <option value="inter_barangay" @selected($currentType === 'inter_barangay')>Inter-Barangay</option>
            <option value="inter_town" @selected($currentType === 'inter_town')>Inter-Town</option>
        </select>
        @error('type') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    @if ($formatsSupported ?? false)
    @php
        // Existing tournaments may have no format yet: nothing is preselected then.
        $currentTournamentFormat = old('tournament_format', $tournament?->tournament_format);
        $currentFinalsFormat = old('finals_format', $tournament?->finals_format);
    @endphp

    <div class="field @error('tournament_format') has-error @enderror">
        <label for="tournament_format"><i class="fa-solid fa-sitemap" aria-hidden="true"></i> Tournament format *</label>
        <select id="tournament_format" name="tournament_format" required>
            <option value="">Select tournament format</option>
            @foreach (\App\Models\Tournament::TOURNAMENT_FORMATS as $value => $label)
                <option value="{{ $value }}" @selected($currentTournamentFormat === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('tournament_format') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    <div class="field @error('finals_format') has-error @enderror">
        <label for="finals_format"><i class="fa-solid fa-medal" aria-hidden="true"></i> Finals format *</label>
        <select id="finals_format" name="finals_format" required>
            <option value="">Select finals format</option>
            @foreach (\App\Models\Tournament::FINALS_FORMATS as $value => $label)
                <option value="{{ $value }}" @selected($currentFinalsFormat === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('finals_format') <div class="field-error">{{ $message }}</div> @enderror
    </div>
    @endif

    @if ($venueSupported ?? false)
    <div class="field field-full @error('venue') has-error @enderror">
        <label for="venue"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Primary venue <span class="optional">(optional)</span></label>
        <input id="venue" name="venue" type="text" value="{{ old('venue', $tournament?->venue) }}" placeholder="e.g. San Isidro Covered Court, Quezon City" maxlength="255">
        @error('venue') <div class="field-error">{{ $message }}</div> @enderror
        <div class="field-hint">Use a full address so the map and directions point to the right place. Individual games can still have their own venue.</div>
    </div>
    @endif

    @if ($prizePoolSupported ?? false)
    <div class="field field-full @error('prize_pool') has-error @enderror">
        <label for="prize_pool"><i class="fa-solid fa-sack-dollar" aria-hidden="true"></i> Prize pool (₱) <span class="optional">(optional)</span></label>
        <input id="prize_pool" name="prize_pool" type="number" min="0" max="9999999999.99" step="0.01" value="{{ old('prize_pool', $tournament?->prize_pool) }}" placeholder="e.g. 50000">
        @error('prize_pool') <div class="field-error">{{ $message }}</div> @enderror
        <div class="field-hint">Total prize money in Philippine pesos. Leave blank if there is no prize pool or it has not been announced.</div>
    </div>
    @endif

    <div class="field @error('start_date') has-error @enderror">
        <label for="start_date"><i class="fa-regular fa-calendar" aria-hidden="true"></i> Start date *</label>
        <input id="start_date" name="start_date" type="date" value="{{ old('start_date', $tournament?->start_date?->format('Y-m-d')) }}" required>
        @error('start_date') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    <div class="field @error('end_date') has-error @enderror">
        <label for="end_date"><i class="fa-regular fa-calendar-check" aria-hidden="true"></i> End date *</label>
        <input id="end_date" name="end_date" type="date" value="{{ old('end_date', $tournament?->end_date?->format('Y-m-d')) }}" required>
        @error('end_date') <div class="field-error">{{ $message }}</div> @enderror
        <div class="field-hint">The end date cannot be earlier than the start date.</div>
    </div>

    <div class="field field-full @error('description') has-error @enderror">
        <label for="description"><i class="fa-solid fa-align-left" aria-hidden="true"></i> Description</label>
        <textarea id="description" name="description" placeholder="Add tournament rules, venue details, or other information...">{{ old('description', $tournament?->description) }}</textarea>
        @error('description') <div class="field-error">{{ $message }}</div> @enderror
    </div>
</div>
