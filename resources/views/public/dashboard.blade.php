<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BrgyHoops - Tournament Dashboard</title>

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

        nav {
            background: #111827;
            color: white;
            padding: 20px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        nav h1 {
            font-size: 24px;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-left: 25px;
        }

        nav a:hover {
            text-decoration: underline;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .welcome {
            text-align: center;
            margin-bottom: 40px;
        }

        .welcome h2 {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .welcome p {
            color: #666;
            font-size: 18px;
        }

        .section {
            margin-bottom: 40px;
        }

        .section h3 {
            font-size: 26px;
            margin-bottom: 20px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .card h3 {
            margin-bottom: 10px;
        }

        .card p {
            color: #666;
            line-height: 1.5;
        }

        .game {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        .game strong {
            display: block;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .game p {
            color: #666;
            margin-bottom: 5px;
        }

        .announcement {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        .announcement h4 {
            margin-bottom: 8px;
        }

        .announcement p {
            color: #666;
            line-height: 1.5;
        }

        @media (max-width: 900px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            nav {
                padding: 20px;
                flex-direction: column;
                gap: 15px;
            }

            nav a {
                margin-left: 10px;
                margin-right: 10px;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .welcome h2 {
                font-size: 28px;
            }
        }
    </style>
</head>

<body>

    <nav>
        <h1>🏀 BrgyHoops</h1>

        <div>
            <a href="/">Home</a>
            <a href="/login">Login</a>
            <a href="/register">Register</a>
        </div>
    </nav>

    <div class="container">

        <div class="welcome">
            <h2>BrgyHoops Tournament</h2>
            <p>
                Inter-Barangay Basketball Tournament Information
            </p>
        </div>

        <div class="section">

            <h3>Tournament Information</h3>

            <div class="dashboard-grid">

                <div class="card">
                    <h3>🏀 Teams</h3>
                    <p>
                        View participating barangays and basketball teams.
                    </p>
                </div>

                <div class="card">
                    <h3>👥 Players</h3>
                    <p>
                        View registered players participating in the tournament.
                    </p>
                </div>

                <div class="card">
                    <h3>📅 Schedule</h3>
                    <p>
                        View upcoming games, dates, times, and venues.
                    </p>
                </div>

                <div class="card">
                    <h3>🏆 Standings</h3>
                    <p>
                        View team standings and win-loss records.
                    </p>
                </div>

            </div>

        </div>

        <div class="section">

            <h3>Upcoming Games</h3>

            <div class="game">

                <strong>
                    Barangay A vs Barangay B
                </strong>

                <p>Date: To be announced</p>
                <p>Time: To be announced</p>
                <p>Venue: To be announced</p>

            </div>

            <div class="game">

                <strong>
                    Barangay C vs Barangay D
                </strong>

                <p>Date: To be announced</p>
                <p>Time: To be announced</p>
                <p>Venue: To be announced</p>

            </div>

        </div>

        <div class="section">

            <h3>Game Results</h3>

            <div class="game">

                <strong>
                    Recent Game Result
                </strong>

                <p>
                    Game results will appear here after completed games.
                </p>

            </div>

        </div>

        <div class="section">

            <h3>Announcements</h3>

            <div class="announcement">

                <h4>Tournament Announcement</h4>

                <p>
                    Tournament announcements and important updates
                    will appear here.
                </p>

            </div>

        </div>

    </div>

</body>
</html>