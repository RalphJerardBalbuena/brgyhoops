
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BrgyHoops | Inter-Barangay Basketball</title>
    <meta name="description" content="BrgyHoops is your community basketball tournament management platform for teams, schedules, standings, and game results.">

    <link rel="stylesheet" href="{{ asset('css/brgyhoops.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700;800;900&family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --red: #e21b23;
            --red-dark: #bd111a;
            --black: #111318;
            --dark: #191b22;
            --muted: #626976;
            --white: #ffffff;
            --light: #f5f6f8;
            --border: #e5e7eb;
            --container: 1200px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 100px;
        }

        body {
            font-family: 'DM Sans', Arial, sans-serif;
            background: var(--light);
            color: var(--black);
            -webkit-font-smoothing: antialiased;
        }

        a {
            -webkit-tap-highlight-color: transparent;
        }

        .container {
            width: min(var(--container), calc(100% - 64px));
            margin: 0 auto;
        }

        /* Navigation */

        .navbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            min-height: 78px;
            background: rgba(17, 19, 24, .97);
            border-bottom: 1px solid rgba(255, 255, 255, .08);
            backdrop-filter: blur(16px);
        }

        .nav-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 78px;
            gap: 30px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 11px;
            color: var(--white);
            text-decoration: none;
            flex-shrink: 0;
        }

        /* FIXED: White basketball icon on red background */
        .brand-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 43px;
            height: 43px;
            border-radius: 12px;
            background: var(--red);
            color: #ffffff !important;
            font-size: 21px;
            box-shadow: 0 5px 16px rgba(226, 27, 35, .22);
        }

        .brand-icon i {
            color: #ffffff !important;
            -webkit-text-fill-color: #ffffff !important;
        }

        .brand-name {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 34px;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -.4px;
            text-transform: uppercase;
            color: #ffffff;
        }

        .brand-name span {
            color: var(--red);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .nav-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 43px;
            padding: 10px 17px;
            border-radius: 8px;
            color: #c7c9d0;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            transition: color .2s ease, background .2s ease, transform .2s ease;
        }

        .nav-links a:hover {
            color: var(--white);
            background: rgba(255, 255, 255, .08);
        }

        .nav-links .nav-register {
            margin-left: 7px;
            padding: 12px 20px;
            background: var(--red);
            color: var(--white);
        }

        .nav-links .nav-register:hover {
            background: var(--red-dark);
            transform: translateY(-1px);
        }

        /* Hero */

        .hero {
            position: relative;
            isolation: isolate;
            display: flex;
            align-items: center;
            min-height: 640px;
            overflow: hidden;
            color: var(--white);
            background: var(--black);
        }

        .hero-background {
            position: absolute;
            z-index: -2;
            inset: 0;
            background:
                url('https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=2200&q=90')
                center 43% / cover no-repeat;
            transform: scale(1.02);
        }

        .hero::before {
            content: "";
            position: absolute;
            z-index: -1;
            inset: 0;
            background:
                linear-gradient(90deg, rgba(12, 13, 17, .97) 0%, rgba(12, 13, 17, .86) 42%, rgba(12, 13, 17, .38) 100%),
                linear-gradient(0deg, rgba(12, 13, 17, .6), transparent 55%);
        }

        .hero::after {
            content: "";
            position: absolute;
            z-index: -1;
            top: 0;
            right: 0;
            width: 9px;
            height: 100%;
            background: var(--red);
        }

        .hero-content {
            width: min(780px, 100%);
            padding: 90px 0 105px;
        }

        .hero-label {
            display: inline-flex;
            align-items: center;
            gap: 11px;
            margin-bottom: 25px;
            color: #ffb8bb;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 2.4px;
            text-transform: uppercase;
        }

        .hero-label i {
            color: var(--red);
            font-size: 16px;
        }

        .hero h1 {
            max-width: 850px;
            margin-bottom: 25px;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: clamp(66px, 8.2vw, 104px);
            font-weight: 900;
            line-height: .91;
            letter-spacing: -1px;
            text-transform: uppercase;
        }

        .hero h1 span {
            display: block;
            color: var(--red);
        }

        .hero-description {
            max-width: 590px;
            margin-bottom: 35px;
            color: #d2d4da;
            font-size: 17px;
            line-height: 1.9;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 11px;
            min-height: 54px;
            padding: 15px 24px;
            border: 1px solid transparent;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
            transition: background .2s ease, transform .2s ease, border-color .2s ease;
        }

        .button-primary {
            background: var(--red);
            color: var(--white);
            box-shadow: 0 7px 22px rgba(226, 27, 35, .23);
        }

        .button-primary:hover {
            background: var(--red-dark);
            transform: translateY(-2px);
        }

        .button-outline {
            border-color: rgba(255, 255, 255, .28);
            background: rgba(255, 255, 255, .05);
            color: var(--white);
        }

        .button-outline:hover {
            border-color: rgba(255, 255, 255, .6);
            background: rgba(255, 255, 255, .1);
            transform: translateY(-2px);
        }

        .hero-bottom {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 25px;
            margin-top: 47px;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, .16);
        }

        .hero-highlight {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #d6d7dc;
            font-size: 13px;
            font-weight: 600;
        }

        .hero-highlight i {
            color: #ff777d;
            font-size: 16px;
        }

        /* Information strip */

        .info-strip {
            position: relative;
            z-index: 2;
            background: var(--red);
            color: var(--white);
        }

        .info-strip-inner {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
        }

        .info-item {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 13px;
            min-height: 92px;
            padding: 20px;
        }

        .info-item + .info-item {
            border-left: 1px solid rgba(255, 255, 255, .23);
        }

        .info-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 43px;
            height: 43px;
            flex-shrink: 0;
            border-radius: 12px;
            background: rgba(255, 255, 255, .15);
            font-size: 18px;
        }

        .info-item strong {
            display: block;
            margin-bottom: 4px;
            font-size: 14px;
            font-weight: 800;
        }

        .info-item span {
            display: block;
            color: rgba(255, 255, 255, .83);
            font-size: 12px;
            line-height: 1.5;
        }

        /* Tournament features */

        .features {
            padding: 105px 0;
            background: var(--light);
        }

        .section-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 30px;
            margin-bottom: 45px;
        }

        .section-heading-copy {
            max-width: 650px;
        }

        .section-kicker {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 13px;
            color: var(--red);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1.8px;
            text-transform: uppercase;
        }

        .section-kicker::before {
            content: "";
            width: 24px;
            height: 3px;
            background: var(--red);
        }

        .section-heading h2 {
            margin-bottom: 13px;
            color: var(--black);
            font-family: 'Barlow Condensed', sans-serif;
            font-size: clamp(39px, 5vw, 54px);
            font-weight: 800;
            line-height: 1;
            text-transform: uppercase;
        }

        .section-heading p {
            color: var(--muted);
            font-size: 15px;
            line-height: 1.85;
        }

        .section-side-note {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 16px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--white);
            color: #444955;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .section-side-note i {
            color: var(--red);
            font-size: 16px;
        }

        .feature-container {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
        }

        .feature-card {
            position: relative;
            display: flex;
            flex-direction: column;
            min-height: 285px;
            padding: 28px 24px;
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: var(--white);
            box-shadow: 0 4px 16px rgba(17, 19, 24, .035);
            transition: transform .25s ease, border-color .25s ease, box-shadow .25s ease;
        }

        .feature-card:hover {
            transform: translateY(-6px);
            border-color: rgba(226, 27, 35, .4);
            box-shadow: 0 16px 35px rgba(17, 19, 24, .09);
        }

        .feature-card::after {
            content: "";
            position: absolute;
            right: -32px;
            bottom: -32px;
            width: 100px;
            height: 100px;
            border: 15px solid rgba(226, 27, 35, .045);
            border-radius: 50%;
            pointer-events: none;
        }

        .feature-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 25px;
        }

        .feature-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 54px;
            height: 54px;
            border-radius: 13px;
            background: #fff0f1;
            color: var(--red);
            font-size: 22px;
        }

        .feature-number {
            color: #c7c9d0;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 25px;
            font-weight: 800;
        }

        .feature-card h3 {
            margin-bottom: 11px;
            color: var(--black);
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 28px;
            font-weight: 800;
            line-height: 1.05;
            text-transform: uppercase;
        }

        .feature-card p {
            margin-bottom: 23px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.85;
        }

        .feature-bottom {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: auto;
            color: var(--red);
            font-size: 12px;
            font-weight: 800;
        }

        .feature-bottom i {
            transition: transform .2s ease;
        }

        .feature-card:hover .feature-bottom i {
            transform: translateX(4px);
        }

        /* Community banner */

        .community {
            padding: 0 0 100px;
            background: var(--light);
        }

        .community-panel {
            position: relative;
            isolation: isolate;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 35px;
            padding: 55px 58px;
            overflow: hidden;
            border-radius: 18px;
            background: var(--black);
            color: var(--white);
        }

        .community-panel::before {
            content: "";
            position: absolute;
            z-index: -1;
            top: -150px;
            right: 120px;
            width: 350px;
            height: 350px;
            border: 45px solid rgba(226, 27, 35, .13);
            border-radius: 50%;
        }

        .community-copy {
            max-width: 680px;
        }

        .community-copy .section-kicker {
            color: #ff8c91;
        }

        .community-copy h2 {
            margin-bottom: 15px;
            font-family: 'Barlow Condensed', sans-serif;
            font-size: clamp(38px, 4.6vw, 52px);
            font-weight: 800;
            line-height: 1;
            text-transform: uppercase;
        }

        .community-copy p {
            max-width: 590px;
            color: #c5c7cf;
            font-size: 14px;
            line-height: 1.9;
        }

        .community-action {
            flex-shrink: 0;
        }

        /* Footer */

        .footer {
            padding: 48px 0 25px;
            background: #0b0c10;
            color: var(--white);
        }

        .footer-main {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 35px;
            padding-bottom: 35px;
        }

        .footer-brand {
            display: inline-block;
            margin-bottom: 14px;
            color: var(--white);
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 35px;
            font-weight: 900;
            line-height: 1;
            text-decoration: none;
            text-transform: uppercase;
        }

        .footer-brand span {
            color: var(--red);
        }

        .footer-description {
            max-width: 420px;
            color: #9699a3;
            font-size: 13px;
            line-height: 1.9;
        }

        .footer-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .footer-links a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 13px;
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: 8px;
            color: #d3d4da;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: background .2s ease, color .2s ease;
        }

        .footer-links a:hover {
            background: rgba(255, 255, 255, .08);
            color: var(--white);
        }

        .footer-links a i {
            color: #ff777d;
        }

        .footer-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding-top: 23px;
            border-top: 1px solid rgba(255, 255, 255, .1);
            color: #81848e;
            font-size: 12px;
            line-height: 1.7;
        }

        .footer-bottom i {
            color: var(--red);
        }

        /* Responsive */

        @media (max-width: 1050px) {
            .feature-container {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .feature-card {
                min-height: 250px;
            }

            .section-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .community-panel {
                padding: 45px;
            }
        }

        @media (max-width: 760px) {
            .container {
                width: min(100% - 40px, var(--container));
            }

            .nav-inner {
                min-height: 72px;
                gap: 14px;
                flex-wrap: wrap;
                padding-top: 14px;
                padding-bottom: 14px;
            }

            .brand-name {
                font-size: 30px;
            }

            .brand-icon {
                width: 39px;
                height: 39px;
                font-size: 18px;
            }

            .nav-links {
                width: 100%;
                justify-content: flex-start;
                flex-wrap: wrap;
            }

            .nav-links a {
                min-height: 39px;
                padding: 9px 12px;
                font-size: 13px;
            }

            .nav-links .nav-register {
                margin-left: auto;
            }

            .hero {
                min-height: 570px;
            }

            .hero::before {
                background:
                    linear-gradient(90deg, rgba(12, 13, 17, .94), rgba(12, 13, 17, .68)),
                    linear-gradient(0deg, rgba(12, 13, 17, .5), transparent);
            }

            .hero-content {
                padding: 75px 0 70px;
            }

            .hero h1 {
                font-size: clamp(59px, 11vw, 82px);
            }

            .hero-description {
                font-size: 15px;
            }

            .hero-bottom {
                gap: 16px 22px;
            }

            .hero-highlight {
                font-size: 12px;
            }

            .info-strip-inner {
                grid-template-columns: 1fr;
                padding: 7px 0;
            }

            .info-item {
                justify-content: flex-start;
                min-height: 76px;
                padding: 14px 0;
            }

            .info-item + .info-item {
                border-top: 1px solid rgba(255, 255, 255, .23);
                border-left: 0;
            }

            .features {
                padding: 75px 0;
            }

            .section-heading h2 {
                font-size: 44px;
            }

            .community {
                padding-bottom: 75px;
            }

            .community-panel {
                align-items: flex-start;
                flex-direction: column;
                padding: 38px 30px;
            }

            .community-copy h2 {
                font-size: 42px;
            }

            .footer-main {
                flex-direction: column;
            }

            .footer-bottom {
                align-items: flex-start;
                flex-direction: column;
                gap: 8px;
            }
        }

        @media (max-width: 480px) {
            .container {
                width: calc(100% - 32px);
            }

            .brand-name {
                font-size: 28px;
            }

            .nav-links {
                gap: 4px;
            }

            .nav-links a {
                gap: 6px;
                padding: 9px 10px;
                font-size: 12px;
            }

            .nav-links .nav-register {
                padding: 10px 12px;
            }

            .hero {
                min-height: auto;
            }

            .hero-content {
                padding: 68px 0;
            }

            .hero-label {
                font-size: 10px;
                letter-spacing: 1.6px;
            }

            .hero h1 {
                font-size: clamp(53px, 14vw, 68px);
            }

            .hero-description {
                font-size: 14px;
                line-height: 1.8;
            }

            .hero-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .button {
                width: 100%;
                min-height: 52px;
            }

            .hero-bottom {
                align-items: flex-start;
                flex-direction: column;
                gap: 14px;
                margin-top: 33px;
            }

            .feature-container {
                grid-template-columns: 1fr;
            }

            .feature-card {
                min-height: 235px;
                padding: 25px;
            }

            .section-heading h2 {
                font-size: 39px;
            }

            .section-heading p {
                font-size: 14px;
            }

            .community-panel {
                padding: 32px 23px;
                border-radius: 14px;
            }

            .community-copy h2 {
                font-size: 38px;
            }

            .community-copy p {
                font-size: 13px;
            }

            .footer-links {
                flex-direction: column;
                align-items: stretch;
                width: 100%;
            }

            .footer-links a {
                width: 100%;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition-duration: .01ms !important;
                animation-duration: .01ms !important;
            }
        }
    </style>
</head>

<body>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-inner">

            <a href="/" class="brand" aria-label="BrgyHoops home">
                <span class="brand-icon">
                    <i class="fa-solid fa-basketball" aria-hidden="true"></i>
                </span>

                <span class="brand-name">Brgy<span>Hoops</span></span>
            </a>

            <div class="nav-links">
                <a href="/" aria-current="page">
                    <i class="fa-solid fa-house" aria-hidden="true"></i>
                    Home
                </a>

                <a href="/login">
                    <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                    Login
                </a>

                <a href="/register" class="nav-register">
                    <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                    Register
                </a>
            </div>

        </div>
    </nav>

    <!-- Hero -->
    <main>
        <section class="hero">
            <div class="hero-background" aria-hidden="true"></div>

            <div class="container">
                <div class="hero-content">

                    <div class="hero-label">
                        <i class="fa-solid fa-basketball" aria-hidden="true"></i>
                        Inter-Barangay Basketball
                    </div>

                    <h1>
                        One Community.
                        <span>One Court.</span>
                        One Game.
                    </h1>

                    <p class="hero-description">
                        Bringing barangays together through basketball.
                        Keep your tournament organized with a central place
                        for teams, game schedules, standings, and results.
                    </p>

                    <div class="hero-actions">
                        <a href="/register" class="button button-primary">
                            Get Started
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>

                        <a href="#tournament-info" class="button button-outline">
                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                            Explore BrgyHoops
                        </a>
                    </div>

                    <div class="hero-bottom">
                        <div class="hero-highlight">
                            <i class="fa-solid fa-people-group" aria-hidden="true"></i>
                            Community-driven
                        </div>

                        <div class="hero-highlight">
                            <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
                            Organized schedules
                        </div>

                        <div class="hero-highlight">
                            <i class="fa-solid fa-trophy" aria-hidden="true"></i>
                            Every game counts
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- Information strip -->
        <section class="info-strip" aria-label="BrgyHoops highlights">
            <div class="container info-strip-inner">

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fa-solid fa-people-group" aria-hidden="true"></i>
                    </div>
                    <div>
                        <strong>Team Management</strong>
                        <span>Keep teams and players organized</span>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                    </div>
                    <div>
                        <strong>Game Schedules</strong>
                        <span>Stay informed about upcoming games</span>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">
                        <i class="fa-solid fa-chart-simple" aria-hidden="true"></i>
                    </div>
                    <div>
                        <strong>Standings & Results</strong>
                        <span>Follow the tournament progress</span>
                    </div>
                </div>

            </div>
        </section>

        <!-- Tournament information -->
        <section class="features" id="tournament-info">
            <div class="container">

                <div class="section-heading">
                    <div class="section-heading-copy">
                        <div class="section-kicker">Everything in one place</div>

                        <h2>Your Tournament.<br>Made Simple.</h2>

                        <p>
                            Follow the action throughout the tournament.
                            BrgyHoops brings essential basketball tournament
                            information together in one convenient platform.
                        </p>
                    </div>

                    <div class="section-side-note">
                        <i class="fa-solid fa-basketball" aria-hidden="true"></i>
                        Built for barangay basketball
                    </div>
                </div>

                <div class="feature-container">

                    <article class="feature-card">
                        <div class="feature-top">
                            <div class="feature-icon">
                                <i class="fa-solid fa-people-group" aria-hidden="true"></i>
                            </div>
                            <span class="feature-number">01</span>
                        </div>

                        <h3>Teams & Players</h3>

                        <p>
                            Keep participating barangays, teams, and player
                            information organized throughout the tournament.
                        </p>

                        <div class="feature-bottom">
                            <span>Team management</span>
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </div>
                    </article>

                    <article class="feature-card">
                        <div class="feature-top">
                            <div class="feature-icon">
                                <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                            </div>
                            <span class="feature-number">02</span>
                        </div>

                        <h3>Game Schedule</h3>

                        <p>
                            Keep track of basketball matchups, game dates,
                            starting times, and venues.
                        </p>

                        <div class="feature-bottom">
                            <span>Follow upcoming games</span>
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </div>
                    </article>

                    <article class="feature-card">
                        <div class="feature-top">
                            <div class="feature-icon">
                                <i class="fa-solid fa-ranking-star" aria-hidden="true"></i>
                            </div>
                            <span class="feature-number">03</span>
                        </div>

                        <h3>Team Standings</h3>

                        <p>
                            Follow team rankings and win-loss records as the
                            competition moves forward.
                        </p>

                        <div class="feature-bottom">
                            <span>Track tournament progress</span>
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </div>
                    </article>

                    <article class="feature-card">
                        <div class="feature-top">
                            <div class="feature-icon">
                                <i class="fa-solid fa-trophy" aria-hidden="true"></i>
                            </div>
                            <span class="feature-number">04</span>
                        </div>

                        <h3>Game Results</h3>

                        <p>
                            Review completed games, final scores, and
                            tournament results in one place.
                        </p>

                        <div class="feature-bottom">
                            <span>Review game outcomes</span>
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </div>
                    </article>

                </div>
            </div>
        </section>

        <!-- Call to action -->
        <section class="community">
            <div class="container">
                <div class="community-panel">

                    <div class="community-copy">
                        <div class="section-kicker">Your game starts here</div>

                        <h2>Ready to join the action?</h2>

                        <p>
                            Create your BrgyHoops account and become part of
                            your community basketball tournament. Whether you're
                            a player or a team manager, there's a place for you.
                        </p>
                    </div>

                    <div class="community-action">
                        <a href="/register" class="button button-primary">
                            Create Your Account
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>

                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">

            <div class="footer-main">
                <div>
                    <a href="/" class="footer-brand">Brgy<span>Hoops</span></a>

                    <p class="footer-description">
                        Inter-Barangay Basketball Tournament Management System.
                        Bringing communities together, one game at a time.
                    </p>
                </div>

                <div class="footer-links">
                    <a href="/">
                        <i class="fa-solid fa-house" aria-hidden="true"></i>
                        Home
                    </a>

                    <a href="/login">
                        <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                        Login
                    </a>

                    <a href="/register">
                        <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                        Register
                    </a>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; {{ date('Y') }} BrgyHoops. All rights reserved.</p>

                <p>
                    Made for the love of the game
                    <i class="fa-solid fa-basketball" aria-hidden="true"></i>
                </p>
            </div>

        </div>
    </footer>

</body>
</html>