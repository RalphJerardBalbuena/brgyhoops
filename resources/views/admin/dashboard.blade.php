<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BrgyHoops - Administrator Dashboard</title>

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
            font-size: 24px;
        }

        nav a {
            color: white;
            text-decoration: none;
        }

        nav a:hover {
            text-decoration: underline;
        }

        /* Main Content */
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

        /* Dashboard Cards */
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

        /* Mobile */
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

    <!-- Navigation -->
    <nav>

        <h1>🏀 BrgyHoops Admin</h1>

        <a href="/">Back to Home</a>

    </nav>


    <!-- Main Content -->
    <div class="container">

        <div class="welcome">

            <h2>Administrator Dashboard</h2>

            <p>
                Manage the BrgyHoops Inter-Barangay Basketball Tournament.
            </p>

        </div>


        <!-- Dashboard Cards -->
        <div class="dashboard-grid">

            <div class="card">

                <h3>🏀 Tournaments</h3>

                <p>
                    Create and manage basketball tournaments.
                </p>

            </div>


            <div class="card">

                <h3>👥 Teams</h3>

                <p>
                    Manage participating teams and barangays.
                </p>

            </div>


            <div class="card">

                <h3>🧑 Players</h3>

                <p>
                    Review and manage player information.
                </p>

            </div>


            <div class="card">

                <h3>✅ Approvals</h3>

                <p>
                    Approve or reject teams and players.
                </p>

            </div>


            <div class="card">

                <h3>📅 Game Schedule</h3>

                <p>
                    Manage game dates, times, and schedules.
                </p>

            </div>


            <div class="card">

                <h3>📍 Venues</h3>

                <p>
                    Manage tournament game venues.
                </p>

            </div>


            <div class="card">

                <h3>📊 Game Results</h3>

                <p>
                    Record final scores and game results.
                </p>

            </div>


            <div class="card">

                <h3>🏆 Standings</h3>

                <p>
                    View tournament standings and records.
                </p>

            </div>


            <div class="card">

                <h3>📢 Announcements</h3>

                <p>
                    Create and manage tournament announcements.
                </p>

            </div>

        </div>

    </div>

</body>
</html>