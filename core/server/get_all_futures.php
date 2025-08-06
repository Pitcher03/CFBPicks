<?php

header('Content-Type: application/json');
session_start();
require_once 'connection.php';

$stmt = $con->prepare("SELECT * FROM futures");
$stmt->execute();
$result = $stmt->get_result();

$futures = [];
if ($result) {
    $futures = $result->fetch_all(MYSQLI_ASSOC);
}

echo json_encode($futures);

$stmt->close();
$con->close();
