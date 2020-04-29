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

// Successfully connected to the database.

$check_username = $conn->prepare("SELECT * FROM users WHERE username=:username");
$check_username->bindValue(':username', $_POST['username']);
$check_username->execute();
$result = $check_username->fetch();
if ($check_username->rowCount() < 1) {
    // Account doesn't exist.
    $response = array('status' => 'failed', 'detail' => 'no account');
    die(json_encode($response));
}

if ($result['discord_id'] != null) {
    $response = array('status' => 'failed', 'detail' => 'account already linked.');
    die(json_encode($response));
}

if (time() - (int)$result['discord_link_time'] > 120) {
    $response = array('status' => 'failed', 'detail' => 'account link timeout.');
    die(json_encode($response));
}

$set_discord_id = $conn->prepare("UPDATE users SET discord_id=:discord_id WHERE username=:username");
$set_discord_id->bindValue(':username', $_POST['username']);
$set_discord_id->bindValue(':discord_id', $_POST['discord_id']);
$set_discord_id->execute();

$response = array('status' => 'success', 'detail' => 'linked discord account.');
die(json_encode($response));

function secret_directory($fileName)
{
    return '../authentication/private_folder_authentication/' . $fileName;
}
