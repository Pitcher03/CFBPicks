<?php

session_start();
include "server/connection.php";

// verify user is admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit;
} else {
    echo "<script>var username = '" . htmlspecialchars($_SESSION['username'], ENT_QUOTES) . "';const ADMIN = true;</script>";
}

$url = "https://cfbpicks.live/live%20scores/cfb_future_scoreboard.json";
$fileContent = file_get_contents($url);
echo "<script>var futureScoreboard = JSON.parse(" . json_encode($fileContent) . ");</script>";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="icon" href="assets/website images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles/styles.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
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
    <div class="container mt-4 mb-3">
        <div class="page-header">
            <h1 id="page-title" class="text-center"></h1>
        </div>
        <div id="games-grid" class="row g-3 mt-4"></div>
        <div id="offered-games-section" style="display: none;">
            <hr class="my-5">
            <h2 class="text-center mb-4" id="already-offered">Already Offered</h2>
            <div id="offered-games-grid" class="row g-3 mt-4"></div>
        </div>
    </div>
    <div class="submit-picks-container mb-4" id="submit-container">
        <button id="add-games-btn" class="btn btn-success btn-lg" disabled>Add Selected Games</button>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/scripts/get_week.js"></script>
    <script src="assets/scripts/account_navigation.js"></script>
    <script>
    let db_games = [];

    async function fetchDbGames(week) {
        try {
            const response = await fetch('server/get_games.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ week: week })
            });
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            } else {
                console.log("Games fetched from db successfully.");
            }
            const data = await response.json();
            db_games = data;
        } catch (error) {
            console.error("Failed to fetch database games:", error);
            alert("Error: Could not fetch existing games from the database.");
        }
    }

    function gameInDb(game) {
        return db_games.find(g => { return g.date == game.time && g.home == game.home && g.away == game.away; });
    }

    document.addEventListener('DOMContentLoaded', async () => {
        await fetchDbGames(current_week+1); // get games already added this week
        
        document.getElementById("page-title").innerHTML = "Admin Dashboard - Week " + Number(current_week+1); // should i add 1 or nah 

        const accountDiv = document.getElementById('account');
        if (typeof username !== 'undefined') {
            accountDiv.innerHTML = `<a href="#" class="nav-link dropdown-toggle text-white" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        ${username} (Admin)
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                                    </ul>`;
        }

        if (typeof futureScoreboard === 'undefined' || !futureScoreboard.scoreboard) {
            console.error("Failed to load or parse game data!");
            document.getElementById('games-grid').innerHTML = '<p class="text-center text-danger">Could not load game data.</p>';
            return;
        }

        const gamesGrid = document.getElementById('games-grid');
        const offeredGamesGrid = document.getElementById('offered-games-grid');
        const addGamesBtn = document.getElementById('add-games-btn');
        let newGamesHtml = '';
        let offeredGamesHtml = '';
        let selectedGames = {};
        let offeredPoints = {'1': 0, '2': 0, '3': 0, '4': 0};

        futureScoreboard.scoreboard.forEach((game, index) => {
            const gameId = `game-${index}`;
            const gameDate = new Date(game.time);
            const headerTime = gameDate.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                hour: 'numeric', 
                minute: '2-digit' 
            }).replace(',', '');
            
            const existingGame = gameInDb(game);

            if (existingGame) {
                offeredPoints[existingGame.value]++;
                let awayBonusText = '';
                let homeBonusText = '';
                if (existingGame.underdog) {
                    const bonusText = `<strong class="bonus-text">(+${existingGame.bonus})</strong>`;
                    if (existingGame.underdog === existingGame.away) {
                        awayBonusText = bonusText;
                    } else {
                        homeBonusText = bonusText;
                    }
                }

                offeredGamesHtml += `
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="game-card disabled-card">
                            <div class="game-header">
                                <span>${headerTime}</span>
                                <span class="point-value">${existingGame.value} Point${existingGame.value > 1 ? 's' : ''}</span>
                            </div>
                            <div class="game-body">
                                <div class="team-row">
                                    <img src="assets/logos/${existingGame.away}.png" alt="${existingGame.away}" class="team-logo">
                                    <span class="team-name">${existingGame.away} ${awayBonusText}</span>
                                </div>
                                <div class="team-row">
                                    <img src="assets/logos/${existingGame.home}.png" alt="${existingGame.home}" class="team-logo">
                                    <span class="team-name">${existingGame.home} ${homeBonusText}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                const spread = parseFloat(game.homeSpread);
                const basePointValue = 1;
                let awaySpreadDisplay = '', homeSpreadDisplay = '', awayBonusInput = '', homeBonusInput = '';

                if (!isNaN(spread) && spread !== 0) {
                    const absSpread = Math.abs(spread);
                    const recommendedBonus = Math.floor(Math.pow(absSpread, 1.1) * Math.sqrt(basePointValue) / 5);
                    const minBonus = Math.max(0, recommendedBonus - 3);
                    const maxBonus = recommendedBonus + 3;

                    const bonusInputHTML = `
                        <div class="bonus-container">
                            <span class="bonus-label">Bonus:</span>
                            <input type="number" class="bonus-input" value="${recommendedBonus}" min="${minBonus}" max="${maxBonus}" placeholder="${recommendedBonus}">
                        </div>
                    `;

                    if (spread < 0) {
                        homeSpreadDisplay = `(${spread})`;
                        awayBonusInput = bonusInputHTML;
                    } else {
                        awaySpreadDisplay = `(${-spread})`;
                        homeBonusInput = bonusInputHTML;
                    }
                }
                
                newGamesHtml += `
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="game-card admin-card" id="${gameId}" data-game-info='${JSON.stringify(game)}'>
                            <div class="game-header"><span>${headerTime}</span></div>
                            <div class="game-body">
                                <div class="team-row">
                                    <img src="assets/logos/${game.away}.png" alt="${game.away}" class="team-logo">
                                    <span class="team-name">${game.away} <span class="spread-text">${awaySpreadDisplay}</span></span>
                                    ${awayBonusInput || '<div class="bonus-input-placeholder"></div>'}
                                </div>
                                <div class="team-row">
                                    <img src="assets/logos/${game.home}.png" alt="${game.home}" class="team-logo">
                                    <span class="team-name">${game.home} <span class="spread-text">${homeSpreadDisplay}</span></span>
                                    ${homeBonusInput || '<div class="bonus-input-placeholder"></div>'}
                                </div>
                            </div>
                            <div class="game-footer">
                                <span class="team-value-label">Value</span>
                                <input type="number" class="point-input" value="${basePointValue}" min="0" max="4">
                            </div>
                        </div>
                    </div>
                `;
            }
        });
        
        gamesGrid.innerHTML = newGamesHtml || '<p class="text-center text-muted">No new games to add for this week.</p>';
        if (offeredGamesHtml) {
            document.getElementById('offered-games-section').style.display = 'block';
            offeredGamesGrid.innerHTML = offeredGamesHtml;
            
            let str = "";
            if (offeredPoints['4'] > 0) str += `${offeredPoints[4]}x 4-point, `;
            if (offeredPoints['3'] > 0) str += `${offeredPoints[3]}x 3-point, `;
            if (offeredPoints['2'] > 0) str += `${offeredPoints[2]}x 2-point, `;
            if (offeredPoints['1'] > 0) str += `${offeredPoints[1]}x 1-point, `;
            let sum = 0; 
            for (value in offeredPoints) {
                sum += value*offeredPoints[value];
            } 
            str += ` ${sum} total points`;
            document.getElementById("already-offered").innerText = "Already Offered - " + str;
        }

        function updateSelectedGame(gameId) {
            if (selectedGames[gameId]) {
                const gameCard = document.getElementById(gameId);
                const pointInput = gameCard.querySelector('.point-input');
                const bonusInput = gameCard.querySelector('.bonus-input');
                selectedGames[gameId].pointValue = parseInt(pointInput.value, 10);
                selectedGames[gameId].bonusValue = bonusInput ? parseInt(bonusInput.value, 10) : 0;
            }
        }

        gamesGrid.addEventListener('click', function(e) {
            const gameCard = e.target.closest('.admin-card');
            if (!gameCard) return;
            if (e.target.closest('.point-input') || e.target.closest('.bonus-container')) return;

            const gameId = gameCard.id;
            gameCard.classList.toggle('selected');

            if (gameCard.classList.contains('selected')) {
                const gameInfo = JSON.parse(gameCard.dataset.gameInfo);
                gameInfo.week = current_week+1;
                const pointInput = gameCard.querySelector('.point-input');
                const bonusInput = gameCard.querySelector('.bonus-input');
                gameInfo.pointValue = parseInt(pointInput.value, 10);
                gameInfo.bonusValue = bonusInput ? parseInt(bonusInput.value, 10) : 0;
                selectedGames[gameId] = gameInfo;
            } else {
                delete selectedGames[gameId];
            }
            addGamesBtn.disabled = Object.keys(selectedGames).length === 0;
        });

        gamesGrid.addEventListener('input', function(e) {
            const target = e.target;
            const gameCard = target.closest('.admin-card');
            if (!gameCard) return;
            const gameId = gameCard.id;
            
            if (target.classList.contains('point-input')) {
                const baseValue = parseInt(target.value, 10);
                const bonusInput = gameCard.querySelector('.bonus-input');
                if (bonusInput) {
                    const gameInfo = JSON.parse(gameCard.dataset.gameInfo);
                    const spread = Math.abs(parseFloat(gameInfo.homeSpread));
                    const recommendedBonus = Math.floor(Math.pow(spread, 1.1) * Math.sqrt(baseValue) / 5);
                    const minBonus = Math.max(0, recommendedBonus - 3);
                    const maxBonus = recommendedBonus + 3;
                    bonusInput.value = recommendedBonus;
                    bonusInput.placeholder = recommendedBonus;
                    bonusInput.min = minBonus;
                    bonusInput.max = maxBonus;
                }
            }
            updateSelectedGame(gameId);
        });

        addGamesBtn.addEventListener('click', function() {
            const gamesToSubmit = Object.values(selectedGames);
            if (gamesToSubmit.length === 0) return;

            if (confirm("Are you sure you want to upload " + gamesToSubmit.length + " games?")) {
                gamesToSubmit.forEach(g => {
                    if (g.homeSpread > 0) {
                        g.underdog = g.home;
                    } else if (g.homeSpread < 0) {
                        g.underdog = g.away;
                    }
                });
                uploadGames(gamesToSubmit);
            }
        });

        async function uploadGames(gamesToSubmit) {
            try {
                const response = await fetch('upload_games.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ newGames: gamesToSubmit })
                });
                const data = await response.json();
                if (data.success) {
                    alert('Successfully uploaded ' + data.games_added + ' games.');
                } else {
                    alert('Error: ' + (data.message || 'An unknown error occurred.'));
                }
            } catch (error) {
                console.error('Upload Error:', error);
                alert('An error occurred while trying to upload the games.');
            } finally {
                window.location.reload();
            }
        }
    });
    </script>
</body>
</html>
