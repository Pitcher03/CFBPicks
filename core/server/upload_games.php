<?php

header('Content-Type: application/json');
session_start();
require_once 'connection.php';

function send_json_response($success, $message, $data = []) {
    $response = ['success' => $success, 'message' => $message];
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    echo json_encode($response);
    exit();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    send_json_response(false, 'Authentication error: You do not have the right to upload games.');
}

$json_payload = file_get_contents('php://input');
$request_data = json_decode($json_payload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    send_json_response(false, 'Invalid JSON payload received.');
}

if (!isset($request_data['newGames']) || !is_array($request_data['newGames'])) {
    send_json_response(false, 'Invalid request format: "games" array not found.');
}

$games_to_upload = $request_data['newGames'];
$games_inserted_count = 0;
$stmt = $con->prepare("INSERT INTO games (week, date, home, away, value, underdog, bonus) VALUES (?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) {
    send_json_response(false, 'Database error: Failed to prepare statement.');
}

foreach ($games_to_upload as $game) {
    if (!isset($game['week'], $game['time'], $game['home'], $game['away'], $game['pointValue'])) {
        send_json_response(false, 'One or more fields missing for game: ' . json_encode($game));
    }

    $week = (int)$game['week'];
    $home_team = $game['home'];
    $away_team = $game['away'];
    $point_value = (int)$game['pointValue'];
    $underdog = null;
    $bonus_value = null;

    if (isset($game['underdog'])) {
        $underdog = $game['underdog'];
    }
    if(isset($game['bonusValue'])) {
        $bonus_value = (int)$game['bonusValue'];
    }
    
    try { // all inputs need to be central time
        $date_ct = new DateTime($game['time']); 
        $db_date_string = $date_ct->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        send_json_response(false, 'Failed to establish start time of game: ' . json_encode($game));
    }

    $stmt->bind_param("isssisi", $week, $db_date_string, $home_team, $away_team, $point_value, $underdog, $bonus_value);
    
    if ($stmt->execute()) {
        $games_inserted_count++;
    } else {
        send_json_response(false, 'Failed to execute insert query: ' . $stmt->error);
    }
}

$stmt->close();
$con->close();

if ($games_inserted_count > 0) {
    send_json_response(true, 'Games have been successfully added to the database.', ['games_added' => $games_inserted_count]);
} else {
    send_json_response(false, 'No valid games were provided to be added.');
}
