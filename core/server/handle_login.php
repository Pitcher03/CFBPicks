<?php

session_start();
header('Content-Type: application/json');
require_once 'connection.php';

function send_error($error_code, $message) {
    echo json_encode([
        'success' => false,
        'error'   => $error_code,
        'message' => $message
    ]);
    exit();
}

if (!isset($_POST['username']) || !isset($_POST['password']) || !isset($_POST['type'])) {
    send_error('missing_fields', 'Required fields are missing.');
}

$username = trim($_POST['username']);
$password = $_POST['password'];
$type = $_POST['type']; 

if ($type === 'login') {
    $stmt = $con->prepare("SELECT id, password_hash FROM accounts WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        send_error('username_not_found', 'Username not found. Would you like to create a new account?');
    }

    $user = $result->fetch_assoc();
    
    // login successful
    if (password_verify($password, $user['password_hash'])) {
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $username;
        if ($username == "Caden") {
            $_SESSION['admin'] = true;
        } else {
            $_SESSION['admin'] = false;
        }
        echo json_encode(['success' => true]);
    } else {
        send_error('incorrect_password', 'Incorrect password.');
    }
    $stmt->close();
} elseif ($type === 'register') {
    if (strlen($username) < 3 || strlen($username) > 20) {
        send_error('bad_username', 'Username must be 3-20 characters long.');
    }
    if (strlen($password) < 8 || strlen($password) > 30) {
        send_error('bad_password', 'Password must be 8-30 characters long.');
    }

    $stmt = $con->prepare("SELECT id FROM accounts WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        send_error('username_exists', 'That username is already taken.');
    }
    $stmt->close();

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $con->prepare("INSERT INTO accounts (username, password_hash) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $password_hash);

    if ($stmt->execute()) {
        $new_user_id = $stmt->insert_id;
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $new_user_id;
        $_SESSION['username'] = $username;
        $_SESSION['admin'] = ($username == "Caden");
    
        $con->begin_transaction();
    
        try {
            $futures_stmt = $con->prepare("INSERT INTO futures (account_id) VALUES (?)");
            $futures_stmt->bind_param("i", $new_user_id);
            $futures_stmt->execute();
            $futures_stmt->close();
    
            $scores_stmt = $con->prepare("INSERT INTO scores (account_id, week_num, score) VALUES (?, ?, 0)");
            $week_num = 0;
            $scores_stmt->bind_param("ii", $new_user_id, $week_num);
    
            for ($week_num = 0; $week_num <= 16; $week_num++) {
                $scores_stmt->execute();
            }
            $scores_stmt->close();
    
            $con->commit();
            echo json_encode(['success' => true]);
        } catch (mysqli_sql_exception $exception) {
            $con->rollback();
            error_log("Registration DB Error: " . $exception->getMessage()); 
            send_error('db_error', 'Could not initialize user data. Please contact @Pitcher03_ on instagram.');
        }
    } else {
        send_error('db_error', 'Could not create account. Please try again.');
    }
    $stmt->close();
} else {
    send_error('invalid_type', 'Invalid request type.');
}

$con->close();
