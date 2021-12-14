<?php
include 'Telegram-Core.php';
include_once 'inc/db.php';
include_once 'func/gen_tbl_id.php';


$bot_token = 'bot:token';
$telegram = new Telegram($bot_token);
$text = $telegram->Text(); //user sent messga
$chat_id = $telegram->ChatID();

$username = $telegram->Username();
$userID = $telegram->UserID();
$fName = $telegram->FirstName();
$lName = $telegram->LastName();

function getFirstUser(){
    return custom_query("SELECT id FROM users LIMIT 1");
}


if ($text == "/truth_joingame"){
    // Check if userID exists in table
    $userID_exists = record_info_in_tbl("userID", "userID", "$userID", "users");

    // User already in game
    if ($userID_exists) {
        if (empty($fName)) $response = "Sorry $username, But you are already in game";
        else if (!empty($fName)) $response = "Sorry $fName, But you are already in game";
    }

    // User join game
    else if (!$userID_exists) {
        $new_id = get_new_id("id", "users");
        $sql = "INSERT INTO `users` (`id`, `username`, `userID`, `fName`, `lName`) VALUES (:id,:username,:userID,:fName,:lName);";
        $stmt = $conn->prepare($sql);
        try{
            $stmt->execute(array(':id' => $new_id, ':username' => $username, ':userID' => $userID, ':fName' => $fName, ':lName' => $lName));
            if (empty($fName)) $response = "$username has joined the game";
            else if (!empty($fName)) $response = "$fName has joined the game";
        }
        catch(PDOException $e) {
            echo json_encode(array("statusCode" => $e->getMessage()));
            return 0;
        }
    }

    $content = array('chat_id' => $chat_id, 'text' => $response);
    $telegram->sendMessage($content);
}

else if ($text == "/truth_leavegame") {
    // Check if userID exists in table
    $userID_exists = record_info_in_tbl("userID", "userID", "$userID", "users");

    // User in game
    if ($userID_exists) {
        $sql = "DELETE FROM users WHERE userID='$userID'";
        if ($conn->query($sql) == TRUE) {
            if (empty($fName)) $response = "$username has left the game!";
            else if (!empty($fName)) $response = "$fName has left the game!";
        }

    }

    // User not in game
    else if (!$userID_exists) {
        if (empty($fName)) $response = "Sorry $username, You are not in game!";
        else if (!empty($fName)) $response = "Sorry $fName, You are not in game";

    }

    $content = array('chat_id' => $chat_id, 'text' => $response);
    $telegram->sendMessage($content);
}

else if ($text == "/truth_startgame"){

    // Check if user is in game
    $userID_exists = custom_query("SELECT COUNT(*) FROM users WHERE userID=$userID");

    // User is in game
    if ($userID_exists) {
        // Check if game is already started
        $game_started = custom_query("SELECT val FROM data WHERE col='game_started'");

        // Game has not been started
        if (!$game_started) {
            $count_users = custom_query("SELECT COUNT(*) FROM users");
            // No players
            if ($count_users<1) $response = "Sorry, There are no players!";

            // Only one player
            else if ($count_users==1) $response = "Sorry, Cant start game with 1 player only!";

            // More than two players
            else if ($count_users>1) {

                // Set turn
                $sql = "UPDATE data SET val=:val WHERE col=:col";
                $stmt = $conn->prepare($sql);
                $stmt->execute(array(':val' => getFirstUser(), ':col' => 'current_turn'));
                $affected_rows = $stmt->rowCount();

                $sql = "UPDATE data SET val=:val WHERE col=:col";
                $stmt = $conn->prepare($sql);
                $stmt->execute(array(':val' => '1', ':col' => 'game_started'));
                $affected_rows = $stmt->rowCount();
                if ($affected_rows == 1) $response = "Game has been started!\nPlease use /truth_nextturn to start playing!";
                else $response = "Error while starting the game!";
            }

            $content = array('chat_id' => $chat_id, 'text' => $response);
            $telegram->sendMessage($content);
    }
    // User is not in game
    else if (!$userID_exists) {
        $content = array('chat_id' => $chat_id, 'text' => "Sorry, But you are not in game!");
        $telegram->sendMessage($content);
    }


    }

    // Game has been already started
    else if ($game_started) {
        $content = array('chat_id' => $chat_id, 'text' => "Sorry, Game has been already started!\nUse /truth_stopgame to stop the game.");
        $telegram->sendMessage($content);
    }

}

else if ($text == "/truth_stopgame") {
    // Check if game is already started
    $game_started = custom_query("SELECT val FROM data WHERE col='game_started'");

    // Game has already been started
    if ($game_started) {
        // Stop the game
        $sql = "UPDATE data SET val=:val WHERE col=:col";
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':val' => '0', ':col' => 'game_started'));
        $affected_rows = $stmt->rowCount();
        if ($affected_rows == 1) $response = "Game has been stopped!";
        else $response = "Error while stopping the game!";

        $content = array('chat_id' => $chat_id, 'text' => $response);
        $telegram->sendMessage($content);
    }

    // Game has not been started
    else if (!$game_started) {
        $content = array('chat_id' => $chat_id, 'text' => "Sorry, Game has not been started!\nUse /truth_startgame to start the game.");
        $telegram->sendMessage($content);
    }
}

else if ($text == "/truth_resetquest") {
    custom_query("UPDATE questions SET used=0"); // Mark all questions as not used
    $content = array('chat_id' => $chat_id, 'text' => "Done resetting questions.");
    $telegram->sendMessage($content);
}

else if ($text == "/truth_nextturn" || $text == "/truth_nextturn@clean_truth_game_bot" || $text == "121."){
    $user_turn = custom_query("SELECT val FROM data WHERE col='current_turn'");

    if (!isset($user_turn) || empty($user_turn)) $user_turn = getFirstUser();

    $turn_fName = custom_query("SELECT fName FROM users WHERE id=$user_turn");
    $random_question = custom_query("SELECT question FROM questions WHERE used='0'");

    // If found question
    if (!empty($random_question))  {
        custom_query("UPDATE questions SET used=1 WHERE question='$random_question'"); // Mark question as used

        // Set turn for next user
        $next_turn = custom_query("SELECT id FROM users WHERE id > '$user_turn' ORDER BY id ASC LIMIT 1");

        // Didnt find next turn
        if (!$next_turn) $next_turn = getFirstUser();

        // Update next turn
        custom_query("UPDATE data SET val=$next_turn WHERE col='current_turn'");

        $content = array('chat_id' => $chat_id, 'text' => "Its [$turn_fName] turns\n\n$random_question\n\nYou can use /truth_nextturn for next turn.");
        $telegram->sendMessage($content);
    }
    // Didnt find any question
    else if (empty($random_question)) {
        $content = array('chat_id' => $chat_id, 'text' => "Sorry, All questions has been already used.\nUser /truth_resetquest To reset all questions.");
        $telegram->sendMessage($content);
    }
}

else if ($text == "/truth_help") {
    $content = array('chat_id' => $chat_id, 'text' => "Truth Game Bot Help\n
    /truth_joingame -> Join Game.
    /truth_leavegame ->Leave Game.
    /truth_startgame -> Start Game.
    /truth_stopgame -> Stop Game.
    /truth_nextturn -> Next turn.
    /truth_resetquest -> Reset All Used Questions.\n
    Made With ♥ By Suleiman");
    $telegram->sendMessage($content);
}
