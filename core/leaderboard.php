<?php

session_start();
include "server/connection.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo "<script>var username = undefined;</script>";
} else {
    echo "<script>var username = '" . htmlspecialchars($_SESSION['username'], ENT_QUOTES) . "';</script>";
    if (isset($_SESSION['admin']) && $_SESSION['admin'] == true) {
        echo "<script>const ADMIN = true;</script>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard</title>
    <link rel="icon" href="assets/website images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles/styles.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <img src="assets/website images/logo.png" alt="Logo" width="30" height="30" class="d-inline-block align-text-top me-2">
                CFBPicks
            </a>
            <div class="d-flex">
                <div class="dropdown" id="account"></div>
            </div>
        </div>
    </nav>
    <div class="container-fluid mt-4 mb-3">
        <div class="page-header">
            <h1 class="text-center">Leaderboard</h1>
        </div>
        <div id="leaderboard-container" class="table-responsive mt-4">
            <table id="leaderboard-table"></table>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/scripts/get_week.js"></script>
    <script src="assets/scripts/account_navigation.js"></script>
    <script>
    let all_players = [];
    let all_scores = [];
    let all_winners = [];

    async function fetchData(url) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ week: current_week })
            });
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            const data = await response.json();
            if (data.error) throw new Error(data.error);
            return data;
        } catch (error) {
            console.error(`Failed to fetch from ${url}:`, error);
            alert(`Error: Could not load data from ${url}.`);
            return null;
        }
    }

    function buildLeaderboardTable() {
        const table = document.getElementById('leaderboard-table');
        if (!all_players.length || !all_scores.length || !all_winners.length) {
            table.innerHTML = '<thead><tr><th>Error</th></tr></thead><tbody><tr><td>Could not load all necessary data to build leaderboard.</td></tr></tbody>';
            return;
        }
        
        let headerHtml = `<thead><tr><th>Place</th><th>Player</th><th>Total</th>`;
        all_winners.forEach(week => {
            headerHtml += `<th>${week.week_num} (+${week.bonus_points})</th>`;
        });
        headerHtml += `<th>Win Bonus</th><th>Futures</th></tr></thead>`;
        
        let bodyHtml = `<tbody>`;
        let place = 0;
        all_players.forEach(p => {
            if (p.id == 0) return;
            const rowClass = (typeof username !== 'undefined' && username == p.username) ? "current-user-row" : "";
            let playerNormalPoints = 0;
            let playerBonusPoints = 0;
            const formattedScore = Number.isInteger(Number(p.score)) ? Math.round(p.score) : p.score;
            
            bodyHtml += `<tr class="${rowClass}"><td>${++place}</td><td>${p.username}</td><td>${formattedScore}</td>`;
            
            all_winners.forEach(week => { // show score for every week
                let playerScore = all_scores.find(s => s.week_num == week.week_num && s.account_id == p.id); // player score this wk
                playerNormalPoints += playerScore?.score ?? 0;
                let winClass = "", winPoints = 0;
                if (week.account_id == p.id || week.account_id_2 == p.id) {
                    winClass = "winning-player";
                    winPoints = week.bonus_points;
                }
                if (week.account_id_2) winPoints *= 0.5;
                playerBonusPoints += winPoints;
                
                bodyHtml += `<td class="${winClass}">${playerScore?.score ?? ""}</td>`;
            });
            
            let playerFuturesPoints = Number(p.score-playerNormalPoints-playerBonusPoints);
            bodyHtml += `<td>${playerBonusPoints > 0 ? Number(playerBonusPoints) : ""}</td><td>${playerFuturesPoints > 0 ? playerFuturesPoints : ""}</td></tr>`;
        });
        
        let maxTotalBonus = all_winners.reduce((a, c) => a + c.bonus_points, 0);
        let maxTotalFutures = 85; /////
        let maxTotal = all_winners.reduce((a, c) => a + c.max, 0) + maxTotalBonus + maxTotalFutures;
        
        bodyHtml += `</tbody><tfoot><tr><th></th><th>MAX</th><th>${maxTotal}</th>`;
        all_winners.forEach(week => {
            bodyHtml += `<th>${week.max}</th>`; 
        });
        bodyHtml += `<th>${maxTotalBonus}</th><th>${maxTotalFutures}</th></tr></tfoot>`;
        
        table.innerHTML = headerHtml + bodyHtml;
    }
    
    async function retrieveAllData() {
        try {
            console.log("Retrieving all accounts...");
            all_players = await fetchData('server/get_accounts.php');
            if (all_players) {
                console.log("Accounts retrieved!", all_players);
            } else {
                alert("Error retrieving accounts from db.");
                return false;
            }
            
            console.log("Retrieving all player scores...");
            all_scores = await fetchData('server/get_scores.php');
            if (all_scores) {
                console.log("Scores retrieved!", all_scores);
            } else {
                alert("Error retrieving player scores from db.");
                return false;
            }
            
            console.log("Retrieving winners...");
            const winnerResponse = await fetch('server/get_winners.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            const winnerData = await winnerResponse.json();
            if (winnerData.length == 0) {
                alert("Failed to retrieve winner from db.");
                return false;
            } else {
                all_winners = winnerData;
                console.log("Winners retrieved!", all_winners);
            }
            
            return true;
        } catch (error) {
            console.error("An error occurred during the data loading sequence:", error);
            return false;
        }
    }

    document.addEventListener('DOMContentLoaded', async () => {
        if (await retrieveAllData()) {
            buildLeaderboardTable();
        } else {
            document.getElementById('leaderboard-container').innerHTML = `<p class="text-center text-danger">A critical error occurred while loading page data.</p>`;
        }
    });
    </script>
</body>
</html>
