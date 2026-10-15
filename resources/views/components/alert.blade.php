{{--
    Inline validation errors for the standalone auth pages (login/register).
    Session flash messages are shown as toasts by partials.toasts instead.
--}}
@if ($errors->any())
    <div class="alert alert-error" role="alert">
        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
        <div>
            Please check the following:

            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

{{-- Homepage palette (red #e21b23 family) so alerts match on every auth page. --}}
<style>
    .alert {
        display: flex;
        align-items: flex-start;
        gap: 11px;
        margin-bottom: 22px;
        padding: 14px 16px;
        border: 1px solid transparent;
        border-left-width: 4px;
        border-radius: 10px;
        font-size: 14px;
        line-height: 1.6;
    }

    .alert > i {
        margin-top: 3px;
    }

    .alert-success {
        border-color: #b7edca;
        border-left-color: #167a3e;
        background: #ecfdf3;
        color: #176b3a;
    }

    .alert-error {
        border-color: #ffcaca;
        border-left-color: #e21b23;
        background: #fff0f1;
        color: #a51d25;
    }

    .alert-warning {
        border-color: #f6dfa6;
        border-left-color: #e0a100;
        background: #fff8e6;
        color: #8a5a00;
    }

    .alert-info {
        border-color: #e5e7eb;
        border-left-color: #111318;
        background: #f5f6f8;
        color: #111318;
    }

    .alert ul {
        margin-top: 6px;
        margin-left: 18px;
    }
</style>
