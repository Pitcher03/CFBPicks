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

$stmt = $con->prepare("SELECT * FROM futures WHERE account_id = ?");
$stmt->bind_param("i", $account_id);
$stmt->execute();
$result = $stmt->get_result();

$futures;
if ($result) {
    $futures = $result->fetch_assoc();
}

echo json_encode($futures);

$stmt->close();
$con->close();
