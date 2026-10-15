{{--
    Inline validation summary for forms.

    Session flash messages (success/error/warning/info) are shown as toasts by
    partials.toasts in layouts/app, so they are not repeated here.
--}}
@if ($errors->any())
    <div class="alert alert-error" role="alert">
        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
        <div>
            <strong>Please correct the following:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
