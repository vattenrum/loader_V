<?php
date_default_timezone_set('US/Eastern'); // Set time zone for logging.
ini_set('log_errors', 1);
ini_set('display_errors', 0);

include "../include/config.php"; // SQL Server stuff
$server_server = $config['server'];
$server_username = $config['username'];
$server_password = $config['password'];
$server_dbname = $config['dbname'];
$server_status = $config['status'];

//replace sigs when necessary
$global_vars = "A1 ? ? ? ? 5E 8B 40 10";
$client_mode = "A1 ? ? ? ? 8B 80 ? ? ? ? 5D";
$move_helper = "8B 0D ? ? ? ? 8B 45 ? 51 8B D4 89 02 8B 01";
$glow_obj_manager = "0F 11 05 ? ? ? ? 83 C8 01";
$client_state = "A1 ? ? ? ? 8B 80 ? ? ? ? C3";
$local_player = "8B 0D ? ? ? ? 83 FF FF 74 07";
$render_beams = "B9 ? ? ? ? A1 ? ? ? ? FF 10 A1 ? ? ? ? B9";

if ($server_status != 'online') {
    $response = array('status' => 'failed', 'detail' => 'server offline', 'reason' => $config['reason']);
    die(encrypt_str(json_encode($response)));
}

include '../include/functions.php';

try {
    $conn = new PDO('mysql:host=' . $server_server . ';dbname=' . $server_dbname, $server_username, $server_password, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (PDOException $e) {
    $response = array('status' => 'failed', 'detail' => 'connection error');
    die(encrypt_str(json_encode($response)));
}

// Successfully connected to the database.

$decrypted_user = decrypt_str($_POST['username']);
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

if ((time() - $result['last_login_time'] > 300000)) {// 5 * 1000 * 60 = 5 min
    if ((int)$result['user_type'] != 2) {
        $response = array('status' => 'failed', 'detail' => 'auth timeout');
        die(encrypt_str(json_encode($response)));
    }
}

$response = array('status' => 'successful',
    'global_vars' => $global_vars,
    'client_mode' => $client_mode,
    'move_helper' => $move_helper,
    'glow_obj_manager' => $glow_obj_manager,
    'client_state' => $client_state,
    'local_player' => $local_player,
    'render_beams' => $render_beams);
$response["time"] = time();
die(encrypt_str(json_encode($response)));

function secret_directory($fileName)
{
    return 'private_folder_authentication/' . $fileName;
}
