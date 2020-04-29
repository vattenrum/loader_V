<?php
date_default_timezone_set('US/Eastern'); // Set time zone for logging.
ini_set('log_errors', 1);
ini_set('display_errors', 0);

include '../include/functions.php';

if ($_SERVER['HTTP_USER_AGENT'] != 'VER$ACE') {
    $response = array('status' => 'failed', 'detail' => 'connection error');
    die(encrypt_str(json_encode($response)));
}

include "../include/config.php"; // SQL Server stuff
include "../include/auth_funcs.php";
$server_server = $config['server'];
$server_username = $config['username'];
$server_password = $config['password'];
$server_dbname = $config['dbname'];
$server_status = $config['status'];

if ($server_status != 'online') {
    $response = array('status' => 'failed', 'detail' => 'server offline', 'reason' => $config['reason']);
    die(encrypt_str(json_encode($response)));
}

try {
    $conn = new PDO('mysql:host=' . $server_server . ';dbname=' . $server_dbname, $server_username, $server_password, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (PDOException $e) {
    $response = array('status' => 'failed', 'detail' => 'connection error');
    die(encrypt_str(json_encode($response)));
}

if(!isset($_POST["username"]) || !isset($_POST["password"]) || !isset($_POST["hwid"]) || !isset($_POST["sha256"]))
{
    $response = array('status' => 'failed', 'detail' => 'not set');
    die(encrypt_str(json_encode($response)));
}


// Successfully connected to the database.
$decrypted_user = decrypt_str($_POST['username']);
//$_POST['username'];//
$decrypted_pass = decrypt_str($_POST['password']);
//$_POST['password'];//
$decrypted_hwid = decrypt_str($_POST['hwid']);
//$_POST['hwid'];//
$client_hash = $_POST["sha256"];

$check_login = $conn->prepare("SELECT * FROM users WHERE username=:username");
$check_login->bindValue(':username', $decrypted_user);
$check_login->execute();
$result = $check_login->fetch();
if ($check_login->rowCount() < 1) {
    // Account doesn't exist.
    $response = array('status' => 'failed', 'detail' => 'no account');
    die(encrypt_str(json_encode($response)));
}

$vh = is_valid_hash($conn, $client_hash, $decrypted_user);
$decoded_vh = json_decode($vh, true);
if($decoded_vh["status"] != "success")
{
    die(encrypt_str(json_encode($response)));
}

if (password_verify($decrypted_pass, $result["password"])) {
    // Password is correct.

    // Check verification time.
    $time_left_seconds = intval($result["expire"]) - time();
    if ($time_left_seconds < 1) {
        $response = array('status' => 'failed', 'detail' => 'sub invalid');
        die(encrypt_str(json_encode($response)));
    }
    if (intval($result["expire"]) >= 2000000000) {
        $time_left_seconds = 2000000000;
    }

    if ($result["banned"] == "1") {
        $response = array('status' => 'failed', 'detail' => 'banned', 'extra' => $result["ban_reason"]);
        die(encrypt_str(json_encode($response)));
    }

    // Make sure HWID matches.
    if (($decrypted_hwid != $result["hwid"]) && ($result["hwid"] != "0")) {
        $set_banned = $conn->prepare("UPDATE users SET banned=:banned, ban_reason=:ban_reason WHERE username=:username");
        $set_banned->bindValue(':username', $decrypted_user);
        $set_banned->bindValue(':banned', "1");
        $set_banned->bindValue(':ban_reason', "hwid changed");
        $set_banned->execute();

        $message_info = $decrypted_user . " was banned for mismatched HWID - loader";
        send_email("versacehack.xyz - user banned (loader)", $message_info);

        $response = array('status' => 'failed', 'detail' => 'hwid mismatch');
        die(encrypt_str(json_encode($response)));
    }

    // Use the informtion from cheat files to send data to the client
    $response = array('status' => 'successful');
    include secret_directory('cheat.php');
    $response['download'] = strrev(base64_encode(strrev($cheat['download'])));
    $response['key'] = (string)(generateRandomString(12) . $cheat['key'] . generateRandomString(12));
	$response['time'] = time();
    die(encrypt_str(json_encode($response)));
} else {
    // Password is incorrect.
    $response = array('status' => 'failed', 'detail' => 'wrong password');
    die(encrypt_str(json_encode($response)));
}

function secret_directory($fileName)
{
    return 'private_folder_authentication/' . $fileName;
}
