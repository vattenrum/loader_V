<?php
    date_default_timezone_set('US/Eastern'); // Set time zone for logging.
    ini_set('log_errors', 1);
    ini_set('display_errors', 0);

    $server_server = "localhost";
    $server_username = "dsafhads78f0y453h4t53hdshfda-98d";
    $server_password = "dsafhads78f0y453h4t53hdshfda-98d";
    $server_dbname = "dsafhads78f0y453h4t53hdshfda-98d";
    $server_status = "online";

    try {
        $conn = new PDO('mysql:host=' . $server_server . ';dbname=' . $server_dbname, $server_username, $server_password, [PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    } catch (PDOException $e) {
        $response = array('status' => 'failed', 'detail' => 'connection error');
        die(json_encode($response));
    }

$success = 0;
$failed = 0;
$deleted = 0;
$get_orders = $conn->prepare("SELECT * FROM orders");
$get_orders->execute();
while ($row = $get_orders->fetch(PDO::FETCH_ASSOC)) {
    $balance = file_get_contents("https://blockchain.info/q/addressbalance/" . $row['address'] . "?confirmations=1"); //get information

    $required_balance = $row['value_in_btc'];
    if (floatval($balance) >= floatval($required_balance)) {
        $check_user_exists = $conn->prepare("SELECT * FROM users WHERE username=:username");
        $check_user_exists->bindValue(':username', $row['username']);
        $check_user_exists->execute();
        $username_result = $check_user_exists->fetch();
        if ($check_user_exists->rowCount() == 0) {
            $failed++;
            continue;
        }

        $current_expire_val = (int)$username_result['expire'];
        if ($current_expire_val >= 2000000000) {
            $failed++;
            continue;
        }

        // Key hasn't been used yet, username is valid. Establish expiration date.
        $expire_val = 0;
        // Add days/months/years to current time (add weeks * 7 to days, since DateInterval doesn't accept weeks.)
        $months_to_add = intval($row["sub_time"]);
        $date = new DateTime();
        $date->setTimestamp($current_expire_val > time() ? $current_expire_val : time());
        $date->add(new DateInterval("P" . $months_to_add . "M"));
        $expire_val = $date->getTimestamp();
        
        // Update users expiration time.
        $update_account = $conn->prepare("UPDATE users SET expire=:expire, autobuy_usage=autobuy_usage+1 WHERE username=:username");
        $update_account->bindValue(':username', $row['username']);
        $update_account->bindValue(':expire', $expire_val);
        $update_account->execute();

		$find_user = $conn->prepare("SELECT discord_id FROM users WHERE username=:username");
		$find_user->bindValue(":username", $row['username']);
		$find_user->execute();
		$user_result = $find_user->fetch();

		$notify = $conn->prepare("INSERT INTO finished_orders (user_notified, order_id, discord_id) VALUES (0, :order_id, :discord_id);");
		$notify->bindValue(":order_id", $row["order_id"]);
		$notify->bindValue(":discord_id", $user_result["discord_id"]);

        //remove from orders list
        $remove_order = $conn->prepare("DELETE FROM orders WHERE order_id=:order_id");
        $remove_order->bindValue(':order_id', $row['order_id']);
        $remove_order->execute();
        $success++;
        sleep(3);
    } else {
        $failed++;
    }
}

$delete_old_orders = $conn->prepare("DELETE FROM orders WHERE :time-time>1209600"); // 1209600 - 2 weeks
$delete_old_orders->bindValue(":time", time());
$delete_old_orders->execute();
$deleted = $delete_old_orders->rowCount();
    
$response = "failed: $failed, succeeded: $success, deleted: $deleted";
die($response);