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
$check_discord_id = $conn->prepare("SELECT * FROM users WHERE discord_id=:discord_id");
$check_discord_id->bindValue(':discord_id', $_POST['discord_id']);
$check_discord_id->execute();
$result = $check_discord_id->fetch();
if ($check_discord_id->rowCount() < 1) {
    // Account doesn't exist.
    $response = array('status' => 'failed', 'detail' => 'no account linked to discord id.');
    die(json_encode($response));
}


$list_invite_keys = $conn->prepare("SELECT * FROM invite_keys WHERE inviter=:inviter AND used=0");
$list_invite_keys->bindValue(':inviter', $result['username']);
$list_invite_keys->execute();

$unused_invites = array();
while ($row = $list_invite_keys->fetch(PDO::FETCH_ASSOC)) {
    array_push($unused_invites, $row['invite_key']);
}


if (count(unused_invites) == 0) {
    $response = array('status' => 'success', 'detail' => 'no unused and created invite codes.');
    die(json_encode($response));
} else {
    $response = array('status' => 'success', 'detail' => json_encode($unused_invites));
    die(json_encode($response));
}

function secret_directory($fileName)
{
    return '../authentication/private_folder_authentication/' . $fileName;
}
