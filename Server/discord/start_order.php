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

$find_username = $conn->prepare("SELECT * FROM users WHERE discord_id=:discord_id");
$find_username->bindValue(':discord_id', $_POST['discord_id']);
$find_username->execute();
$result = $find_username->fetch();
if ($find_username->rowCount() < 1) {
    // Account doesn't exist.
    $response = array('status' => 'failed', 'detail' => 'no account linked to id');
    die(json_encode($response));
}

$create_order = $conn->prepare("INSERT INTO orders (order_id, username, discord_id, time, value_in_btc, address, sub_time) VALUES (:order_id, :username, :discord_id, :time, :value_in_btc, :address, :sub_time);");
$t = time();
$temp_order_id = md5($result['username'] . $_POST['discord_id']) . '-' . $t;
$create_order->bindValue(':order_id', $temp_order_id);
$create_order->bindValue(':username', $result['username']);
$create_order->bindValue(':discord_id', $_POST['discord_id']);
$create_order->bindValue(':time', $t);
$create_order->bindValue(':sub_time', $_POST["time"]); //how many months of sub

$cost = file_get_contents("https://blockchain.info/tobtc?currency=USD&value=" . (17 * intval($_POST["time"])));
$create_order->bindValue(':value_in_btc', $cost); //17USD * time_in_months to BTC^

$btc_addr = get_unique_btc_addr($conn); //need to make this changeable between native segwit and non native
$create_order->bindValue(':address', $btc_addr);
$create_order->execute();

$response = array('status' => 'success', 'detail' => array('cost' => $cost, 'btc_addr' => $btc_addr, 'order_id' => $temp_order_id, 'sub_time' => $_POST["time"]));
die(json_encode($response));

function secret_directory($fileName)
{
    return '../authentication/private_folder_authentication/' . $fileName;
}

function get_unique_btc_addr($conn)
{
    $get_addr = $conn->prepare("SELECT addr FROM addresses LIMIT 1");
    $get_addr->execute();
    $result = $get_addr->fetch();

    $remove_addr = $conn->prepare("DELETE FROM addresses WHERE addr=:addr");
    $remove_addr->bindValue(':addr', $result['addr']);
    $remove_addr->execute();

    return $result['addr'];
}
