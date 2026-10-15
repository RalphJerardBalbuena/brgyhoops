<div class="field @error($name) has-error @enderror">

    <label for="{{ $name }}">
        {{ $label }}
    </label>

    <input
        type="{{ $type ?? 'text' }}"
        id="{{ $name }}"
        name="{{ $name }}"
        value="{{ old($name) }}"
        placeholder="{{ $placeholder ?? '' }}"
        class="input"
        {{ $required ?? true ? 'required' : '' }}
    >

    @error($name)
        <small class="field-error">
            {{ $message }}
        </small>
    @enderror

</div>
