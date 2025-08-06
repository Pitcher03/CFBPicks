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

if (!isset($request_data['week'])) {
    echo json_encode(['error' => 'Week parameter is required.']);
    exit;
}

$week = (int)$request_data['week'];

$stmt = $con->prepare("SELECT * FROM picks WHERE game_id IN (SELECT id FROM `games` WHERE week = ?)");
$stmt->bind_param("i", $week);
$stmt->execute();
$result = $stmt->get_result();

$games = [];
if ($result) {
    $games = $result->fetch_all(MYSQLI_ASSOC);
}

echo json_encode($games);

$stmt->close();
$con->close();