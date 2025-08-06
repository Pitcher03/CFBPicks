<?php //------------------All Picks page calls this script----------------------

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

// --- 1. Payload Validation ---
$json_payload = file_get_contents('php://input');
$request_data = json_decode($json_payload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    send_json_response(false, 'Invalid JSON payload received.');
}
if (!isset($request_data['week']) || !isset($request_data['tiebreaker'])) {
    send_json_response(false, 'Invalid request format: week or tiebreaker value not found.');
}
$week = $request_data['week'];
$actual_tiebreaker = (int)$request_data['tiebreaker'];

try {
    // --- 2. Fetch All Necessary Data in Fewer Queries ---
    $update_leaderboard_stmt = $con->prepare("
        UPDATE accounts
        SET score = (
            COALESCE((
                SELECT SUM(score)
                FROM scores
                WHERE account_id = accounts.id
            ), 0) +
            COALESCE((
                SELECT SUM(CASE WHEN account_id_2 IS NOT NULL THEN bonus_points / 2 ELSE bonus_points END)
                FROM winners
                WHERE account_id = accounts.id OR account_id_2 = accounts.id
            ), 0) +
            COALESCE((
                SELECT
                    (CASE WHEN user_picks.p12 = results.p12 AND user_picks.p12 != '' THEN 5 ELSE 0 END) +
                    (CASE WHEN user_picks.mac = results.mac AND user_picks.mac != '' THEN 5 ELSE 0 END) +
                    (CASE WHEN user_picks.mtn = results.mtn AND user_picks.mtn != '' THEN 5 ELSE 0 END) +
                    (CASE WHEN user_picks.b12 = results.b12 AND user_picks.b12 != '' THEN 5 ELSE 0 END) +
                    (CASE WHEN user_picks.aac = results.aac AND user_picks.aac != '' THEN 5 ELSE 0 END) +
                    (CASE WHEN user_picks.b10 = results.b10 AND user_picks.b10 != '' THEN 5 ELSE 0 END) +
                    (CASE WHEN user_picks.sec = results.sec AND user_picks.sec != '' THEN 5 ELSE 0 END) +
                    (CASE WHEN user_picks.usa = results.usa AND user_picks.usa != '' THEN 5 ELSE 0 END) +
                    (CASE WHEN user_picks.sun = results.sun AND user_picks.sun != '' THEN 5 ELSE 0 END) +
                    (CASE WHEN user_picks.acc = results.acc AND user_picks.acc != '' THEN 5 ELSE 0 END) +
                    (CASE WHEN user_picks.seed_1 = results.seed_1 AND user_picks.seed_1 != '' THEN 5 ELSE 0 END) +
                    (CASE WHEN user_picks.seed_2 = results.seed_2 AND user_picks.seed_2 != '' THEN 5 ELSE 0 END) +
                    (CASE WHEN user_picks.seed_3 = results.seed_3 AND user_picks.seed_3 != '' THEN 5 ELSE 0 END) +
                    (CASE WHEN user_picks.seed_4 = results.seed_4 AND user_picks.seed_4 != '' THEN 5 ELSE 0 END) +
                    (CASE WHEN user_picks.armynavy = results.armynavy AND user_picks.armynavy != '' THEN 5 ELSE 0 END) +
                    (CASE WHEN user_picks.champion = results.champion AND user_picks.champion != '' THEN 10 ELSE 0 END)
                FROM futures AS user_picks, futures AS results
                WHERE user_picks.account_id = accounts.id AND results.account_id = 0
            ), 0)
        ) 
        WHERE id != 0;
    ");

    // Get all finalized games for the week
    $games_stmt = $con->prepare("SELECT id, value, underdog, bonus, winner FROM games WHERE week = ? AND winner IS NOT NULL;");
    $games_stmt->bind_param("i", $week);
    $games_stmt->execute();
    $finalized_games_result = $games_stmt->get_result();
    $finalized_games = $finalized_games_result->fetch_all(MYSQLI_ASSOC);
    $games_stmt->close();

    // Get all picks for all games in the current week
    $game_ids = array_column($finalized_games, 'id');
    
    if (empty($game_ids)) { // should any scores be set to 0 anyways..?
        $clear_scores_stmt = $con->prepare("UPDATE scores SET score = 0 WHERE week_num = ?");
        $clear_scores_stmt->bind_param("i", $week);
        $clear_winners_stmt = $con->prepare("UPDATE winners SET account_id = null, account_id_2 = null WHERE week_num = ?");
        $clear_winners_stmt->bind_param("i", $week);
        
        if ($clear_scores_stmt->execute() && $clear_winners_stmt->execute() && $update_leaderboard_stmt->execute()) {                send_json_response(true, 'Set weekly scores to 0 and winner to null.');
        } else {
            send_json_response(false, 'Error executing cleanup statements.');
        }
    }
    
    $placeholders = implode(',', array_fill(0, count($game_ids), '?'));
    $types = str_repeat('i', count($game_ids));

    $picks_stmt = $con->prepare("SELECT account_id, game_id, pick, type FROM picks WHERE game_id IN ($placeholders)");
    $picks_stmt->bind_param($types, ...$game_ids);
    $picks_stmt->execute();
    $picks_result = $picks_stmt->get_result();
    $all_picks_raw = $picks_result->fetch_all(MYSQLI_ASSOC);
    $picks_stmt->close();

    // Organize picks by account_id for easy lookup
    $picks_by_account = [];
    foreach ($all_picks_raw as $pick) {
        $picks_by_account[$pick['account_id']][$pick['game_id']][$pick['type']] = $pick['pick'];
    }
    
    // --- 3. Calculate Scores for Each Player ---
    $weekly_scores = [];
    foreach ($picks_by_account as $account_id => $player_picks) {
        $week_score = 0;
        $confidence_pick = null;

        foreach($player_picks as $game_picks) {
            if (isset($game_picks['confidence'])) {
                $confidence_pick = $game_picks['confidence'];
                break;
            }
        }
        
        foreach ($finalized_games as $game) {
            $game_id = $game['id'];
            if (!isset($player_picks[$game_id]['normal'])) continue;

            $picked_team = $player_picks[$game_id]['normal'];

            if ($game['winner'] === $picked_team) {
                $point_value = $game['value'];
                
                if ($game['underdog'] === $picked_team) {
                    $point_value += $game['bonus'];
                }
                
                if ($confidence_pick === $picked_team) {
                    $point_value *= 2;
                }
                
                $week_score += $point_value;
            }
        }
        $weekly_scores[$account_id] = $week_score;
    }

    // --- 4. Insert or Update Scores in the Database ---
    $scores_updated = 0;
    $scores_inserted = 0;
    $find_score_stmt = $con->prepare("SELECT score FROM scores WHERE account_id = ? AND week_num = ?");
    $update_score_stmt = $con->prepare("UPDATE scores SET score = ? WHERE account_id = ? AND week_num = ?");
    $insert_score_stmt = $con->prepare("INSERT INTO scores (account_id, week_num, score) VALUES (?, ?, ?)");

    foreach ($weekly_scores as $account_id => $score) {
        $find_score_stmt->bind_param("ii", $account_id, $week);
        $find_score_stmt->execute();
        $result = $find_score_stmt->get_result();
        $score_row = $result->fetch_assoc();
        
        if (empty($score_row)) {
            $insert_score_stmt->bind_param("iii", $account_id, $week, $score);
            $insert_score_stmt->execute();
            $scores_inserted++;
        } else {
            $old_score = $score_row['score'];
            if ($score != $old_score) {
                $update_score_stmt->bind_param("iii", $score, $account_id, $week);
                $update_score_stmt->execute();
                $scores_updated++;
            }
        }
    }
    $update_score_stmt->close();
    $insert_score_stmt->close();

    // --- 5. Determine Weekly Winner if All Games are Final ---
    $winner_updated = false;
    $games_remaining_stmt = $con->prepare("SELECT COUNT(*) as remaining FROM games WHERE week = ? AND winner IS NULL");
    $games_remaining_stmt->bind_param("i", $week);
    $games_remaining_stmt->execute();
    $games_remaining = $games_remaining_stmt->get_result()->fetch_assoc()['remaining'];
    $games_remaining_stmt->close();

    // find the weekly winner(s) if the games have all ended
    if ($games_remaining == 0) {
        $max_score = max($weekly_scores);
        $potential_winners = array_keys($weekly_scores, $max_score);
        
        $winner_ids = [];
        if (count($potential_winners) === 1) { // one winner
            $winner_ids = $potential_winners;
        } else { // need to find tiebreaker
            $min_diff = PHP_INT_MAX;
            $tied_winners = [];

            foreach ($potential_winners as $p_id) {
                $player_tb_pick = 99; // default tiebreaker
                foreach($picks_by_account[$p_id] as $game_picks) {
                    if(isset($game_picks['tiebreaker'])) {
                        $player_tb_pick = (int)$game_picks['tiebreaker'];
                        break;
                    }
                }

                $diff = abs($player_tb_pick - $actual_tiebreaker);
                
                if ($diff < $min_diff) {
                    $min_diff = $diff;
                    $tied_winners = [$p_id];
                } elseif ($diff === $min_diff) {
                    $tied_winners[] = $p_id;
                }
            }
            $winner_ids = $tied_winners;
        }
        
        if (!empty($winner_ids)) {
            if (isset($winner_ids[1])) {
                $winner_stmt = $con->prepare("UPDATE winners SET account_id = ?, account_id_2 = ? WHERE week_num = ?");
                $winner_1 = $winner_ids[0];
                $winner_2 = isset($winner_ids[1]) ? $winner_ids[1] : null;
                $winner_stmt->bind_param("iii", $winner_1, $winner_2, $week);
            } else {
                $winner_stmt = $con->prepare("UPDATE winners SET account_id = ? WHERE week_num = ?");
                $winner_stmt->bind_param("ii", $winner_ids[0], $week);
            }
            $winner_stmt->execute();
            $winner_stmt->close();
            $winner_updated = true;
        }
    } else { // clear winners from table
        $clear_winners_stmt = $con->prepare("UPDATE winners SET account_id = null, account_id_2 = null WHERE week_num = ?");
        $clear_winners_stmt->bind_param("i", $week);
        $clear_winners_stmt->execute();
        $clear_winners_stmt->close();
    }
    
    // --- 6. Update everyone's season score ---
    if (!$update_leaderboard_stmt->execute()) {
        send_json_response(false, 'Fatal Error executing massive query :(');
    }

    $con->close();
    send_json_response(true, 'Player scores updated successfully.', [
        'scores_updated' => $scores_updated,
        'scores_inserted' => $scores_inserted,
        'winner_updated' => $winner_updated
    ]);

} catch (Exception $e) {
    send_json_response(false, 'An exception occurred: ' . $e->getMessage());
}

