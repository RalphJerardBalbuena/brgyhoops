
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account - BrgyHoops</title>

    <link rel="stylesheet" href="{{ asset('css/brgyhoops.css') }}">
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
            --border: #dfe2e7;
            --field-bg: #f8f9fb;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            padding: 35px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'DM Sans', Arial, sans-serif;
            color: var(--black);
            background:
                radial-gradient(circle at 10% 15%, rgba(226, 27, 35, .17), transparent 28%),
                radial-gradient(circle at 90% 85%, rgba(226, 27, 35, .12), transparent 30%),
                #101116;
        }

        .register-wrapper {
            width: 100%;
            max-width: 1120px;
            display: grid;
            grid-template-columns: .92fr 1.08fr;
            background: var(--white);
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 28px 80px rgba(0, 0, 0, .38);
        }

        /* Branding panel */

        .branding {
            position: relative;
            isolation: isolate;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 760px;
            padding: 58px 46px;
            overflow: hidden;
            color: var(--white);
            background:
                linear-gradient(180deg, rgba(12, 13, 17, .72), rgba(12, 13, 17, .94)),
                url('https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=1200&q=85') center / cover no-repeat,
                #191b22;
        }

        .branding::before {
            content: "";
            position: absolute;
            z-index: -1;
            inset: 0;
            background: linear-gradient(145deg, rgba(226, 27, 35, .45), transparent 55%);
        }

        .court-lines {
            position: absolute;
            z-index: -1;
            width: 330px;
            height: 330px;
            right: -150px;
            bottom: -110px;
            border: 2px solid rgba(255, 255, 255, .15);
            border-radius: 50%;
        }

        .court-lines::before {
            content: "";
            position: absolute;
            width: 155px;
            height: 155px;
            top: 85px;
            left: 85px;
            border: 2px solid rgba(255, 255, 255, .13);
            border-radius: 50%;
        }

        .brand-content {
            position: relative;
            z-index: 1;
        }

        .brand-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 58px;
            height: 58px;
            margin-bottom: 22px;
            border-radius: 16px;
            background: var(--red);
            box-shadow: 0 8px 25px rgba(226, 27, 35, .3);
            font-size: 27px;
        }

        .logo {
            margin-bottom: 25px;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: clamp(66px, 6vw, 88px);
            font-weight: 900;
            line-height: .9;
            letter-spacing: -1.5px;
            text-transform: uppercase;
        }

        .logo span {
            display: block;
            color: var(--red);
        }

        .brand-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 15px;
            color: #ffb7ba;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .brand-eyebrow::before {
            content: "";
            width: 25px;
            height: 3px;
            background: var(--red);
        }

        .branding h2 {
            max-width: 400px;
            margin-bottom: 18px;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: clamp(34px, 3.2vw, 43px);
            font-weight: 800;
            line-height: 1.05;
            text-transform: uppercase;
        }

        .branding p {
            max-width: 390px;
            color: rgba(255, 255, 255, .78);
            font-size: 15px;
            line-height: 1.85;
        }

        .features {
            display: grid;
            gap: 17px;
            margin-top: 38px;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 14px;
            color: rgba(255, 255, 255, .94);
            font-size: 14px;
            font-weight: 600;
        }

        .feature-icon {
            display: flex;
            flex: 0 0 40px;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border: 1px solid rgba(255, 255, 255, .16);
            border-radius: 12px;
            background: rgba(255, 255, 255, .09);
            color: #ffb7ba;
            font-size: 16px;
        }

        .brand-footer {
            position: absolute;
            right: 46px;
            bottom: 28px;
            left: 46px;
            padding-top: 17px;
            border-top: 1px solid rgba(255, 255, 255, .16);
            color: rgba(255, 255, 255, .55);
            font-size: 11px;
            letter-spacing: .5px;
        }

        /* Registration panel */

        .register-panel {
            padding: 48px 54px;
        }

        .register-header {
            margin-bottom: 30px;
        }

        .register-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            color: var(--red);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .register-kicker i {
            font-size: 13px;
        }

        .register-header h1 {
            margin-bottom: 9px;
            color: var(--black);
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 48px;
            font-weight: 800;
            line-height: 1;
            text-transform: uppercase;
        }

        .register-header p {
            color: var(--muted);
            font-size: 15px;
            line-height: 1.7;
        }

        /* Alerts */

        .alert {
            margin-bottom: 22px;
            padding: 14px 16px;
            border: 1px solid #fecaca;
            border-radius: 10px;
            background: #fff1f2;
            color: #991b1b;
            font-size: 14px;
            line-height: 1.6;
        }

        /* Form */

        .register-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            column-gap: 18px;
        }

        .form-group {
            min-width: 0;
            margin-bottom: 19px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 9px;
            color: #30343c;
            font-size: 14px;
            font-weight: 800;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            top: 50%;
            left: 16px;
            color: #8a909c;
            font-size: 16px;
            pointer-events: none;
            transform: translateY(-50%);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            min-height: 55px;
            padding: 14px 15px 14px 45px;
            border: 1px solid var(--border);
            border-radius: 10px;
            outline: none;
            background: var(--field-bg);
            color: var(--black);
            font-family: 'DM Sans', Arial, sans-serif;
            font-size: 14px;
            transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
        }

        .form-group input::placeholder {
            color: #969ba5;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--red);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(226, 27, 35, .09);
        }

        .form-group select {
            cursor: pointer;
            appearance: none;
            padding-right: 40px;
        }

        .select-arrow {
            position: absolute;
            top: 50%;
            right: 16px;
            color: #777e89;
            pointer-events: none;
            transform: translateY(-50%);
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border: 0;
            background: transparent;
            color: #777e89;
            cursor: pointer;
            transform: translateY(-50%);
        }

        .password-toggle:hover {
            color: var(--red);
        }

        .password-input {
            padding-right: 48px !important;
        }

        .error-message {
            margin-top: 7px;
            color: var(--red-dark);
            font-size: 12px;
            font-weight: 600;
            line-height: 1.5;
        }

        .register-button {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 11px;
            width: 100%;
            min-height: 58px;
            margin-top: 3px;
            border: 0;
            border-radius: 10px;
            background: var(--red);
            color: var(--white);
            font-family: 'DM Sans', Arial, sans-serif;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 7px 18px rgba(226, 27, 35, .19);
            transition: background .2s ease, transform .2s ease, box-shadow .2s ease;
        }

        .register-button:hover {
            background: var(--red-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 23px rgba(226, 27, 35, .25);
        }

        .register-button:active {
            transform: translateY(0);
        }

        .login-text {
            margin-top: 24px;
            color: var(--muted);
            font-size: 14px;
            text-align: center;
        }

        .login-text a {
            margin-left: 4px;
            color: var(--red);
            font-weight: 800;
            text-decoration: none;
        }

        .login-text a:hover {
            color: var(--red-dark);
            text-decoration: underline;
        }

        .home-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
            color: #858b96;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: color .2s ease;
        }

        .home-link:hover {
            color: var(--red);
        }

        .form-note {
            grid-column: 1 / -1;
            margin: -2px 0 18px;
            color: #7c828d;
            font-size: 12px;
            line-height: 1.6;
        }

        @media (max-width: 950px) {
            .register-wrapper {
                max-width: 680px;
                grid-template-columns: 1fr;
            }

            .branding {
                min-height: auto;
                padding: 40px;
            }

            .logo {
                font-size: 68px;
            }

            .branding h2 {
                max-width: 500px;
                font-size: 36px;
            }

            .branding p {
                max-width: 520px;
            }

            .features {
                grid-template-columns: 1fr;
                gap: 12px;
                margin-top: 25px;
            }

            .feature {
                font-size: 13px;
            }

            .brand-footer {
                display: none;
            }

            .register-panel {
                padding: 42px 40px;
            }
        }

        @media (max-width: 520px) {
            body {
                align-items: flex-start;
                padding: 14px;
            }

            .register-wrapper {
                border-radius: 16px;
            }

            .branding {
                padding: 30px 25px;
            }

            .brand-mark {
                width: 48px;
                height: 48px;
                margin-bottom: 18px;
                border-radius: 13px;
                font-size: 22px;
            }

            .logo {
                margin-bottom: 20px;
                font-size: 58px;
            }

            .branding h2 {
                font-size: 31px;
            }

            .branding p {
                font-size: 14px;
            }

            .features {
                gap: 10px;
            }

            .feature-icon {
                flex-basis: 34px;
                width: 34px;
                height: 34px;
                border-radius: 10px;
                font-size: 14px;
            }

            .register-panel {
                padding: 32px 24px;
            }

            .register-header {
                margin-bottom: 25px;
            }

            .register-header h1 {
                font-size: 41px;
            }

            .register-header p {
                font-size: 14px;
            }

            .register-form {
                grid-template-columns: 1fr;
            }

            .form-group,
            .form-group.full-width,
            .register-button,
            .form-note {
                grid-column: 1;
            }

            .form-group {
                margin-bottom: 17px;
            }

            .form-group input,
            .form-group select {
                min-height: 54px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition-duration: .01ms !important;
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
            }
        }
    </style>
</head>

<body>
    @include('partials.toasts')

    <main class="register-wrapper">
        <!-- Branding panel -->
        <section class="branding">
            <div class="court-lines"></div>

            <div class="brand-content">
                <div class="brand-mark" aria-hidden="true">
                    <i class="fa-solid fa-basketball"></i>
                </div>

                <div class="logo">
                    Brgy<span>Hoops</span>
                </div>

                <div class="brand-eyebrow">Your community. Your court.</div>

                <h2>Join your community on the court.</h2>

                <p>
                    Create your account and get connected with your barangay
                    basketball tournament. Be part of the game, on and off the court.
                </p>

                <div class="features">
                    <div class="feature">
                        <div class="feature-icon" aria-hidden="true">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <span>Manage your tournament activities</span>
                    </div>

                    <div class="feature">
                        <div class="feature-icon" aria-hidden="true">
                            <i class="fa-solid fa-people-group"></i>
                        </div>
                        <span>Connect with teams and players</span>
                    </div>

                    <div class="feature">
                        <div class="feature-icon" aria-hidden="true">
                            <i class="fa-solid fa-calendar-days"></i>
                        </div>
                        <span>Stay updated with schedules and results</span>
                    </div>
                </div>
            </div>

            <div class="brand-footer">
                <i class="fa-solid fa-basketball"></i>
                &nbsp; BRGYHOOPS COMMUNITY BASKETBALL
            </div>
        </section>

        <!-- Registration form -->
        <section class="register-panel">
            <header class="register-header">
                <div class="register-kicker">
                    <i class="fa-solid fa-user-plus"></i>
                    Get in the game
                </div>

                <h1>Create Account</h1>

                <p>Enter your details below to join BrgyHoops.</p>
            </header>

            @include('components.alert')

            <form action="/register" method="POST" class="register-form">
                @csrf

                <!-- Full name -->
                <div class="form-group full-width">
                    <label for="name">Full Name</label>

                    <div class="input-wrapper">
                        <i class="input-icon fa-regular fa-user" aria-hidden="true"></i>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="Enter your full name"
                            autocomplete="name"
                            required
                            autofocus
                        >
                    </div>

                    @error('name')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Email -->
                <div class="form-group full-width">
                    <label for="email">Email Address</label>

                    <div class="input-wrapper">
                        <i class="input-icon fa-regular fa-envelope" aria-hidden="true"></i>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="Enter your email address"
                            autocomplete="email"
                            required
                        >
                    </div>

                    @error('email')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password">Password</label>

                    <div class="input-wrapper">
                        <i class="input-icon fa-solid fa-lock" aria-hidden="true"></i>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="password-input"
                            placeholder="Create a password"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            data-password-toggle="password"
                            aria-label="Show password"
                            aria-pressed="false"
                        >
                            <i class="fa-regular fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>

                    @error('password')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Confirm password -->
                <div class="form-group">
                    <label for="password_confirmation">Confirm Password</label>

                    <div class="input-wrapper">
                        <i class="input-icon fa-solid fa-shield-halved" aria-hidden="true"></i>

                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="password-input"
                            placeholder="Confirm your password"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            data-password-toggle="password_confirmation"
                            aria-label="Show confirm password"
                            aria-pressed="false"
                        >
                            <i class="fa-regular fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>

                    @error('password_confirmation')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Account role -->
                <div class="form-group full-width">
                    <label for="role">Account Role</label>

                    <div class="input-wrapper">
                        <i class="input-icon fa-solid fa-users" aria-hidden="true"></i>

                        <select id="role" name="role" required>
                            <option value="">Select your role</option>

                            <option
                                value="team_manager"
                                {{ old('role') == 'team_manager' ? 'selected' : '' }}
                            >
                                Team Manager
                            </option>

                            <option
                                value="player"
                                {{ old('role') == 'player' ? 'selected' : '' }}
                            >
                                Player
                            </option>
                        </select>

                        <i class="select-arrow fa-solid fa-chevron-down" aria-hidden="true"></i>
                    </div>

                    @error('role')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <p class="form-note">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    Choose the role that matches how you will participate in the tournament.
                </p>

                <button type="submit" class="register-button" data-loading-text="Creating account…">
                    <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                    Create Account
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </button>
            </form>

            <div class="login-text">
                Already have an account?
                <a href="/login">Sign in</a>
            </div>

            <a class="home-link" href="/">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Back to Home
            </a>
        </section>
    </main>

    <script>
        document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                const inputId = button.getAttribute('data-password-toggle');
                const input = document.getElementById(inputId);
                const icon = button.querySelector('i');

                if (!input || !icon) {
                    return;
                }

                const showPassword = input.type === 'password';

                input.type = showPassword ? 'text' : 'password';
                button.setAttribute('aria-pressed', String(showPassword));
                button.setAttribute(
                    'aria-label',
                    showPassword ? 'Hide password' : 'Show password'
                );

                icon.classList.toggle('fa-eye', !showPassword);
                icon.classList.toggle('fa-eye-slash', showPassword);
            });
        });
    </script>
</body>
</html>