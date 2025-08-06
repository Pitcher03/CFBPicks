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
    send_json_response(false, 'Authentication error: Attempted to find stats but user is logged out.');
}

$account_id = $_SESSION['user_id'];
$json_payload = file_get_contents('php://input');
$request_data = json_decode($json_payload, true);

if (!isset($request_data['week']) || !isset($request_data['key'])) {
    echo json_encode(['error' => 'Week and Key parameters required.']);
    exit;
}

$week = (int)$request_data['week'];
$key = $request_data['key'];
$week_stats = null;
$season_stats = null;
$empty_picks = null;
$bonus_points = null;

try {
    $week_stmt = $con->prepare("
    WITH WeeklyRanks AS (
      SELECT account_id, score, RANK() OVER (ORDER BY score DESC) AS user_rank
      FROM scores
      WHERE week_num = ?
    )
    SELECT wr.score, wr.user_rank, (SELECT COUNT(*) FROM WeeklyRanks) AS total_players
    FROM WeeklyRanks wr
    WHERE wr.account_id = ?");
    $week_stmt->bind_param("ii", $week, $account_id);
    $week_stmt->execute();
    $result = $week_stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $week_stats = $row;
    }
    
    $season_stmt = $con->prepare("
    WITH SeasonRanks AS (
      SELECT id, score, RANK() OVER (ORDER BY score DESC) AS user_rank
      FROM accounts
    )
    SELECT sr.score, sr.user_rank, (SELECT COUNT(*)-1 FROM SeasonRanks) AS total_players
    FROM SeasonRanks sr
    WHERE sr.id = ?");
    $season_stmt->bind_param("i", $account_id);
    $season_stmt->execute();
    $result = $season_stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $season_stats = $row;
    }
    
    $picks_stmt = $con->prepare("SELECT ((SELECT COUNT(*) FROM games WHERE week = ?) + 3 - (SELECT COUNT(*) FROM picks WHERE account_id = ? AND game_id IN (SELECT id FROM games WHERE week = ?)) - (SELECT COUNT(*) FROM futures WHERE account_id = ? AND LENGTH(" . $key . ") > 0)) AS empty_picks");
    $picks_stmt->bind_param("iiii", $week, $account_id, $week, $account_id);
    $picks_stmt->execute();
    $result = $picks_stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $empty_picks = $row['empty_picks'];
    }
    
    $bonus_stmt = $con->prepare("SELECT bonus_points FROM winners WHERE week_num = ?");
    $bonus_stmt->bind_param("i", $week);
    $bonus_stmt->execute();
    $result = $bonus_stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $bonus_points = $row['bonus_points'];
    }
    
    $week_stmt->close();
    $season_stmt->close();
    $picks_stmt->close();
    $bonus_stmt->close();
} catch (Exception $ex) {
    send_json_response(false, 'Failed to retrieve statistics: ' . $ex->getMessage());
}

$con->close();

send_json_response(true, 'Statistics retrieved successfully.', [
    'week_stats' => $week_stats, 
    'season_stats' => $season_stats, 
    'empty_picks' => $empty_picks,
    'bonus_points' => $bonus_points]);

