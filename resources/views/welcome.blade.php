<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="{{ asset('css/brgyhoops.css') }}">

    <title>BrgyHoops - Basketball Tournament Management System</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            color: #111827;
        }

        /* =========================
           Navigation
        ========================= */

        .navbar {
            background: #111827;
            min-height: 72px;
            padding: 0 6%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.12);
        }

        .brand {
            color: white;
            text-decoration: none;
            font-size: 27px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .brand span {
            color: #dc2626;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-links a {
            color: #d1d5db;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            transition: 0.2s ease;
        }

        .nav-links a:hover {
            color: white;
            background: #1f2937;
        }

        .nav-links a.active {
            color: white;
            background: #dc2626;
        }

        .nav-links .register-link {
            background: white;
            color: #111827;
            margin-left: 5px;
        }

        .nav-links .register-link:hover {
            background: #f3f4f6;
            color: #111827;
        }

        /* =========================
           Hero
        ========================= */

        .hero {
            background:
                linear-gradient(
                    135deg,
                    rgba(17, 24, 39, 0.97),
                    rgba(127, 29, 29, 0.95)
                );

            color: white;
            text-align: center;
            padding: 110px 20px 100px;
        }

        .hero-content {
            max-width: 850px;
            margin: 0 auto;
        }

        .hero h1 {
            font-size: 52px;
            line-height: 1.1;
            margin-bottom: 20px;
            letter-spacing: -1px;
        }

        .hero h1 span {
            color: #f87171;
        }

        .hero p {
            max-width: 700px;
            margin: 0 auto 35px;
            color: #e5e7eb;
            font-size: 19px;
            line-height: 1.7;
        }

        .hero-button {
            display: inline-block;
            background: #dc2626;
            color: white;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 9px;
            font-size: 16px;
            font-weight: 700;
            transition: 0.2s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .hero-button:hover {
            background: #b91c1c;
            transform: translateY(-2px);
        }

        /* =========================
           Tournament Information
        ========================= */

        .features {
            padding: 80px 6%;
            background: #f4f6f8;
        }

        .section-header {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 45px;
        }

        .section-header h2 {
            font-size: 34px;
            color: #111827;
            margin-bottom: 12px;
        }

        .section-header p {
            color: #6b7280;
            font-size: 16px;
            line-height: 1.6;
        }

        .feature-container {
            max-width: 1150px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 22px;
        }

        .feature-card {
            background: white;
            padding: 30px 25px;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.06);
            transition: 0.2s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .feature-number {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #fee2e2;
            color: #dc2626;
            font-weight: 800;
            margin-bottom: 20px;
        }

        .feature-card h3 {
            color: #111827;
            font-size: 19px;
            margin-bottom: 12px;
        }

        .feature-card p {
            color: #6b7280;
            line-height: 1.6;
            font-size: 14px;
        }

        /* =========================
           Call To Action
        ========================= */

        .cta {
            background: white;
            padding: 70px 20px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }

        .cta h2 {
            font-size: 30px;
            margin-bottom: 12px;
            color: #111827;
        }

        .cta p {
            color: #6b7280;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .cta-button {
            display: inline-block;
            background: #dc2626;
            color: white;
            text-decoration: none;
            padding: 13px 26px;
            border-radius: 8px;
            font-weight: 700;
            transition: 0.2s ease;
        }

        .cta-button:hover {
            background: #b91c1c;
        }

        /* =========================
           Footer
        ========================= */

        footer {
            background: #111827;
            color: white;
            text-align: center;
            padding: 30px 20px;
        }

        footer .footer-brand {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        footer .footer-brand span {
            color: #dc2626;
        }

        footer p {
            color: #9ca3af;
            font-size: 13px;
            line-height: 1.6;
        }

        /* =========================
           Responsive Design
        ========================= */

        @media (max-width: 950px) {

            .feature-container {
                grid-template-columns: repeat(2, 1fr);
            }

            .hero h1 {
                font-size: 44px;
            }
        }

        @media (max-width: 700px) {

            .navbar {
                padding: 15px 20px;
                flex-direction: column;
                gap: 12px;
            }

            .brand {
                font-size: 24px;
            }

            .nav-links {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
            }

            .nav-links a {
                padding: 8px 12px;
                font-size: 14px;
            }

            .hero {
                padding: 75px 20px;
            }

            .hero h1 {
                font-size: 36px;
            }

            .hero p {
                font-size: 17px;
            }

            .features {
                padding: 60px 20px;
            }

            .section-header h2 {
                font-size: 28px;
            }

            .feature-container {
                grid-template-columns: 1fr;
            }

            .cta {
                padding: 55px 20px;
            }
        }

        @media (max-width: 450px) {

            .nav-links {
                gap: 2px;
            }

            .nav-links a {
                padding: 7px 9px;
                font-size: 13px;
            }

            .hero h1 {
                font-size: 31px;
            }
        }
    </style>
</head>

<body>

    <!-- =========================
         Navigation
    ========================= -->

    <nav class="navbar">

        <a href="/" class="brand">
            Brgy<span>Hoops</span>
        </a>

        <div class="nav-links">

            <a href="/" class="active">
                Home
            </a>

            <a href="/login">
                Login
            </a>

            <a href="/register" class="register-link">
                Register
            </a>

        </div>

    </nav>


    <!-- =========================
         Hero Section
    ========================= -->

    <section class="hero">

        <div class="hero-content">

            <h1>
                Welcome to <span>BrgyHoops</span>
            </h1>

            <p>
                Inter-Barangay Basketball Tournament
                Management System
            </p>

            <a href="/register" class="hero-button">
                Get Started
            </a>

        </div>

    </section>


    <!-- =========================
         Tournament Information
    ========================= -->

    <section class="features">

        <div class="section-header">

            <h2>
                Tournament Information
            </h2>

            <p>
                Stay informed about participating teams,
                game schedules, standings, and tournament results.
            </p>

        </div>


        <div class="feature-container">


            <!-- Teams -->

            <div class="feature-card">

                <div class="feature-number">
                    01
                </div>

                <h3>
                    Teams
                </h3>

                <p>
                    View participating barangays and
                    teams in the basketball tournament.
                </p>

            </div>


            <!-- Schedule -->

            <div class="feature-card">

                <div class="feature-number">
                    02
                </div>

                <h3>
                    Game Schedule
                </h3>

                <p>
                    View upcoming basketball games,
                    schedules, dates, times, and venues.
                </p>

            </div>


            <!-- Standings -->

            <div class="feature-card">

                <div class="feature-number">
                    03
                </div>

                <h3>
                    Standings
                </h3>

                <p>
                    View tournament standings and
                    team win-loss records.
                </p>

            </div>


            <!-- Results -->

            <div class="feature-card">

                <div class="feature-number">
                    04
                </div>

                <h3>
                    Game Results
                </h3>

                <p>
                    View completed games, final scores,
                    and game history.
                </p>

            </div>

        </div>

    </section>


    <!-- =========================
         Call To Action
    ========================= -->

    <section class="cta">

        <h2>
            Ready to join BrgyHoops?
        </h2>

        <p>
            Create your account and become part of
            the community basketball tournament.
        </p>

        <a href="/register" class="cta-button">
            Create an Account
        </a>

    </section>


    <!-- =========================
         Footer
    ========================= -->

    <footer>

        <div class="footer-brand">
            Brgy<span>Hoops</span>
        </div>

        <p>
            Inter-Barangay Basketball Tournament Management System
        </p>

        <p>
            &copy; 2026 BrgyHoops. All rights reserved.
        </p>

    </footer>

</body>
</html>