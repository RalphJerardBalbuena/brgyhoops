
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#111318">
    <title>Login | BrgyHoops</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800;900&family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/brgyhoops-ui.css') }}">
    <script src="{{ asset('js/brgyhoops-ui.js') }}" defer></script>

    <style>
        :root {
            --red: #e21b23;
            --red-dark: #bd111a;
            --black: #111318;
            --muted: #626976;
            --white: #ffffff;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
            background:
                radial-gradient(ellipse at 15% 100%, rgba(226,27,35,.18), transparent 40%),
                radial-gradient(ellipse at 90% 0%, rgba(226,27,35,.08), transparent 40%),
                #111318;
            font-family: 'DM Sans', sans-serif;
            color: var(--black);
        }

        .page-shell {
            width: 100%;
            max-width: 1190px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            color: var(--white);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--white);
            text-decoration: none;
        }

        .brand-icon {
            width: 46px;
            height: 46px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: var(--red);
            color: var(--white);
            font-size: 22px;
        }

        .brand-name {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 32px;
            font-weight: 900;
            letter-spacing: -.5px;
        }

        .brand-name span {
            color: var(--red);
        }

        .topbar-caption {
            color: #c5c8d0;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .login-card {
            display: grid;
            grid-template-columns: 1fr .94fr;
            min-height: 690px;
            overflow: hidden;
            border-radius: 18px;
            background: var(--white);
            box-shadow: 0 30px 100px rgba(0,0,0,.48);
        }

        .court-panel {
            position: relative;
            isolation: isolate;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 690px;
            padding: 46px;
            overflow: hidden;
            color: var(--white);
            background:
                linear-gradient(180deg, rgba(10,12,16,.35), rgba(10,12,16,.93)),
                url('https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=1400&q=85') center / cover;
        }

        .court-panel::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: -1;
            background:
                linear-gradient(135deg, rgba(15,17,22,.78), rgba(15,17,22,.3) 50%, rgba(15,17,22,.92)),
                repeating-linear-gradient(
                    135deg,
                    transparent 0,
                    transparent 40px,
                    rgba(255,255,255,.025) 41px,
                    transparent 42px
                );
        }

        .court-panel::after {
            content: "";
            position: absolute;
            z-index: -1;
            right: -110px;
            bottom: -160px;
            width: 370px;
            height: 370px;
            border: 3px solid rgba(255,255,255,.13);
            border-radius: 50%;
            box-shadow:
                0 0 0 36px rgba(255,255,255,.035),
                0 0 0 74px rgba(255,255,255,.025);
        }

        .court-top,
        .court-bottom {
            position: relative;
            z-index: 1;
        }

        .court-badge {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 10px 14px;
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 5px;
            background: rgba(0,0,0,.25);
            color: var(--white);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .court-badge span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--red);
        }

        .hero-logo {
            margin-top: 65px;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: clamp(76px, 7vw, 108px);
            font-weight: 900;
            line-height: .88;
            letter-spacing: -2px;
            text-transform: uppercase;
            text-shadow: 0 5px 25px rgba(0,0,0,.3);
        }

        .hero-logo span {
            display: block;
            color: var(--red);
        }

        .hero-tagline {
            max-width: 390px;
            margin-top: 30px;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 34px;
            font-weight: 800;
            line-height: 1.12;
            text-transform: uppercase;
        }

        .hero-description {
            max-width: 390px;
            margin-top: 15px;
            color: #e0e2e7;
            font-size: 15px;
            line-height: 1.9;
        }

        .feature-list {
            display: grid;
            gap: 19px;
            margin-top: 34px;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.6;
        }

        .feature-icon {
            display: grid;
            place-items: center;
            width: 40px;
            height: 40px;
            flex-shrink: 0;
            border: 1px solid rgba(226,27,35,.7);
            border-radius: 50%;
            background: rgba(0,0,0,.3);
            color: var(--red);
            font-size: 17px;
        }

        .court-bottom {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 38px;
            padding-top: 21px;
            border-top: 1px solid rgba(255,255,255,.18);
            color: #c7cbd3;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
        }

        .court-bottom strong {
            color: var(--white);
        }

        .login-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 56px 48px;
            background:
                radial-gradient(circle at 100% 0%, rgba(226,27,35,.035), transparent 27%),
                var(--white);
        }

        .mobile-brand {
            display: none;
        }

        .section-label {
            margin-bottom: 14px;
            color: var(--red);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .login-heading {
            margin-bottom: 32px;
        }

        .login-heading h1 {
            margin-bottom: 12px;
            color: #15171c;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 56px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -.5px;
        }

        .login-heading h1 span {
            color: var(--red);
        }

        .login-heading p {
            color: var(--muted);
            font-size: 15px;
            line-height: 1.8;
        }

        .alert {
            padding: 14px 16px;
            margin-bottom: 22px;
            border: 1px solid transparent;
            border-radius: 8px;
            font-size: 14px;
            line-height: 1.7;
        }

        .alert-success {
            color: #176b3a;
            background: #ecfdf3;
            border-color: #b7edca;
        }

        .alert-error,
        .alert-danger {
            color: #a51d25;
            background: #fff0f0;
            border-color: #ffcaca;
        }

        .form-group {
            margin-bottom: 23px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #292d35;
            font-size: 14px;
            font-weight: 800;
        }

        .input-wrapper {
            position: relative;
        }

        .field-icon {
            position: absolute;
            top: 50%;
            left: 16px;
            transform: translateY(-50%);
            color: #737b87;
            font-size: 17px;
            pointer-events: none;
        }

        .form-group input {
            width: 100%;
            min-height: 57px;
            padding: 15px 16px 15px 48px;
            border: 1px solid #d9dde4;
            border-radius: 8px;
            outline: none;
            background: #f8f9fb;
            color: #171a20;
            font-family: inherit;
            font-size: 15px;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }

        .form-group input::placeholder {
            color: #858c98;
            font-size: 14px;
        }

        .form-group input:focus {
            border-color: var(--red);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(226,27,35,.09);
        }

        .error-message {
            margin-top: 8px;
            color: var(--red-dark);
            font-size: 14px;
            line-height: 1.6;
        }

        .password-row {
            display: flex;
            justify-content: flex-end;
            margin-top: -4px;
            margin-bottom: 25px;
        }

        .forgot-link {
            color: var(--red-dark);
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
        }

        .forgot-link:hover,
        .register-text a:hover {
            text-decoration: underline;
        }

        .login-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            min-height: 59px;
            padding: 15px 18px;
            border: 0;
            border-radius: 8px;
            background: var(--red);
            color: var(--white);
            font-family: inherit;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 7px 16px rgba(226,27,35,.2);
            transition: background .2s, transform .2s, box-shadow .2s;
        }

        .login-button:hover {
            transform: translateY(-2px);
            background: var(--red-dark);
            box-shadow: 0 10px 22px rgba(226,27,35,.25);
        }

        .button-ball {
            font-size: 18px;
        }

        .or-divider {
            display: flex;
            align-items: center;
            gap: 13px;
            margin: 27px 0;
            color: #737985;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .or-divider::before,
        .or-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #e1e4e9;
        }

        .register-box {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 6px;
            padding: 17px 12px;
            border: 1px solid #dce0e6;
            border-radius: 8px;
            background: #f8f9fb;
            color: #555e6b;
            font-size: 13px;
            line-height: 1.7;
            text-align: center;
        }

        .register-text a {
            margin-left: 3px;
            color: var(--red-dark);
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
        }

        .home-link {
            display: block;
            margin-top: 26px;
            color: #626976;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
        }

        .home-link:hover {
            color: #171a20;
        }

        .page-footer {
            margin-top: 20px;
            color: #9297a1;
            font-size: 12px;
            text-align: center;
        }

        @media (max-width: 900px) {
            .login-card {
                grid-template-columns: .9fr 1.1fr;
            }

            .court-panel {
                padding: 36px 30px;
            }

            .hero-logo {
                font-size: 76px;
            }

            .hero-tagline {
                font-size: 28px;
            }

            .hero-description {
                font-size: 14px;
            }

            .feature {
                font-size: 13px;
            }

            .login-panel {
                padding: 44px 32px;
            }

            .login-heading h1 {
                font-size: 50px;
            }
        }

        @media (max-width: 680px) {
            body {
                padding: 20px 14px;
            }

            .page-shell {
                max-width: 460px;
            }

            .topbar {
                margin-bottom: 17px;
            }

            .topbar-caption {
                display: none;
            }

            .login-card {
                grid-template-columns: 1fr;
                min-height: auto;
                border-radius: 14px;
            }

            .court-panel {
                display: none;
            }

            .login-panel {
                padding: 32px 26px;
            }

            .mobile-brand {
                display: flex;
                align-items: center;
                gap: 11px;
                margin-bottom: 32px;
            }

            .mobile-brand .brand-icon {
                width: 40px;
                height: 40px;
                font-size: 19px;
            }

            .mobile-brand .brand-name {
                color: #171a20;
                font-size: 30px;
            }

            .login-heading h1 {
                font-size: 48px;
            }

            .login-heading p {
                font-size: 15px;
            }

            .form-group label {
                font-size: 14px;
            }

            .form-group input {
                min-height: 56px;
                font-size: 15px;
            }

            .feature {
                font-size: 14px;
            }
        }

        @media (max-width: 360px) {
            .login-panel {
                padding: 26px 19px;
            }

            .login-heading h1 {
                font-size: 43px;
            }

            .form-group input::placeholder {
                font-size: 13px;
            }

            .register-box {
                flex-direction: column;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                transition-duration: .01ms !important;
            }
        }
    </style>
</head>

<body>
    @include('partials.toasts')

    <main class="page-shell">
        <header class="topbar">
            <a href="/" class="brand" aria-label="BrgyHoops home">
                <span class="brand-icon">
                    <i class="fa-solid fa-basketball" aria-hidden="true"></i>
                </span>
                <span class="brand-name">Brgy<span>Hoops</span></span>
            </a>

            <p class="topbar-caption">Your community. Your court. Your game.</p>
        </header>

        <div class="login-card">
            <section class="court-panel">
                <div class="court-top">
                    <div class="court-badge">
                        <span></span>
                        Barangay basketball
                    </div>

                    <div class="hero-logo">
                        BRGY
                        <span>HOOPS.</span>
                    </div>

                    <h2 class="hero-tagline">
                        Welcome back<br>to the court.
                    </h2>

                    <p class="hero-description">
                        Sign in to manage your account and stay connected
                        with your barangay basketball tournament.
                    </p>

                    <div class="feature-list">
                        <div class="feature">
                            <span class="feature-icon">
                                <i class="fa-solid fa-users" aria-hidden="true"></i>
                            </span>
                            <span>Manage teams and tournament activities</span>
                        </div>

                        <div class="feature">
                            <span class="feature-icon">
                                <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                            </span>
                            <span>Track players, games, and schedules</span>
                        </div>

                        <div class="feature">
                            <span class="feature-icon">
                                <i class="fa-solid fa-trophy" aria-hidden="true"></i>
                            </span>
                            <span>Follow your community's tournament journey</span>
                        </div>
                    </div>
                </div>

                <div class="court-bottom">
                    <strong>PLAY HARD.</strong>
                    <span>STAND TOGETHER.</span>
                </div>
            </section>

            <section class="login-panel">
                <div class="mobile-brand">
                    <span class="brand-icon">
                        <i class="fa-solid fa-basketball" aria-hidden="true"></i>
                    </span>
                    <span class="brand-name">Brgy<span>Hoops</span></span>
                </div>

                <header class="login-heading">
                    <div class="section-label"></div>
                    <h1>Welcome <span>Back.</span></h1>
                    <p>Sign in to continue to BrgyHoops.</p>
                </header>

                @include('components.alert')

                <form action="/login" method="POST">
                    @csrf

                    <div class="form-group">
                        <label for="email">Email Address</label>

                        <div class="input-wrapper">
                            <span class="field-icon">
                                <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                            </span>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="Enter your email address"
                                autocomplete="email"
                                required
                                autofocus
                            >
                        </div>

                        @error('email')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>

                        <div class="input-wrapper">
                            <span class="field-icon">
                                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                            </span>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required
                            >
                        </div>

                        @error('password')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="password-row">
                        <a href="/forgot-password" class="forgot-link">
                            Forgot your password?
                        </a>
                    </div>

                    <button type="submit" class="login-button" data-loading-text="Signing in…">
                        <i class="fa-solid fa-basketball button-ball" aria-hidden="true"></i>
                        <span>Sign In</span>
                        <span aria-hidden="true">→</span>
                    </button>
                </form>

                <div class="or-divider">Join the game</div>

                <div class="register-box">
                    <span>Don't have an account?</span>
                    <span class="register-text">
                        <a href="/register">Create an account →</a>
                    </span>
                </div>

                <a href="/" class="home-link">← Back to Home</a>
            </section>
        </div>

        <footer class="page-footer">
            © {{ date('Y') }} BrgyHoops · Made for the love of the game.
        </footer>
    </main>
</body>
</html>