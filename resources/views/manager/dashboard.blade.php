<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BrgyHoops - Team Manager</title>

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
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .welcome {
            margin-bottom: 30px;
        }

        .welcome h2 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .welcome p {
            color: #666;
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
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .card h3 {
            margin-bottom: 10px;
        }

        .card p {
            color: #666;
            line-height: 1.5;
        }

        .section {
            margin-top: 30px;
        }

        .section h3 {
            margin-bottom: 15px;
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
            margin-bottom: 8px;
        }

        @media (max-width: 900px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            nav {
                padding: 20px;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <nav>
        <h1>🏀 BrgyHoops</h1>

        <a href="/">Back to Home</a>
    </nav>


    <div class="container">

        <div class="welcome">
            <h2>Team Manager Dashboard</h2>

            <p>
                Manage your team, players, and tournament activities.
            </p>
        </div>


        <div class="dashboard-grid">

            <div class="card">
                <h3>🏀 My Team</h3>

                <p>
                    View and manage your team information.
                </p>
            </div>


            <div class="card">
                <h3>👥 Players</h3>

                <p>
                    Manage the players registered under your team.
                </p>
            </div>


            <div class="card">
                <h3>📅 Schedule</h3>

                <p>
                    View your team's upcoming games.
                </p>
            </div>


            <div class="card">
                <h3>📊 Results</h3>

                <p>
                    View your team's game results.
                </p>
            </div>

        </div>


        <div class="section">

            <h3>Upcoming Games</h3>

            <div class="game">

                <strong>Barangay A vs Barangay B</strong>

                <p>Date: To be announced</p>
                <p>Time: To be announced</p>
                <p>Venue: To be announced</p>

            </div>

        </div>


        <div class="section">

            <h3>Team Status</h3>

            <div class="game">

                <strong>Registration Status</strong>

                <p>
                    Your team's registration status will appear here.
                </p>

            </div>

        </div>

    </div>

</body>
</html>