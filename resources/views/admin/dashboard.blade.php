<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    link rel="stylesheet" href="{{ asset('css/brgyhoops.css') }}">

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

        /* Welcome Section */
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
        }

        /* Dashboard Widgets */
        .widget-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 35px;
        }

        .widget {
            background: white;
            border-radius: 10px;
            padding: 25px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        .widget-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .widget-title {
            font-size: 17px;
            font-weight: bold;
            color: #374151;
        }

        .widget-number {
            font-size: 32px;
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 8px;
        }

        .widget-description {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.5;
        }

        /* Section */
        .section {
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

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .action-button {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 18px;
            text-decoration: none;
            color: #111827;
            font-weight: bold;
            transition: 0.2s ease;
            display: block;
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
            margin-top: 7px;
            font-weight: normal;
        }

        /* Recent Activity */
        .activity-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .activity-placeholder {
            text-align: center;
            padding: 35px 20px;
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
            margin-top: 20px;
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

            .widget-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .quick-actions {
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

            .widget-grid {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .widget {
                padding: 20px;
            }
        }
    </style>
</head>

<body>

    <!-- Navigation -->
    <nav>

        <div class="brand">
            Brgy<span>Hoops</span> Admin
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

            <h1>Administrator Dashboard</h1>

            <p>
                Welcome to the BrgyHoops Tournament Management System.
                Manage tournaments, teams, players, and other tournament information from this dashboard.
            </p>

        </section>


        <!-- Dashboard Widgets -->
        <section class="widget-grid">

            <!-- Tournaments -->
            <div class="widget">

                <div class="widget-header">
                    <div class="widget-title">
                        Tournaments
                    </div>
                </div>

                <div class="widget-number">
                    0
                </div>

                <p class="widget-description">
                    View and manage basketball tournaments.
                </p>

            </div>


            <!-- Teams -->
            <div class="widget">

                <div class="widget-header">
                    <div class="widget-title">
                        Teams
                    </div>
                </div>

                <div class="widget-number">
                    0
                </div>

                <p class="widget-description">
                    Manage participating teams and barangays.
                </p>

            </div>


            <!-- Players -->
            <div class="widget">

                <div class="widget-header">
                    <div class="widget-title">
                        Players
                    </div>
                </div>

                <div class="widget-number">
                    0
                </div>

                <p class="widget-description">
                    Review and manage registered players.
                </p>

            </div>

        </section>


        <!-- Quick Actions -->
        <section class="section">

            <div class="section-header">

                <h2>Quick Actions</h2>

                <p>
                    Quickly access common administrator tasks.
                </p>

            </div>


            <div class="quick-actions">

                <a href="#" class="action-button">
                    Manage Tournaments
                    <span>Create or update tournament information</span>
                </a>


                <a href="#" class="action-button">
                    Manage Teams
                    <span>Review participating teams</span>
                </a>


                <a href="#" class="action-button">
                    Manage Players
                    <span>Review player information</span>
                </a>


                <a href="#" class="action-button">
                    Game Schedule
                    <span>View and manage scheduled games</span>
                </a>


                <a href="#" class="action-button">
                    Game Results
                    <span>Review tournament game results</span>
                </a>


                <a href="#" class="action-button">
                    Announcements
                    <span>Manage tournament announcements</span>
                </a>

            </div>

        </section>


        <!-- Recent Activity -->
        <section class="section">

            <div class="section-header">

                <h2>Recent Activity</h2>

                <p>
                    Recent administrator and tournament activity will appear here.
                </p>

            </div>


            <div class="activity-card">

                <div class="activity-placeholder">

                    <h3>No recent activity</h3>

                    <p>
                        Recent tournament, team, player, and system activities will be displayed in this section.
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
            Administrator Dashboard
        </p>

    </footer>

</body>
</html>