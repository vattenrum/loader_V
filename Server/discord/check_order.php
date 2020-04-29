<?php
date_default_timezone_set('US/Eastern'); // Set time zone for logging.
ini_set('log_errors', 1);
ini_set('display_errors', 0);

if ($_SERVER['HTTP_USER_AGENT'] != 'VER$ACE-DISCORD-BOT') {
    $response = array('status' => 'failed', 'detail' => 'connection error');
    die(json_encode($response));
}

include "../include/config.php"; // SQL Server stuff
include "../include/functions.php";
$server_server = $config['server'];
$server_username = $config['username'];
$server_password = $config['password'];
$server_dbname = $config['dbname'];
$server_status = $config['status'];

try {
    $conn = new PDO('mysql:host=' . $server_server . ';dbname=' . $server_dbname, $server_username, $server_password, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (PDOException $e) {
    $response = array('status' => 'failed', 'detail' => 'connection error');
    die(json_encode($response));
}

$find_order = $conn->prepare("SELECT * FROM orders WHERE order_id=:order_id");
$find_order->bindValue(':order_id', $_POST['order_id']);
$find_order->execute();
$result = $find_order->fetch();
if ($find_order->rowCount() < 1) {
    // order doesn't exist.
    $response = array('status' => 'failed', 'detail' => 'no order by that id.');
    die(json_encode($response));
}

if ($result['discord_id'] != $_POST['discord_id']) {
    $response = array('status' => 'failed', 'detail' => 'order doesnt belong to discord id.');
    die(json_encode($response));
}

$json_of_addr = utf8_encode(file_get_contents("https://api.blockcypher.com/v1/btc/main/addrs/" . $result['address'])); //get information
$res = json_decode($json_of_addr, true);

$balance = $res["balance"];
$required_balance = $result['value_in_btc'];
if (floatval($balance) >= floatval($required_balance)) {
    $response = array('status' => 'success', 'detail' => 'order paid, account updated');

    $check_user_exists = $conn->prepare("SELECT * FROM users WHERE username=:username");
    $check_user_exists->bindValue(':username', $result['username']);
    $check_user_exists->execute();
    $username_result = $check_user_exists->fetch();
    if ($check_user_exists->rowCount() == 0) {
        // Username doesn't exist.
        $response = array('status' => 'failed', 'detail' => 'user not found');
        die(json_encode($response));
    }

    $current_expire_val = (int)$username_result['expire'];
    if ($current_expire_val >= 2000000000) {
        // User already has lifetime.
        $response = array('status' => 'failed', 'detail' => 'already lifetime');
        die(json_encode($response));
    }

    $expire_val = 0;
    // Add days/months/years to current time (add weeks * 7 to days, since DateInterval doesn't accept weeks.)
    $months_to_add = intval($result["sub_time"]);
    $date = new DateTime();
    $date->setTimestamp($current_expire_val > time() ? $current_expire_val : time());
    $date->add(new DateInterval("P" . $months_to_add . "M"));
    $expire_val = $date->getTimestamp();

    // Update users expiration time.
    $update_account = $conn->prepare("UPDATE users SET expire=:expire WHERE username=:username");
    $update_account->bindValue(':username', $result['username']);
    $update_account->bindValue(':expire', $expire_val);
    $update_account->execute();

    $update_account = $conn->prepare("UPDATE users SET autobuy_usage=autobuy_usage+1 WHERE username=:username");
    $update_account->bindValue(':username', $result['username']);
    $update_account->execute();

    //remove from orders list
    $remove_order = $conn->prepare("DELETE FROM orders WHERE order_id=:order_id");
    $remove_order->bindValue(':order_id', $_POST['order_id']);
    $remove_order->execute();

    die(json_encode($response));
} else {
    $response = array('status' => 'failed', 'detail' => 'order not fulfilled - either unconfirmed (requires 1 conf) or not enough');
    die(json_encode($response));
}

function secret_directory($fileName)
{
    return '../authentication/private_folder_authentication/' . $fileName;
}
