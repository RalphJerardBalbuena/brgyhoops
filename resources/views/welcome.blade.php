<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">


    <title>BrgyHoops - Basketball Tournament Management System</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            color: #222;
        }

        /* Navigation */
        nav {
            background: #111827;
            color: white;
            padding: 20px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        nav h1 {
            font-size: 26px;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-left: 25px;
            font-size: 16px;
        }

        nav a:hover {
            text-decoration: underline;
        }

        /* Hero Section */
        .hero {
            text-align: center;
            padding: 90px 20px;
            background: white;
        }

        .hero h2 {
            font-size: 46px;
            margin-bottom: 20px;
        }

        .hero p {
            font-size: 20px;
            color: #666;
            margin-bottom: 30px;
        }

        .button {
            display: inline-block;
            background: #2563eb;
            color: white;
            padding: 14px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 16px;
        }

        .button:hover {
            background: #1d4ed8;
        }

        /* Features */
        .features {
            padding: 60px 40px;
            text-align: center;
        }

        .features h2 {
            font-size: 32px;
            margin-bottom: 40px;
        }

        .feature-container {
            display: flex;
            justify-content: center;
            gap: 25px;
            flex-wrap: wrap;
        }

        .feature-card {
            background: white;
            width: 280px;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .feature-card h3 {
            margin-bottom: 15px;
        }

        .feature-card p {
            color: #666;
            line-height: 1.6;
        }

        /* Footer */
        footer {
            background: #111827;
            color: white;
            text-align: center;
            padding: 25px;
            margin-top: 30px;
        }

        /* Mobile */
        @media (max-width: 700px) {

            nav {
                padding: 20px;
                flex-direction: column;
                gap: 15px;
            }

            nav a {
                margin-left: 10px;
                margin-right: 10px;
            }

            .hero h2 {
                font-size: 34px;
            }

            .hero p {
                font-size: 18px;
            }
        }
    </style>
</head>

<body>

    <!-- Navigation -->
    <nav>

        <h1>🏀 BrgyHoops</h1>

        <div>
            <a href="/">Home</a>
            <a href="/login">Login</a>
            <a href="/register">Register</a>
        </div>

    </nav>


    <!-- Hero Section -->
    <section class="hero">

        <h2>Welcome to BrgyHoops</h2>

        <p>
            Inter-Barangay Basketball Tournament Management System
        </p>

        <a href="/register" class="button">
            Get Started
        </a>

    </section>


    <!-- Features Section -->
    <section class="features">

        <h2>Tournament Information</h2>

        <div class="feature-container">

            <div class="feature-card">

                <h3>🏀 Teams</h3>

                <p>
                    View participating barangays and teams
                    in the basketball tournament.
                </p>

            </div>


            <div class="feature-card">

                <h3>📅 Game Schedule</h3>

                <p>
                    View upcoming basketball games,
                    schedules, dates, times, and venues.
                </p>

            </div>


            <div class="feature-card">

                <h3>🏆 Standings</h3>

                <p>
                    View tournament standings and
                    team win-loss records.
                </p>

            </div>


            <div class="feature-card">

                <h3>📊 Game Results</h3>

                <p>
                    View completed games, final scores,
                    and game history.
                </p>

            </div>

        </div>

    </section>


    <!-- Footer -->
    <footer>

        <p>
            BrgyHoops &copy; 2026
        </p>

        <p>
            Inter-Barangay Basketball Tournament Management System
        </p>

    </footer>

</body>
</html>