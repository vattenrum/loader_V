<?php
date_default_timezone_set('US/Eastern'); // Set time zone for logging.
ini_set('log_errors', 1);
ini_set('display_errors', 0);

if ($_SERVER['HTTP_USER_AGENT'] != 'VER$ACE') {
    $response = array('status' => 'failed', 'detail' => 'connection error');
    die(encrypt_str(json_encode($response)));
}

include "../include/config.php"; // SQL Server stuff
include '../include/functions.php';
$server_server = $config['server'];
$server_username = $config['username'];
$server_password = $config['password'];
$server_dbname = $config['dbname'];
$server_status = $config['status'];

$decrypted_user = decrypt_str($_POST['username']);
if ($server_status != 'online' && $decrypted_user != "null") { //cant be bothered to check user_type rn
    $response = array('status' => 'failed', 'detail' => 'server offline', 'reason' => $config['reason']);
    die(encrypt_str(json_encode($response)));
}

try {
    $conn = new PDO('mysql:host=' . $server_server . ';dbname=' . $server_dbname, $server_username, $server_password, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (PDOException $e) {
    $response = array('status' => 'failed', 'detail' => 'connection error');
    die(encrypt_str(json_encode($response)));
}

// Successfully connected to the database.
$decrypted_hwid = decrypt_str($_POST['hwid']);

$check_login = $conn->prepare("SELECT * FROM users WHERE username=:username");
$check_login->bindValue(':username', $decrypted_user);
$check_login->execute();
$result = $check_login->fetch();
if ($check_login->rowCount() < 1) {
    // Account doesn't exist.
    $response = array('status' => 'failed', 'detail' => 'no account');
    die(encrypt_str(json_encode($response)));
}

if ($result["banned"] == "1") {
    $response = array('status' => 'failed', 'detail' => 'banned', 'extra' => $result["ban_reason"]);
    die(encrypt_str(json_encode($response)));
}

// Check verification time.
$time_left_seconds = intval($result["expire"]) - time();
if ($time_left_seconds < 1) {
    $response = array('status' => 'failed', 'detail' => 'sub invalid');
    die(encrypt_str(json_encode($response)));
}

if (intval($result["expire"]) >= 2000000000) {
    $time_left_seconds = 2000000000;
}

// Make sure HWID matches.
if (($decrypted_hwid != $result["dll_hwid"]) && ($result["dll_hwid"] != "0")) {
    $set_banned = $conn->prepare("UPDATE users SET banned=:banned, ban_reason=:ban_reason WHERE username=:username");
    $set_banned->bindValue(':username', $decrypted_user);
    $set_banned->bindValue(':banned', "1");
    $set_banned->bindValue(':ban_reason', "hwid changed");
    $set_banned->execute();

    $message_info = $decrypted_user . " was banned for mismatched HWID - dll";
    send_email("versacehack.xyz - user banned (dll)", $message_info);
    log_event($conn, "dll_ban", $decrypted_user, "user banned - mismatched HWID: " . $_SERVER["REMOTE_ADDR"]);

    $response = array('status' => 'failed', 'detail' => 'hwid mismatch');
    die(encrypt_str(json_encode($response)));
}

// Update HWID if needed.
if ($result["dll_hwid"] == "0") {
    $update_account = $conn->prepare("UPDATE users SET dll_hwid=:hwid WHERE username=:username");
    $update_account->bindValue(':username', $decrypted_user);
    $update_account->bindValue(':hwid', $decrypted_hwid);
    $update_account->execute();
}

if ((time() - $result['last_login_time'] > 300000)) {// 5 * 1000 * 60 = 5 min, also excludes
    if ((int)$result['user_type'] != 2) {
        $response = array('status' => 'failed', 'detail' => 'auth timeout');
        die(encrypt_str(json_encode($response)));
    }
}

log_event($conn, "dll_login", $decrypted_user, "user logged in: " . $_SERVER["REMOTE_ADDR"]);
$response = array('status' => 'successful', "time" => time());
die(encrypt_str(json_encode($response)));

function secret_directory($fileName)
{
    return 'private_folder_authentication/' . $fileName;
}
