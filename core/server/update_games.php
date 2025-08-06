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

$json_payload = file_get_contents('php://input');
$request_data = json_decode($json_payload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    send_json_response(false, 'Invalid JSON payload received.');
}

if (!isset($request_data['games']) || !is_array($request_data['games'])) {
    send_json_response(false, 'Invalid request format: "games" array not found.');
}

$games_to_process = $request_data['games'];
$games_modified = 0;
$stmt = $con->prepare("UPDATE games SET winner = ? WHERE id = ?");

if (!$stmt) {
    send_json_response(false, 'Database error: Failed to prepare update statement.');
}

foreach ($games_to_process as $game) {
    if (!isset($game['game_id']) || !isset($game['winner'])) {
        send_json_response(false, 'Invalid request: one or more games missing info.');
    }

    $game_id = (int)$game['game_id'];
    $winner = $game['winner'];

    $stmt->bind_param("si", $winner, $game_id);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $games_modified++;
        }
    } else {
        send_json_response(false, 'Insert query failed.');
    }
}

$stmt->close();
$con->close();

send_json_response(true, 'Games updated successfully.', [
    'games_modified' => $games_modified
]);