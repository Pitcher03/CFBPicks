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
if (!isset($request_data['week'])) {
    send_json_response(false, 'Invalid request format: week not found.');
}
$week = $request_data['week'];

$stmt = $con->prepare("SELECT week_num, account_id, score FROM scores WHERE week_num = ? ORDER BY score");
$stmt->bind_param("i", $week);
$stmt->execute();
$result = $stmt->get_result();
$scores = [];
if ($result) {
    $scores = $result->fetch_all(MYSQLI_ASSOC);
}

echo json_encode($scores);

$stmt->close();
$con->close();