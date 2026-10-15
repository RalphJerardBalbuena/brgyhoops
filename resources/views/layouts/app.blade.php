<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @hasSection('title')
        <title>@yield('title') | BrgyHoops</title>
    @else
        <title>BrgyHoops | Inter-Barangay Basketball</title>
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700;800;900&family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/brgyhoops-app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/brgyhoops-ui.css') }}">
    <script src="{{ asset('js/brgyhoops-ui.js') }}" defer></script>

    @stack('styles')
</head>

<body>

    @include('partials.navbar')

    @include('partials.toasts')

    <main>
        @yield('content')
    </main>

    @include('partials.footer')

    @stack('scripts')

</body>
</html>
