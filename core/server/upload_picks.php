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

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    send_json_response(false, 'Authentication error: You must be logged in to submit picks.');
}

$account_id = $_SESSION['user_id'];
$json_payload = file_get_contents('php://input');
$request_data = json_decode($json_payload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    send_json_response(false, 'Invalid JSON payload received.');
}

if (!isset($request_data['picks']) || !isset($request_data['week'])) {
    send_json_response(false, 'Invalid request format: picks or week num not found.');
}

$picks_to_process = $request_data['picks'];
$week = $request_data['week'];
$picks_added = 0;
$picks_modified = 0;
$tiebreaker_added = false;
$tiebreaker_modified = false;
$confidence_added = false;
$confidence_modified = false;
$futures_modified = false;
$locked_games = false;

$time_stmt = $con->prepare("SELECT date FROM games WHERE id = ?");
$check_pick_stmt = $con->prepare("SELECT id FROM picks WHERE account_id = ? AND game_id = ? AND type = ?");
$update_pick_stmt = $con->prepare("UPDATE picks SET pick = ? WHERE account_id = ? AND game_id = ? AND type = ?");
$insert_pick_stmt = $con->prepare("INSERT INTO picks (account_id, game_id, pick, type) VALUES (?, ?, ?, ?)");
$check_confidence_stmt = $con->prepare("SELECT id FROM picks WHERE account_id = ? AND game_id IN (SELECT id FROM games WHERE week = ?) AND type = 'confidence'");
$delete_confidence_stmt = $con->prepare("DELETE FROM picks WHERE id = ?");

if (!$time_stmt || !$check_pick_stmt || !$update_pick_stmt || !$insert_pick_stmt || !$check_confidence_stmt || !$delete_confidence_stmt) {
    send_json_response(false, 'Database error: Failed to prepare one or more statements.');
}

try {
    foreach ($picks_to_process as $pick) {
        if (!isset($pick['pick'], $pick['type'])) {
            send_json_response(false, 'Invalid request: one or more picks missing info.');
        }
    
        $pick_team = $pick['pick'];
        $pick_type = $pick['type'];
        
        if ($pick_type == 'futures') { // handle futures update
            $conference_key = $pick['key'];
            $update_futures_stmt = $con->prepare("UPDATE futures SET " . $conference_key . " = ? WHERE account_id = ?");
            $update_futures_stmt->bind_param("si", $pick_team, $account_id);
            $update_futures_stmt->execute(); 
            if ($update_futures_stmt->affected_rows == 1) { // skip to next pick
                $futures_modified = true;
            }
            $update_futures_stmt->close();
            continue;
        }
        
        if (!isset($pick['game_id'])) {
            send_json_response(false, 'Invalid request: one or more picks missing game id.');
        }
        
        $game_id = (int)$pick['game_id'];
        
        // Check if the game is locked
        $time_stmt->bind_param("i", $game_id);
        $time_stmt->execute();
        $result = $time_stmt->get_result();
        
        if ($result->num_rows == 0) {
            send_json_response(false, 'Invalid request: failed to locate game with id ' . $game_id);
        } else {
            $game_row = $result->fetch_assoc();
            try {
                $game_start_time = new DateTime($game_row['date'], new DateTimeZone('America/Chicago'));
                $current_time_utc = new DateTime('now', new DateTimeZone('UTC'));
                if ($current_time_utc > $game_start_time) {
                    $locked_games = true;
                    continue; // Skip this pick
                }
            } catch (Exception $e) {
                send_json_response(false, 'Error handling date ' . $game_row['date']);
            }
        }
    
        if ($pick_type == "confidence") {
            // First, find and delete any existing confidence pick for this week
            $check_confidence_stmt->bind_param("ii", $account_id, $week);
            $check_confidence_stmt->execute();
            $result = $check_confidence_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $existing_id = $result->fetch_assoc()['id'];
                $delete_confidence_stmt->bind_param("i", $existing_id);
                $delete_confidence_stmt->execute();
                $confidence_modified = true;
            } else {
                $confidence_added = true;
            }
            
            // Now, insert the new confidence pick using the pre-prepared statement
            $insert_pick_stmt->bind_param("iiss", $account_id, $game_id, $pick_team, $pick_type);
            $insert_pick_stmt->execute();
    
        } else { // Handle "normal" and "tiebreaker" picks
            $check_pick_stmt->bind_param("iis", $account_id, $game_id, $pick_type);
            $check_pick_stmt->execute();
            $result = $check_pick_stmt->get_result();
            
            if ($result->num_rows > 0) { // Update existing pick
                $update_pick_stmt->bind_param("siis", $pick_team, $account_id, $game_id, $pick_type);
                if ($update_pick_stmt->execute() && $update_pick_stmt->affected_rows > 0) {
                    if ($pick_type == "normal") $picks_modified++;
                    if ($pick_type == "tiebreaker") $tiebreaker_modified = true;
                }
            } else { // Add new pick
                $insert_pick_stmt->bind_param("iiss", $account_id, $game_id, $pick_team, $pick_type);
                if ($insert_pick_stmt->execute()) {
                    if ($pick_type == "normal") $picks_added++;
                    if ($pick_type == "tiebreaker") $tiebreaker_added = true;
                }
            }
        }
    }   
} catch (Exception $e) {
    send_json_response(false, "Internal server error: " . $e->getMessage());
}

// Close all prepared statements
$time_stmt->close();
$check_pick_stmt->close();
$update_pick_stmt->close();
$insert_pick_stmt->close();
$check_confidence_stmt->close();
$delete_confidence_stmt->close();
$con->close();

if ($locked_games) {
    send_json_response(false, 'One or more of your picks were for games that have already started and were not saved.');
}

send_json_response(true, 'Picks processed successfully.', [
    'picks_added' => $picks_added,
    'picks_modified' => $picks_modified,
    'tiebreaker_added' => $tiebreaker_added,
    'tiebreaker_modified' => $tiebreaker_modified,
    'confidence_added' => $confidence_added,
    'confidence_modified' => $confidence_modified,
    'futures_modified' => $futures_modified
]);
