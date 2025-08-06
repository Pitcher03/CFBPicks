<?php

date_default_timezone_set('America/Chicago');
$phoneNumber = "4258706173@vtext.com";
$scoreboardUrl = "https://cfbpicks.live/live%20scores/scoring_updates.json";
$logFilePath = "/home/y956w8mybugv/public_html/live scores/emails.log";

function write_to_log($message, $logFile) {
    if (!is_writable(dirname($logFile))) {
        error_log("Log directory is not writable: " . dirname($logFile));
        return;
    }
    $datetime = new DateTime("now");
    $timestamp = $datetime->format('Y-m-d H:i-s');
    $log_entry = "[$timestamp] - $message" . PHP_EOL;
    file_put_contents($logFile, $log_entry, FILE_APPEND | LOCK_EX);
}

function split_message($updates, $limit = 140) {
    if (empty($updates)) {
        return [];
    }
    $chunks = [];
    $current_chunk = '';

    foreach ($updates as $update) {
        $change_text = $update['change'];
        $text_to_add = empty($current_chunk) ? $change_text : ", " . $change_text;

        if (strlen($current_chunk . $text_to_add) > $limit) {
            $chunks[] = $current_chunk;
            $current_chunk = $change_text;
        } else {
            $current_chunk .= $text_to_add;
        }
    }

    if (!empty($current_chunk)) {
        $chunks[] = $current_chunk;
    }
    
    return $chunks;
}

try {
    $txt = file_get_contents($scoreboardUrl);
    if ($txt === false) {
        write_to_log("Failed to fetch scoreboard URL.", $logFilePath);
        exit;
    }

    $data = json_decode($txt, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($data['scoreboard'])) {
        write_to_log("Failed to parse JSON or 'scoreboard' key missing.", $logFilePath);
        exit;
    }

    $scoringUpdates = $data['scoreboard'];
    $lastUpdated = $data['last_updated'];
    
    if (!empty($scoringUpdates)) {
        $messages_to_send = split_message($scoringUpdates);
        $total_messages = count($messages_to_send);
        
        foreach ($messages_to_send as $index => $message_chunk) {
            $current_num = $index + 1;
            
            $subject = $lastUpdated . " $current_num/$total_messages";
            $message = $message_chunk;
            $headers = "From: scores@cfbpicks.live";

            write_to_log("Sending message $current_num/$total_messages: " . trim($message), $logFilePath);
            mail($phoneNumber, $subject, trim($message), $headers);
            
            if ($current_num < $total_messages) {
                sleep(3);
            }
        }
    } else {
        write_to_log("No new scoring updates found.", $logFilePath);
    }

} catch (Exception $e) {
    $errorMessage = "SMS Script Error: " . $e->getMessage();
    error_log($errorMessage);
    write_to_log($errorMessage, $logFilePath);
}