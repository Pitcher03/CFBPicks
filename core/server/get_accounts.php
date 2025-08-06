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

try {
    $stmt = $con->prepare("SELECT id, username, score FROM accounts");
    $stmt->execute();
    $result = $stmt->get_result();
    $accounts = [];
    if ($result) {
        $accounts = $result->fetch_all(MYSQLI_ASSOC);
    }

    echo json_encode($accounts);

    $stmt->close();
    $con->close();
} catch (Exception $e) {
    send_json_response(false, 'An exception occurred: ' . $e->getMessage());
}

