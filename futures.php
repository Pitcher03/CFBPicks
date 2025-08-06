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
    <title>Futures</title>
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
            <h1 class="text-center" title="Each futures pick is worth 5, and Champion is worth double">Futures</h1>
        </div>
        <div id="futures-container" class="table-responsive mt-4">
            <table id="futures-table"></table>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/scripts/get_week.js"></script>
    <script src="assets/scripts/account_navigation.js"></script>
    <script>
    let all_players = [];
    let all_futures = [];

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

    function buildFuturesTable() {
        const table = document.getElementById('futures-table');
        if (!all_players.length || !all_futures.length) {
            table.innerHTML = '<thead><tr><th>Error</th></tr></thead><tbody><tr><td>Could not load all necessary data to build futures table.</td></tr></tbody>';
            return;
        }
        
        const results = all_futures.shift();
        let headerHtml = `<thead><tr>`;
        for (key in results) {
            if (key == 'account_id') {
                headerHtml += `<th></th>`;
            } else if (results[key]) {
                headerHtml += `<th><img src="assets/logos/${results[key]}.png" width="30" height="30"></th>`;
            } else {
                headerHtml += `<th><img src="assets/logos/${key.indexOf("seed") == -1 ? key : 'ncaa'}.png" width="30" height="30"></th>`;
            }
        }
        headerHtml += `<th>Max: ${Object.keys(results).length * 5}</th></tr></thead>`;
        
        let bodyHtml = `<tbody>`;
        all_futures.forEach(row => {
            const rowUsername = all_players.find(p => p.id == row.account_id).username;
            const rowClass = typeof username !== 'undefined' && username == rowUsername ? "current-user-row" : "";
            
            bodyHtml += `<tr class="${rowClass}">`;
            let futures_score = 0;
            let notAvailable = false;
            
            for (key in row) {
                if (key == 'account_id') {
                    bodyHtml += `<td>${rowUsername}</td>`;
                    continue;
                }
                
                let pickClass;
                if (notAvailable) { // pick has not yet been offered
                    pickClass = "pick-cell";
                    bodyHtml += `<th class="${pickClass}"></th>`;
                } else if (key == current_futures.futuresKey) { // pick offered this current week
                    pickClass = "pick-cell pick-cell-live";
                    notAvailable = true;
                    if (row[key]) {
                        bodyHtml += `<th class="${pickClass}">&#128274;</th>`;
                    } else {
                        bodyHtml += `<th class="${pickClass}"></th>`;
                    }
                } else { // pick offered previously
                    if (row[key]) { // player made pick
                        if (results[key]) { // game has been played
                            if (results[key] == row[key]) { 
                                pickClass = "pick-cell pick-cell-correct";
                                futures_score += key == "champion" ? 10 : 5;
                            } else {
                                pickClass = "pick-cell pick-cell-incorrect";
                            }
                        } else { // waiting for game to be played
                            pickClass = "pick-cell pick-cell-waiting";
                        }
                        bodyHtml += `<th class="${pickClass}"><img src="assets/logos/${row[key]}.png" width="30" height="30"></th>`;
                    } else { // player forgot and it is locked
                        pickClass = "pick-cell pick-cell-incorrect";
                        bodyHtml += `<th class="${pickClass}"></th>`;
                    }
                }
            }
            
            bodyHtml += `<th>${futures_score}</th></tr>`;
        });
        bodyHtml += `</tbody>`;
        
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
            
            console.log("Retrieving futures picks...");
            all_futures = await fetchData('server/get_all_futures.php');
            if (all_futures) {
                console.log("Futures retrieved!", all_futures);
            } else {
                alert("Error retrieving futures picks from db.");
                return false;
            }
            
            return true;
        } catch (error) {
            console.error("An error occurred during the data loading sequence:", error);
            return false;
        }
    }

    document.addEventListener('DOMContentLoaded', async () => {
        if (await retrieveAllData()) {
            buildFuturesTable();
        } else {
            document.getElementById('futures-container').innerHTML = `<p class="text-center text-danger">A critical error occurred while loading page data.</p>`;
        }
    });
    </script>
</body>
</html>
