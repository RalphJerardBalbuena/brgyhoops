<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="{{ asset('css/brgyhoops.css') }}">

    <title>BrgyHoops - Public Tournament Dashboard</title>

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

        .login-button {
            border: 1px solid #dc2626;
            padding: 9px 16px;
            border-radius: 6px;
        }

        .login-button:hover {
            background: #dc2626;
            color: white !important;
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        /* Hero */
        .hero {
            background: white;
            border-radius: 12px;
            padding: 45px 30px;
            text-align: center;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 35px;
        }

        .hero h1 {
            font-size: 38px;
            color: #111827;
            margin-bottom: 12px;
        }

        .hero p {
            color: #6b7280;
            font-size: 17px;
            line-height: 1.6;
        }

        /* Section */
        .section {
            margin-bottom: 35px;
        }

        .section-header {
            margin-bottom: 18px;
        }

        .section-header h2 {
            font-size: 24px;
            color: #111827;
            margin-bottom: 6px;
        }

        .section-header p {
            color: #6b7280;
            font-size: 14px;
        }

        /* Tournament Information */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .info-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .info-card h3 {
            color: #111827;
            font-size: 17px;
            margin-bottom: 10px;
        }

        .info-card p {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.6;
        }

        /* Search and Filter */
        .search-panel {
            background: white;
            padding: 22px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .search-form {
            display: grid;
            grid-template-columns: 1fr 180px auto;
            gap: 12px;
        }

        .search-form input,
        .search-form select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            font-size: 14px;
            outline: none;
        }

        .search-form input:focus,
        .search-form select:focus {
            border-color: #dc2626;
        }

        .search-button {
            border: none;
            background: #dc2626;
            color: white;
            padding: 12px 20px;
            border-radius: 7px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }

        .search-button:hover {
            background: #b91c1c;
        }

        /* Teams */
        .teams-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .team-card {
            background: white;
            padding: 22px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .team-card h3 {
            font-size: 18px;
            margin-bottom: 8px;
            color: #111827;
        }

        .team-status {
            display: inline-block;
            background: #ecfdf5;
            color: #047857;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 12px;
        }

        .team-card p {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.5;
        }

        /* Games */
        .game-card {
            background: white;
            padding: 22px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            margin-bottom: 15px;
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

        .game-status {
            display: inline-block;
            margin-top: 10px;
            background: #f3f4f6;
            color: #374151;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        /* Announcements */
        .announcement-card {
            background: white;
            padding: 22px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            margin-bottom: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .announcement-card h3 {
            color: #111827;
            font-size: 17px;
            margin-bottom: 8px;
        }

        .announcement-date {
            color: #dc2626;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .announcement-card p {
            color: #6b7280;
            line-height: 1.6;
            font-size: 14px;
        }

        /* Empty / Placeholder */
        .placeholder {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 35px 20px;
            text-align: center;
            color: #6b7280;
        }

        .placeholder h3 {
            color: #374151;
            margin-bottom: 8px;
        }

        .placeholder p {
            font-size: 14px;
            line-height: 1.6;
        }

        /* Footer */
        footer {
            background: #111827;
            color: #9ca3af;
            text-align: center;
            padding: 22px;
            font-size: 14px;
            margin-top: 20px;
        }

        footer strong {
            color: white;
        }

        /* Tablet */
        @media (max-width: 900px) {
            nav {
                padding: 18px 25px;
            }

            .info-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .teams-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .search-form {
                grid-template-columns: 1fr 1fr;
            }

            .search-button {
                grid-column: span 2;
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

            .hero {
                padding: 35px 20px;
            }

            .hero h1 {
                font-size: 28px;
            }

            .info-grid,
            .teams-grid {
                grid-template-columns: 1fr;
            }

            .search-form {
                grid-template-columns: 1fr;
            }

            .search-button {
                grid-column: auto;
            }
        }
    </style>
</head>

<body>

    <!-- Navigation -->
    <nav>

        <div class="brand">
            Brgy<span>Hoops</span>
        </div>

        <div class="nav-links">
            <a href="/">Home</a>
            <a href="/login" class="login-button">Login</a>
            <a href="/register">Register</a>
        </div>

    </nav>


    <!-- Main Content -->
    <main class="container">

        <!-- Hero -->
        <section class="hero">

            <h1>BrgyHoops Tournament</h1>

            <p>
                Inter-Barangay Basketball Tournament Information
            </p>

            <p>
                View tournament updates, participating teams,
                upcoming games, and announcements.
            </p>

        </section>


        <!-- Tournament Information -->
        <section class="section">

            <div class="section-header">

                <h2>Tournament Information</h2>

                <p>
                    Public tournament information available to everyone.
                </p>

            </div>


            <div class="info-grid">

                <div class="info-card">

                    <h3>Tournament</h3>

                    <p>
                        BrgyHoops Inter-Barangay Basketball Tournament
                    </p>

                </div>


                <div class="info-card">

                    <h3>Participating Teams</h3>

                    <p>
                        View approved teams participating in the tournament.
                    </p>

                </div>


                <div class="info-card">

                    <h3>Game Schedule</h3>

                    <p>
                        View upcoming games, dates, times, and venues.
                    </p>

                </div>


                <div class="info-card">

                    <h3>Standings</h3>

                    <p>
                        Tournament standings and team records will be displayed here.
                    </p>

                </div>

            </div>

        </section>


        <!-- Search and Filter -->
        <section class="section">

            <div class="section-header">

                <h2>Find Tournament Information</h2>

                <p>
                    Search or filter publicly available tournament information.
                </p>

            </div>


            <div class="search-panel">

                <form class="search-form" action="#" method="GET">

                    <input
                        type="text"
                        name="search"
                        placeholder="Search teams, games, or announcements"
                    >

                    <select name="category">

                        <option value="">All Categories</option>
                        <option value="teams">Teams</option>
                        <option value="games">Games</option>
                        <option value="announcements">Announcements</option>

                    </select>

                    <button type="submit" class="search-button">
                        Search
                    </button>

                </form>

            </div>

        </section>


        <!-- Participating Teams -->
        <section class="section">

            <div class="section-header">

                <h2>Participating Teams</h2>

                <p>
                    Approved teams participating in the tournament.
                </p>

            </div>


            <div class="teams-grid">

                <div class="team-card">

                    <span class="team-status">
                        Approved
                    </span>

                    <h3>Barangay A</h3>

                    <p>
                        Participating basketball team.
                    </p>

                </div>


                <div class="team-card">

                    <span class="team-status">
                        Approved
                    </span>

                    <h3>Barangay B</h3>

                    <p>
                        Participating basketball team.
                    </p>

                </div>


                <div class="team-card">

                    <span class="team-status">
                        Approved
                    </span>

                    <h3>Barangay C</h3>

                    <p>
                        Participating basketball team.
                    </p>

                </div>

            </div>

        </section>


        <!-- Upcoming Games -->
        <section class="section">

            <div class="section-header">

                <h2>Upcoming Games</h2>

                <p>
                    View publicly available upcoming tournament games.
                </p>

            </div>


            <div class="game-card">

                <h3>Barangay A vs Barangay B</h3>

                <div class="game-details">

                    <p>Date: To be announced</p>
                    <p>Time: To be announced</p>
                    <p>Venue: To be announced</p>

                </div>

                <span class="game-status">
                    Scheduled
                </span>

            </div>


            <div class="game-card">

                <h3>Barangay B vs Barangay C</h3>

                <div class="game-details">

                    <p>Date: To be announced</p>
                    <p>Time: To be announced</p>
                    <p>Venue: To be announced</p>

                </div>

                <span class="game-status">
                    Scheduled
                </span>

            </div>

        </section>


        <!-- Announcements -->
        <section class="section">

            <div class="section-header">

                <h2>Announcements</h2>

                <p>
                    Important tournament updates and public announcements.
                </p>

            </div>


            <div class="announcement-card">

                <h3>Tournament Announcement</h3>

                <div class="announcement-date">
                    Tournament Update
                </div>

                <p>
                    Tournament announcements and important updates
                    will appear here.
                </p>

            </div>

        </section>

    </main>


    <!-- Footer -->
    <footer>

        <p>
            <strong>BrgyHoops</strong> — Inter-Barangay Basketball Tournament Management System
        </p>

        <p>
            Public Tournament Dashboard
        </p>

    </footer>

</body>
</html>