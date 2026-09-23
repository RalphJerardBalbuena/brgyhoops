<div class="form-group">

    <label for="{{ $name }}">
        {{ $label }}
    </label>

    <input
        type="{{ $type ?? 'text' }}"
        id="{{ $name }}"
        name="{{ $name }}"
        value="{{ old($name) }}"
        placeholder="{{ $placeholder ?? '' }}"
        class="form-input"
        {{ $required ?? true ? 'required' : '' }}
    >

    @error($name)
        <small class="form-error">
            {{ $message }}
        </small>
    @enderror

</div>

<style>
    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
    }

    .form-input {
        width: 100%;
        padding: 12px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 15px;
    }

    .form-input:focus {
        outline: none;
        border-color: #2563eb;
    }

    .form-error {
        display: block;
        margin-top: 5px;
        color: #dc2626;
    }
</style>