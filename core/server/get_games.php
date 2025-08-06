<?php

header('Content-Type: application/json');
session_start();
require_once 'connection.php';

$json_payload = file_get_contents('php://input');
$request_data = json_decode($json_payload, true);

if (!isset($request_data['week'])) {
    echo json_encode(['error' => 'Week parameter is required.']);
    exit;
}

$week = (int)$request_data['week'];

$stmt = $con->prepare("SELECT * FROM games WHERE week = ? ORDER BY date ASC");
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
