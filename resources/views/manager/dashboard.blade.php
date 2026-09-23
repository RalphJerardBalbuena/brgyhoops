<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="{{ asset('css/brgyhoops.css') }}">

    <title>BrgyHoops - Team Manager Dashboard</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            color: #1f2937;
            min-height: 100vh;
        }

        /* Navigation */
        nav {
            background: #111827;
            color: white;
            padding: 18px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand {
            font-size: 24px;
            font-weight: bold;
        }

        .brand span {
            color: #dc2626;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 15px;
        }

        .nav-links a:hover {
            color: #ef4444;
        }

        .home-button {
            border: 1px solid #dc2626;
            padding: 9px 16px;
            border-radius: 6px;
            transition: 0.2s ease;
        }

        .home-button:hover {
            background: #dc2626;
            color: white !important;
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        /* Welcome */
        .welcome {
            margin-bottom: 35px;
        }

        .welcome h1 {
            font-size: 32px;
            margin-bottom: 8px;
            color: #111827;
        }

        .welcome p {
            color: #6b7280;
            font-size: 16px;
            line-height: 1.6;
        }

        /* Dashboard Cards */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 35px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        .card h3 {
            color: #374151;
            font-size: 17px;
            margin-bottom: 12px;
        }

        .card p {
            color: #6b7280;
            line-height: 1.6;
            font-size: 14px;
        }

        /* Team Information */
        .team-info {
            margin-bottom: 35px;
        }

        .section-header {
            margin-bottom: 18px;
        }

        .section-header h2 {
            font-size: 22px;
            color: #111827;
            margin-bottom: 5px;
        }

        .section-header p {
            color: #6b7280;
            font-size: 14px;
        }

        .team-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .team-card h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #111827;
        }

        .team-card p {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 5px;
        }

        /* Team Status */
        .status-section {
            margin-bottom: 35px;
        }

        .status-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .status-label {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .status {
            display: inline-block;
            background: #f3f4f6;
            color: #374151;
            padding: 8px 14px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }

        /* Player Roster */
        .roster-section {
            margin-bottom: 35px;
        }

        .roster-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .roster-placeholder {
            text-align: center;
            padding: 30px 20px;
            color: #6b7280;
        }

        .roster-placeholder h3 {
            color: #374151;
            margin-bottom: 8px;
            font-size: 17px;
        }

        .roster-placeholder p {
            font-size: 14px;
        }

        /* Upcoming Games */
        .games-section {
            margin-bottom: 35px;
        }

        .game-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .game-card h3 {
            color: #111827;
            font-size: 17px;
            margin-bottom: 12px;
        }

        .game-details {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.8;
        }

        /* Quick Actions */
        .actions-section {
            margin-bottom: 35px;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .action-button {
            display: block;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 18px;
            color: #111827;
            text-decoration: none;
            font-weight: bold;
            transition: 0.2s ease;
        }

        .action-button:hover {
            border-color: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 5px 14px rgba(0, 0, 0, 0.08);
        }

        .action-button span {
            display: block;
            color: #dc2626;
            font-size: 13px;
            font-weight: normal;
            margin-top: 7px;
        }

        /* Recent Activity */
        .activity-section {
            margin-bottom: 35px;
        }

        .activity-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .activity-placeholder {
            text-align: center;
            padding: 30px 20px;
            color: #6b7280;
        }

        .activity-placeholder h3 {
            color: #374151;
            margin-bottom: 8px;
            font-size: 17px;
        }

        .activity-placeholder p {
            font-size: 14px;
        }

        /* Footer */
        footer {
            background: #111827;
            color: #9ca3af;
            text-align: center;
            padding: 22px;
            font-size: 14px;
        }

        footer strong {
            color: white;
        }

        /* Tablet */
        @media (max-width: 900px) {
            nav {
                padding: 18px 25px;
            }

            .dashboard-grid,
            .actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Mobile */
        @media (max-width: 600px) {
            nav {
                padding: 18px 20px;
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .nav-links {
                gap: 15px;
                flex-wrap: wrap;
                justify-content: center;
            }

            .container {
                padding: 30px 15px;
            }

            .welcome h1 {
                font-size: 26px;
            }

            .dashboard-grid,
            .actions-grid {
                grid-template-columns: 1fr;
            }

            .card,
            .team-card,
            .status-card,
            .roster-card,
            .game-card,
            .activity-card {
                padding: 20px;
            }
        }
    </style>
</head>

<body>

    <!-- Navigation -->
    <nav>

        <div class="brand">
            Brgy<span>Hoops</span> Manager
        </div>

        <div class="nav-links">
            <a href="/">Home</a>
            <a href="/logout" class="home-button">Logout</a>
        </div>

    </nav>


    <!-- Main Content -->
    <main class="container">

        <!-- Welcome Header -->
        <section class="welcome">

            <h1>Team Manager Dashboard</h1>

            <p>
                Manage your team, players, tournament schedule,
                and other team-related activities from this dashboard.
            </p>

        </section>


        <!-- Dashboard Overview -->
        <section class="dashboard-grid">

            <div class="card">

                <h3>My Team</h3>

                <p>
                    View and manage your team's information,
                    registration details, and tournament participation.
                </p>

            </div>


            <div class="card">

                <h3>Players</h3>

                <p>
                    Review and manage the players registered
                    under your team.
                </p>

            </div>


            <div class="card">

                <h3>Schedule</h3>

                <p>
                    View upcoming games, dates, times,
                    and tournament venues.
                </p>

            </div>

        </section>


        <!-- Team Information -->
        <section class="team-info">

            <div class="section-header">

                <h2>Team Information</h2>

                <p>
                    Your team's current information and registration details.
                </p>

            </div>


            <div class="team-card">

                <h3>My Team</h3>

                <p>
                    Team information will appear here once your team
                    has been registered.
                </p>

                <p>
                    Team Manager: Current Account
                </p>

            </div>

        </section>


        <!-- Team Status -->
        <section class="status-section">

            <div class="section-header">

                <h2>Team Status</h2>

                <p>
                    Current registration status of your team.
                </p>

            </div>


            <div class="status-card">

                <div class="status-label">
                    Registration Status
                </div>

                <div class="status">
                    Pending
                </div>

            </div>

        </section>


        <!-- Player Roster -->
        <section class="roster-section">

            <div class="section-header">

                <h2>Player Roster</h2>

                <p>
                    Players registered under your team.
                </p>

            </div>


            <div class="roster-card">

                <div class="roster-placeholder">

                    <h3>No players registered yet</h3>

                    <p>
                        Your team's player roster will appear here
                        once players have been added.
                    </p>

                </div>

            </div>

        </section>


        <!-- Upcoming Games -->
        <section class="games-section">

            <div class="section-header">

                <h2>Upcoming Games</h2>

                <p>
                    Your team's upcoming tournament games.
                </p>

            </div>


            <div class="game-card">

                <h3>Upcoming Game</h3>

                <div class="game-details">

                    <p>Date: To be announced</p>
                    <p>Time: To be announced</p>
                    <p>Venue: To be announced</p>

                </div>

            </div>

        </section>


        <!-- Quick Actions -->
        <section class="actions-section">

            <div class="section-header">

                <h2>Quick Actions</h2>

                <p>
                    Access common team management functions.
                </p>

            </div>


            <div class="actions-grid">

                <a href="#" class="action-button">

                    Manage Team

                    <span>
                        Update your team information
                    </span>

                </a>


                <a href="#" class="action-button">

                    Submit Roster

                    <span>
                        Submit your team's player roster
                    </span>

                </a>


                <a href="#" class="action-button">

                    View Schedule

                    <span>
                        Check upcoming tournament games
                    </span>

                </a>

            </div>

        </section>


        <!-- Recent Activity -->
        <section class="activity-section">

            <div class="section-header">

                <h2>Recent Activity</h2>

                <p>
                    Recent team activities will appear here.
                </p>

            </div>


            <div class="activity-card">

                <div class="activity-placeholder">

                    <h3>No recent activity</h3>

                    <p>
                        Recent team updates, roster submissions,
                        and tournament activities will be displayed here.
                    </p>

                </div>

            </div>

        </section>

    </main>


    <!-- Footer -->
    <footer>

        <p>
            <strong>BrgyHoops</strong> — Inter-Barangay Basketball Tournament Management System
        </p>

        <p>
            Team Manager Dashboard
        </p>

    </footer>

</body>
</html>