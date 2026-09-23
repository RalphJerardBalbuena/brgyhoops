@if (session('success'))
    <div class="alert alert-success">
        ✅ {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-error">
        ❌ {{ session('error') }}
    </div>
@endif

@if (session('warning'))
    <div class="alert alert-warning">
        ⚠️ {{ session('warning') }}
    </div>
@endif

@if (session('info'))
    <div class="alert alert-info">
        ℹ️ {{ session('info') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-error">
        ❌ Please check the following:

        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<style>
    .alert {
        padding: 15px 20px;
        margin-bottom: 20px;
        border-radius: 8px;
        font-size: 15px;
    }

    .alert-success {
        background: #dcfce7;
        color: #166534;
    }

    .alert-error {
        background: #fee2e2;
        color: #991b1b;
    }

    .alert-warning {
        background: #fef3c7;
        color: #92400e;
    }

    .alert-info {
        background: #dbeafe;
        color: #1e40af;
    }

    .alert ul {
        margin-top: 8px;
        margin-left: 20px;
    }
</style>