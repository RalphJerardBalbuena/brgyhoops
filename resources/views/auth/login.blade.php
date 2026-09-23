<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="{{ asset('css/brgyhoops.css') }}">

    <title>Login - BrgyHoops</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #111827, #1f2937);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 20px;
            color: #111827;
        }

        .login-wrapper {
            width: 100%;
            max-width: 1000px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
        }

        /* =========================
           BRANDING PANEL
        ========================= */

        .branding {
            background: linear-gradient(160deg, #dc2626, #991b1b);
            color: white;
            padding: 55px 45px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .branding::before {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            border: 35px solid rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            top: -90px;
            right: -90px;
        }

        .branding::after {
            content: "";
            position: absolute;
            width: 180px;
            height: 180px;
            border: 25px solid rgba(255, 255, 255, 0.06);
            border-radius: 50%;
            bottom: -70px;
            left: -70px;
        }

        .logo {
            position: relative;
            z-index: 1;
            font-size: 38px;
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 15px;
        }

        .logo span {
            color: #fbbf24;
        }

        .branding h2 {
            position: relative;
            z-index: 1;
            font-size: 27px;
            line-height: 1.2;
            margin-bottom: 15px;
        }

        .branding p {
            position: relative;
            z-index: 1;
            color: rgba(255, 255, 255, 0.85);
            line-height: 1.7;
            max-width: 390px;
        }

        .features {
            position: relative;
            z-index: 1;
            margin-top: 30px;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            color: rgba(255, 255, 255, 0.92);
        }

        .feature-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        /* =========================
           LOGIN PANEL
        ========================= */

        .login-panel {
            padding: 50px 45px;
        }

        .login-header {
            margin-bottom: 30px;
        }

        .login-header h1 {
            font-size: 30px;
            margin-bottom: 8px;
            color: #111827;
        }

        .login-header p {
            color: #6b7280;
            font-size: 15px;
        }

        /* =========================
           FORM
        ========================= */

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 700;
            color: #374151;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-group input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 15px;
            color: #111827;
            background: #f9fafb;
            outline: none;

            transition:
                border-color 0.2s ease,
                background 0.2s ease,
                box-shadow 0.2s ease;
        }

        .form-group input:focus {
            border-color: #dc2626;
            background: white;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
        }

        .form-group input::placeholder {
            color: #9ca3af;
        }

        /* =========================
           FORGOT PASSWORD
        ========================= */

        .password-row {
            display: flex;
            justify-content: flex-end;
            margin-top: -10px;
            margin-bottom: 20px;
        }

        .forgot-link {
            color: #dc2626;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;

            transition:
                color 0.2s ease,
                text-decoration 0.2s ease;
        }

        .forgot-link:hover {
            color: #b91c1c;
            text-decoration: underline;
        }

        /* =========================
           LOGIN BUTTON
        ========================= */

        .login-button {
            width: 100%;
            border: none;
            border-radius: 10px;
            padding: 14px;
            margin-top: 5px;

            background: #dc2626;
            color: white;

            font-size: 16px;
            font-weight: 700;

            cursor: pointer;

            transition:
                background 0.2s ease,
                transform 0.2s ease,
                box-shadow 0.2s ease;
        }

        .login-button:hover {
            background: #b91c1c;
            color: white;

            transform: translateY(-1px);

            box-shadow:
                0 4px 10px rgba(220, 38, 38, 0.25);
        }

        .login-button:active {
            transform: translateY(0);
            box-shadow: none;
        }

        /* =========================
           REGISTER LINK
        ========================= */

        .register-text {
            text-align: center;
            margin-top: 22px;
            color: #6b7280;
            font-size: 14px;
        }

        .register-text a {
            color: #dc2626;
            font-weight: 700;
            text-decoration: none;

            transition:
                color 0.2s ease,
                text-decoration 0.2s ease;
        }

        .register-text a:hover {
            color: #b91c1c;
            text-decoration: underline;
        }

        /* =========================
           HOME LINK
        ========================= */

        .home-link {
            display: block;
            text-align: center;
            margin-top: 12px;
            color: #9ca3af;
            font-size: 13px;
            text-decoration: none;

            transition: color 0.2s ease;
        }

        .home-link:hover {
            color: #4b5563;
        }

        /* =========================
           ALERT
        ========================= */

        .alert {
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        /* =========================
           VALIDATION ERRORS
        ========================= */

        .error-message {
            margin-top: 6px;
            color: #dc2626;
            font-size: 13px;
        }

        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 800px) {

            .login-wrapper {
                grid-template-columns: 1fr;
                max-width: 520px;
            }

            .branding {
                padding: 35px 30px;
            }

            .branding h2 {
                font-size: 23px;
            }

            .features {
                display: none;
            }

            .login-panel {
                padding: 35px 30px;
            }
        }

        @media (max-width: 480px) {

            body {
                padding: 15px;
            }

            .login-wrapper {
                border-radius: 14px;
            }

            .branding {
                padding: 30px 25px;
            }

            .login-panel {
                padding: 30px 25px;
            }

            .logo {
                font-size: 32px;
            }

            .login-header h1 {
                font-size: 26px;
            }
        }
    </style>
</head>

<body>

<div class="login-wrapper">

    <!-- Branding -->

    <section class="branding">

        <div class="logo">
            Brgy<span>Hoops</span>
        </div>

        <h2>
            Welcome back to the court.
        </h2>

        <p>
            Sign in to manage your account and stay connected
            with your barangay basketball tournament.
        </p>

        <div class="features">

            <div class="feature">
                <div class="feature-icon">✓</div>
                <span>Manage your tournament activities</span>
            </div>

            <div class="feature">
                <div class="feature-icon">✓</div>
                <span>Track teams, players, and schedules</span>
            </div>

            <div class="feature">
                <div class="feature-icon">✓</div>
                <span>Stay updated with tournament results</span>
            </div>

        </div>

    </section>


    <!-- Login Form -->

    <section class="login-panel">

        <div class="login-header">

            <h1>Welcome Back</h1>

            <p>
                Sign in to continue to BrgyHoops.
            </p>

        </div>


        {{-- Existing alert component --}}

        @include('components.alert')


        <form action="/login" method="POST">

            @csrf


            <!-- Email -->

            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <div class="input-wrapper">

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Enter your email address"
                        required
                    >

                </div>

                @error('email')
                    <div class="error-message">
                        {{ $message }}
                    </div>
                @enderror

            </div>


            <!-- Password -->

            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <div class="input-wrapper">

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                    >

                </div>

                @error('password')
                    <div class="error-message">
                        {{ $message }}
                    </div>
                @enderror

            </div>


            <!-- Forgot Password -->

            <div class="password-row">

                <a
                    href="/forgot-password"
                    class="forgot-link"
                >
                    Forgot your password?
                </a>

            </div>


            <!-- Submit -->

            <button
                type="submit"
                class="login-button"
            >
                Sign In
            </button>

        </form>


        <!-- Register -->

        <div class="register-text">

            Don't have an account?

            <a href="/register">
                Create an account
            </a>

        </div>


        <!-- Home -->

        <a
            class="home-link"
            href="/"
        >
            ← Back to Home
        </a>

    </section>

</div>

</body>
</html>