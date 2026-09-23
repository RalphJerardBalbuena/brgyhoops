<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="{{ asset('css/brgyhoops.css') }}">

    <title>Create Account - BrgyHoops</title>

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

        .register-wrapper {
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
           REGISTRATION PANEL
        ========================= */

        .register-panel {
            padding: 50px 45px;
        }

        .register-header {
            margin-bottom: 30px;
        }

        .register-header h1 {
            font-size: 30px;
            margin-bottom: 8px;
            color: #111827;
        }

        .register-header p {
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

        .form-group input,
        .form-group select {
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

        .form-group input:focus,
        .form-group select:focus {
            border-color: #dc2626;
            background: white;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
        }

        .form-group input::placeholder {
            color: #9ca3af;
        }

        .form-group select {
            cursor: pointer;
        }

        /* =========================
           REGISTER BUTTON
        ========================= */

        .register-button {
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

        .register-button:hover {
            background: #b91c1c;
            color: white;

            transform: translateY(-1px);

            box-shadow:
                0 4px 10px rgba(220, 38, 38, 0.25);
        }

        .register-button:active {
            transform: translateY(0);
            box-shadow: none;
        }

        /* =========================
           LOGIN LINK
        ========================= */

        .login-text {
            text-align: center;
            margin-top: 22px;
            color: #6b7280;
            font-size: 14px;
        }

        .login-text a {
            color: #dc2626;
            font-weight: 700;
            text-decoration: none;

            transition:
                color 0.2s ease,
                text-decoration 0.2s ease;
        }

        .login-text a:hover {
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

            .register-wrapper {
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

            .register-panel {
                padding: 35px 30px;
            }
        }

        @media (max-width: 480px) {

            body {
                padding: 15px;
            }

            .register-wrapper {
                border-radius: 14px;
            }

            .branding {
                padding: 30px 25px;
            }

            .register-panel {
                padding: 30px 25px;
            }

            .logo {
                font-size: 32px;
            }

            .register-header h1 {
                font-size: 26px;
            }
        }
    </style>
</head>

<body>

<div class="register-wrapper">

    <!-- Branding -->

    <section class="branding">

        <div class="logo">
            Brgy<span>Hoops</span>
        </div>

        <h2>
            Join your community on the court.
        </h2>

        <p>
            Create your account and get connected with your
            barangay basketball tournament.
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


    <!-- Registration Form -->

    <section class="register-panel">

        <div class="register-header">

            <h1>Create an Account</h1>

            <p>
                Fill in your information to get started.
            </p>

        </div>


        {{-- Alert messages --}}

        @include('components.alert')


        <form action="/register" method="POST">

            @csrf


            <!-- Full Name -->

            <div class="form-group">

                <label for="name">
                    Full Name
                </label>

                <div class="input-wrapper">

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="Enter your full name"
                        required
                    >

                </div>

                @error('name')
                    <div class="error-message">
                        {{ $message }}
                    </div>
                @enderror

            </div>


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
                        placeholder="Create a password"
                        required
                    >

                </div>

                @error('password')
                    <div class="error-message">
                        {{ $message }}
                    </div>
                @enderror

            </div>


            <!-- Confirm Password -->

            <div class="form-group">

                <label for="password_confirmation">
                    Confirm Password
                </label>

                <div class="input-wrapper">

                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        placeholder="Confirm your password"
                        required
                    >

                </div>

                @error('password_confirmation')
                    <div class="error-message">
                        {{ $message }}
                    </div>
                @enderror

            </div>


            <!-- Account Role -->

            <div class="form-group">

                <label for="role">
                    Account Role
                </label>

                <div class="input-wrapper">

                    <select
                        id="role"
                        name="role"
                        required
                    >

                        <option value="">
                            Select your role
                        </option>

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

                </div>

                @error('role')
                    <div class="error-message">
                        {{ $message }}
                    </div>
                @enderror

            </div>


            <!-- Submit -->

            <button
                type="submit"
                class="register-button"
            >
                Create Account
            </button>

        </form>


        <!-- Login Link -->

        <div class="login-text">

            Already have an account?

            <a href="/login">
                Sign in
            </a>

        </div>


        <!-- Home Link -->

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