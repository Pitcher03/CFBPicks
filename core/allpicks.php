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

$url = "https://cfbpicks.live/live%20scores/cfb_scoreboard.json";
$fileContent = file_get_contents($url);
echo "<script>var scoreboard = JSON.parse(" . json_encode($fileContent) . ");</script>";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Picks - CFBPicks</title>
    <link rel="icon" href="assets/website images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/scripts/get_week.js"></script>
    <script src="assets/scripts/account_navigation.js"></script>
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
            <h1 id="page-title" class="text-center"></h1>
        </div>
        <div id="picks-container" class="table-responsive mt-4">
            <table id="picks-table"></table>
        </div>
    </div>
    <script>
    let db_games = [];
    let all_players = [];
    let all_picks = [];
    let all_scores = [];
    let weekly_winner = null;
    let weekly_winner_2 = null;
    let weekly_bonus = null;

    function findMatchingScoreboardGame(dbGame) {
        return scoreboard.scoreboard.find(sbGame => {
            return dbGame.date === sbGame.time && dbGame.home === sbGame.home && dbGame.away === sbGame.away;
        });
    }

    function buildPicksTable() {
        const table = document.getElementById('picks-table');
        if (!db_games.length || !all_scores.length) {
            table.innerHTML = '<thead><tr><th>Error</th></tr></thead><tbody><tr><td>Could not load all necessary data to build weekly leaderboard.</td></tr></tbody>';
            return;
        }
        
        let headerHtml = '<thead><tr><th></th><th></th>';
        db_games.forEach(game => {
            let innerDisplay;
            let liveGame = findMatchingScoreboardGame(game);
            if (liveGame.status === 'Final') {
                if (liveGame.winner === game.underdog) {
                    innerDisplay = `<img src="assets/logos/${liveGame.winner}.png" alt="${liveGame.winner}">+${game.value+game.bonus} (upset)`;
                } else {
                    innerDisplay = `<img src="assets/logos/${liveGame.winner}.png" alt="${liveGame.winner}">+${game.value} pts`;
                }
            } else {
                innerDisplay = `${game.value} points`;
            }
            headerHtml += `<th><div class="game-header-logos">${innerDisplay}</div></th>`;
        });
        headerHtml += '<th></th></tr><tr><th>Player</th><th>Score</th>';
        db_games.forEach(game => {
            headerHtml += `<th><div class="game-header-logos"><img src="assets/logos/${game.away}.png" alt="${game.away}"><img src="assets/logos/${game.home}.png" alt="${game.home}"></div></th>`;
        });
        headerHtml += '<th>Tiebreaker</th></tr></thead>';

        const playerData = {};
        all_scores.forEach(score => {
            playerData[score.account_id] = {
                username: all_players.find(p => p.id == score.account_id).username,
                score: score.score,
                picks: {}
            };
        });

        all_picks.forEach(pick => {
            if (playerData[pick.account_id]) {
                if (!playerData[pick.account_id].picks[pick.game_id]) {
                    playerData[pick.account_id].picks[pick.game_id] = {};
                }
                playerData[pick.account_id].picks[pick.game_id][pick.type] = pick.pick;
            }
        });
        
        console.log("Player Data:", playerData);

        let bodyHtml = '<tbody>';
        if (weekly_winner) {
            const winningPlayer = playerData[weekly_winner];
            delete playerData[weekly_winner];
            buildPlayerRowHtml(winningPlayer, true);
        }
        if (weekly_winner_2) {
            const winningPlayer = playerData[weekly_winner_2];
            delete playerData[weekly_winner_2];
            buildPlayerRowHtml(winningPlayer, true);
        }
        for (const account_id in playerData) {
            const player = playerData[account_id];
            buildPlayerRowHtml(player);
        }
        
        function buildPlayerRowHtml(player, isWeeklyWinner=false) {
            console.log("building row for", player)
            const isCurrentUser = (typeof username !== 'undefined' && username === player.username);
            const bonus = isWeeklyWinner ? weekly_winner_2 ? weekly_bonus/2 : weekly_bonus : null;
            const winningClass = isWeeklyWinner ? "winning-player" : "";
            const scoreHtml = isWeeklyWinner ? `<td class="${winningClass}">${player.score} (+${bonus})</td>` : `<td>${player.score}</td>`;
            bodyHtml += `<tr class="${isCurrentUser ? 'current-user-row' : ''}"><td class="${winningClass}">${player.username}</td>${scoreHtml}`;

            let tiebreakerPick = '-';

            db_games.forEach(game => {
                const liveGame = findMatchingScoreboardGame(game);
                if (!liveGame) {
                    alert("Could not find game " + game.away + " vs " + game.home);
                    return;
                }

                const userPicksThisGame = player.picks[game.id];
                const pick = userPicksThisGame ? userPicksThisGame['normal'] : undefined;
                const confidence = userPicksThisGame ? userPicksThisGame['confidence'] : undefined;
                if (userPicksThisGame && userPicksThisGame['tiebreaker']) {
                    tiebreakerPick = liveGame.status === 'Pregame' ? '&#128274;' : userPicksThisGame['tiebreaker'];
                }
                let cellContent = '';
                let cellClass = '';
                
                if (confidence) cellClass = 'confidence-pick ';

                if (pick) {
                    if (liveGame.status === 'Pregame') {
                        cellClass += 'pick-cell-waiting';
                        cellContent = '&#128274;';
                        if (confidence) cellContent += '&#128274;';
                    } else if (liveGame.status === 'Live') {
                        cellClass += 'pick-cell-live';
                        cellContent = `<img src="assets/logos/${pick}.png" alt="${pick}">`;
                        if (confidence) cellContent += `<img src="assets/logos/${pick}.png" alt="${pick}">`;
                    } else {
                        const isCorrect = liveGame && liveGame.winner === pick;
                        cellClass += isCorrect ? 'pick-cell-correct' : 'pick-cell-incorrect';
                        cellContent = `<img src="assets/logos/${pick}.png" alt="${pick}">`;
                        if (confidence) cellContent += `<img src="assets/logos/${pick}.png" alt="${pick}">`;
                    }
                } else {
                    if (liveGame.status === 'Pregame') {
                        cellClass += 'pick-cell-waiting';
                    } else {
                        cellClass += 'pick-cell-incorrect';
                    }
                }
                bodyHtml += `<td class="pick-cell ${cellClass}">${cellContent}</td>`;
            });

            bodyHtml += `<td class="${winningClass}">${tiebreakerPick}</td></tr>`;
        }
        
        bodyHtml += '</tbody>';

        table.innerHTML = headerHtml + bodyHtml;
    }
    
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
    
    async function retrieveAllData() {
        try {
            console.log("Retrieving db games...");
            db_games = await fetchData('server/get_games.php');
            if (db_games) {
                console.log("Games retrieved!", db_games);
            } else {
                alert("Error retrieving games from db.");
                return false;
            }
            
            const tiebreakerGame = findMatchingScoreboardGame(db_games.find(g => g.info && g.info.startsWith("Picked by")));
            const tiebreakerScore = tiebreakerGame.awayScore === undefined ? 0 : tiebreakerGame.awayScore - tiebreakerGame.homeScore;

            console.log("Triggering score update...");
            const updateResponse = await fetch('server/update_player_scores.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    week: current_week,
                    tiebreaker: tiebreakerScore
                })
            });
            const updateData = await updateResponse.json();
            if (!updateData.success) {
                console.warn("Score update failed:", updateData.message);
            } else {
                console.log("Score update successful.");
            }
            
            console.log("Retrieving all accounts...");
            all_players = await fetchData('server/get_accounts.php');
            if (all_players) {
                console.log("Accounts retrieved!", all_players);
            } else {
                alert("Error retrieving accounts from db.");
                return false;
            }
            
            console.log("Retrieving all picks...");
            all_picks = await fetchData('server/get_all_picks.php');
            if (all_picks) {
                console.log("Picks retrieved!", all_picks);
            } else {
                alert("Error retrieving picks from db.");
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
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    week: current_week
                })
            });
            const winnerData = await winnerResponse.json();
            if (winnerData.length == 0) {
                alert("Failed to retrieve winner from db.");
                return  false;
            } else {
                weekly_winner = winnerData[0].account_id;
                weekly_winner_2 = winnerData[0].account_id_2;
                weekly_bonus = winnerData[0].bonus_points;
                console.log("Winners retrieved!", weekly_winner, weekly_winner_2, weekly_bonus);
            }
            return true;
        } catch (error) {
            console.error("An error occurred during the data loading sequence:", error);
            return false;
        }
    }

    document.addEventListener('DOMContentLoaded', async () => {
        document.getElementById('page-title').innerHTML = "Week " + current_week + " Picks";
        if (await retrieveAllData()) {
            buildPicksTable();
        } else {
            document.getElementById('scores-container').innerHTML = `<p class="text-center text-danger">A critical error occurred while loading page data.</p>`;
        }
    });
    </script>
</body>
</html>
