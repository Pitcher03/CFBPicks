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

$week = null;
if (isset($request_data['week'])) {
    $week = $request_data['week'];
}

try {
    if ($week == null) {
        $stmt = $con->prepare("SELECT * FROM winners");
    } else {
        $stmt = $con->prepare("SELECT * FROM winners WHERE week_num = ?");
        $stmt->bind_param("i", $week);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $winners = [];
    if ($result) {
        $winners = $result->fetch_all(MYSQLI_ASSOC);
    }

    echo json_encode($winners);

    $stmt->close();
    $con->close();
} catch (Exception $e) {
    send_json_response(false, 'An exception occurred: ' . $e->getMessage());
}

