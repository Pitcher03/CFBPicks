<?php

session_start();
include "server/connection.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo "<script>var username = undefined;</script>";
} else {
    $username = $_SESSION['username'];
    echo "<script>var username = '" . $username . "';</script>";
    if (isset($_SESSION['admin']) && $_SESSION['admin'] == true) {
        echo "<script>const ADMIN = true;</script>";
    }
}

$query = "SELECT * FROM `games` WHERE `date` > NOW() ORDER BY `date` ASC LIMIT 1";
$result = mysqli_query($con, $query);

if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    echo "<script>var next_game = " . json_encode($row) . ";</script>";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CFBPicks Dashboard</title>
    <link rel="icon" href="assets/website images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/5.4.3/echarts.min.js"></script>
    <script src="assets/scripts/get_week.js"></script>
    <script src="assets/scripts/animation.js"></script>
    <script src="assets/scripts/account_navigation.js"></script>
</head>
<body>
    <div id="waves" class="chart-container"></div>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="assets/website images/logo.png" alt="Logo" width="30" height="30" class="d-inline-block align-text-top me-2">
                CFBPicks
            </a>
            <div class="d-flex">
                <div class="dropdown" id="account"></div>
            </div>
        </div>
    </nav>
    <div class="container mt-3">
        <h1 class="text-center mb-4" style="text-shadow: 0 0 15px black;">CFBPicks Season 3</h1>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div id="card-mypicks" class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">My Picks</h5>
                        <p class="card-text">View and make your weekly picks.</p>
                        <a href="mypicks.php" class="stretched-link"></a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div id="card-allpicks" class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Everyone's Picks</h5>
                        <p class="card-text">See what picks others have made.</p>
                        <a href="allpicks.php" class="stretched-link"></a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div id="card-leaderboard" class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Leaderboard</h5>
                        <p class="card-text">Check the current season standings.</p>
                        <a href="leaderboard.php" class="stretched-link"></a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div id="card-futures" class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title">Futures</h5>
                        <p class="card-text">View the weekly Futures picks.</p>
                        <a href="futures.php" class="stretched-link"></a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-4 mt-2">
            <div class="col-lg-6">
                <div id="overview-card" class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="overview-header">
                            <span class="fw-bold">Overview</span>
                            <span id="overview-week"></span>
                            <span id="overview-picks"></span>
                        </div>
                        <div class="overview-body">
                            <div class="overview-column"></div>
                            <div class="overview-column"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div id="countdown-card" class="card h-100">
                    <div class="card-body text-center d-flex flex-column justify-content-center">
                        <p class="countdown-timer" id="countdown-timer"></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-4 mt-2 mb-4">
            <div class="col-12">
                <div id="card-rules" class="card">
                    <div class="card-body">
                        <h5 class="card-title">About</h5>
                        <p class="card-text">Welcome back to CFBPicks season 3! Choose the winners of 20 games a week, each worth 1-3 points. Underdogs have a bonus. Choose one 'confidence' game a week to be worth double by double-clicking it. Don't forget the futures pick! Weekly winners receive bonus points and a small cash prize. Entry fee is $40 for the season. After paying out weekly winners, the remaining pot is split 60%/30%/10% with the overall top 3.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        let week_stats = null
        let season_stats = null;
        let empty_picks = null;
        let bonus_points = null;
        
        function centralToLocalTime(centralTime) { 
            // input and output should both be Date() objects
            let offset = 300 - new Date().getTimezoneOffset(); // minutes to add
            return new Date(centralTime.getTime() + offset*60000);
        }

        function buildCountdown() {
            const countdownElement = document.getElementById("countdown-timer");
            const parentElement = countdownElement.parentElement;
            let awayLogo = document.createElement('img');
            awayLogo.style = "position: absolute; top: 0px; left: 25%; height: 100%; max-width: 50%; opacity: 0.7; transform: translateX(-50%);";
            awayLogo.src = "assets/logos/" + next_game.away + ".png";
            let homeLogo = document.createElement('img');
            homeLogo.style = "position: absolute; top: 0px; left: 75%; height: 100%; max-width: 50%; opacity: 0.7; transform: translateX(-50%);";
            homeLogo.src = "assets/logos/" + next_game.home + ".png";
            parentElement.appendChild(awayLogo);
            parentElement.appendChild(homeLogo);
            
            const countdownDate = centralToLocalTime(new Date(next_game.date)).getTime();

            const timerInterval = setInterval(() => {
                const now = new Date().getTime();
                const distance = countdownDate - now;
                countdownElement.style.color = "whitesmoke";

                if (distance < 0) {
                    clearInterval(timerInterval);
                    countdownElement.innerHTML = "No next game found.";
                    return;
                } else if (distance < 3600000) {
                    countdownElement.classList.add("starting-very-soon");
                } else if (distance < 86400000) {
                    countdownElement.classList.add("starting-soon");
                }

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                countdownElement.innerHTML = `${days}d ${hours < 10 ? "0" + hours : hours}h ${minutes < 10 ? "0" + minutes : minutes}m ${seconds < 10 ? "0" + seconds : seconds}s`;
            }, 1000);
        }
        
        function buildOverviewTable() {
            let unpickedText = empty_picks == 0 ? "All picks made" : 
                                empty_picks == 1 ? "1 unpicked game" :
                                empty_picks + " unpicked games";
            document.getElementById("overview-picks").innerText = unpickedText;
            if (empty_picks === 0) {
                document.getElementById("overview-picks").style.color = "#28a745";
            } else {
                document.getElementById("overview-picks").style.color = "#dc3545";
            }
            
            document.getElementById("overview-week").innerText = "Week " + current_week;
            
            const cols = document.querySelectorAll('.overview-column');
            
            let leftHtml = `<div class="overview-row-header">This Week</div>`;
            if (typeof username === 'undefined') {
                leftHtml += `<div class="overview-row"><span>Log in to see statistics!</span></div>`;
            } else if (week_stats === null || bonus_points === null) {
                console.error("Failed to load user week statistics.");
                leftHtml += `<div class="overview-row"><span>Failed to load statistics. Try again later.</span></div>`;
            } else {
                leftHtml += `<div class="overview-row"><span>Score:</span> <span>${week_stats.score}</span></div><div class="overview-row"><span>Place:</span><span>${week_stats.user_rank} of ${week_stats.total_players}</span></div><div class="overview-row"><span>Bonus:</span><span>${bonus_points} points + $0</span></div>`;
            }
            
            let rightHtml = `<div class="overview-row-header">Season</div>`;
            if (typeof username === 'undefined') {
                rightHtml += `<div class="overview-row"><span>Log in to see statistics!</span></div>`;
            } else if (season_stats === null) {
                console.error("Failed to load user season statistics.");
                rightHtml += `<div class="overview-row"><span>Failed to load statistics. Try again later.</span></div>`;
            } else {
                let scoreText = Number.isInteger(Number(season_stats.score)) ? Math.round(Number(season_stats.score)) : season_stats.score;
                rightHtml += `<div class="overview-row"><span>Score:</span> <span>${scoreText}</span></div><div class="overview-row"><span>Place:</span><span>${season_stats.user_rank} of ${season_stats.total_players}</span></div><div class="overview-row"><span>Pot:</span><span>$0</span></div>`;
            }
            
            cols[0].innerHTML = leftHtml;
            cols[1].innerHTML = rightHtml;
        }
        
        async function fetchStatistics() {
            try {
                const statsResponse = await fetch('server/get_overview_stats.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        week: current_week, 
                        key: current_futures.futuresKey 
                    })
                });
                if (!statsResponse.ok) {
                    throw new Error(`HTTP error! Status: ${statsResponse.status}`);
                } else {
                    const data = await statsResponse.json();
                    if (data.error) {
                        throw new Error(data.error);
                    } else {
                        week_stats = data.week_stats;
                        season_stats = data.season_stats;
                        empty_picks = data.empty_picks;
                        bonus_points = data.bonus_points;
                        console.log("Statistics fetched successfully.", week_stats, season_stats, empty_picks, bonus_points);
                    }
                }
            } catch (error) {
                console.error("Failed to fetch database stats:", error);
            }
        }
        
        document.addEventListener('DOMContentLoaded', async () => {
            if (typeof username !== 'undefined') {
                await fetchStatistics();
            }
            buildCountdown();
            buildOverviewTable();
        });
        
        window.onload = () => {
            launchAnimation();
        };
    </script>
</body>
</html>