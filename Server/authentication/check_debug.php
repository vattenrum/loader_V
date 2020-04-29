<?php
date_default_timezone_set('US/Eastern'); // Set time zone for logging.
ini_set('log_errors', 1);
ini_set('display_errors', 0);

if ($_SERVER['HTTP_USER_AGENT'] != 'VER$ACE') {
    $response = array('status' => 'failed', 'detail' => 'connection error');
    die(json_encode($response));
}

include "../include/config.php"; // SQL Server stuff
$server_server = $config['server'];
$server_username = $config['username'];
$server_password = $config['password'];
$server_dbname = $config['dbname'];
$server_status = $config['status'];

if ($server_status != 'online') {
    $response = array('status' => 'failed', 'detail' => 'server offline', 'reason' => $config['reason']);
    die(json_encode($response));
}

try {
    $conn = new PDO('mysql:host=' . $server_server . ';dbname=' . $server_dbname, $server_username, $server_password, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (PDOException $e) {
    $response = array('status' => 'failed', 'detail' => 'connection error');
    die(json_encode($response));
}

if ($_POST['hash'] != $server_client_hash) {
    $set_banned = $conn->prepare("UPDATE users SET banned=:banned WHERE hwid=:hwid");
    $set_banned->bindValue(':hwid', $_POST['hwid']);
    $set_banned->bindValue(':banned', "1");
    $set_banned->execute();
    $response = array('status' => 'failed', 'detail' => 'gluten free vegan oreos');
    die(json_encode($response));
} else {
    $response = array('status' => 'ok', 'detail' => 'ok');
    die(json_encode($response));
}
