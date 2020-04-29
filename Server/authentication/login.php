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
    $response = array('status' => 'failed', 'detail' => 'invalid hash'); //need to add this to the loader
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
        log_event($conn, "loader_ban", $decrypted_user, "user banned - mismatched HWID: " . $_SERVER["REMOTE_ADDR"]);
        $response = array('status' => 'failed', 'detail' => 'hwid mismatch');
        die(encrypt_str(json_encode($response)));
    }

    // Update HWID if needed.
    if ($result["hwid"] == "0") {
        $update_account = $conn->prepare("UPDATE users SET hwid=:hwid WHERE username=:username");
        $update_account->bindValue(':username', $decrypted_user);
        $update_account->bindValue(':hwid', $decrypted_hwid);
        $update_account->execute();
    }

    $update_login_time = $conn->prepare("UPDATE users SET last_login_time=:last_login_time WHERE username=:username");
    $update_login_time->bindValue(':username', $decrypted_user);
    $update_login_time->bindValue(':last_login_time', time()); //update login time, for authentication timeout
    $update_login_time->execute();

    $update_last_ip = $conn->prepare("UPDATE users SET last_ip=:last_ip WHERE username=:username");
    $update_last_ip->bindValue(':username', $decrypted_user);
    $update_last_ip->bindValue(':last_ip', $_SERVER['REMOTE_ADDR']); //update login time, for authentication timeout
    $update_last_ip->execute();

    // Use the informtion from cheat files to send data to the client
    $response = array('status' => 'successful', 'username' => $decrypted_user, 'time_left' => $time_left_seconds);
    //include secret_directory('cheat.php'); //loader needs to make call to get_dll.php from now on to get DLL
    //$response['download'] = strrev(base64_encode(strrev($cheat['download'])));
    //$response['key'] = (string)(generateRandomString(12) . $cheat['key'] . generateRandomString(12));
    log_event($conn, "loader_login", $decrypted_user, "user logged in: " . $_SERVER["REMOTE_ADDR"]);
	$response["time"] = time();
    die(encrypt_str(json_encode($response)));
} else {
    // Password is incorrect.
    $log_failed_login = $conn->prepare("INSERT INTO failed_logins (username, failed_password, ip) VALUES (:username, :password, :ip);");
    $log_failed_login->bindValue(':username', $_POST['username']);
    $log_failed_login->bindValue(':password', $_POST['password']);
    $log_failed_login->bindValue(':ip', $_SERVER['REMOTE_ADDR']);
    $log_failed_login->execute();

    $response = array('status' => 'failed', 'detail' => 'wrong password');
    die(encrypt_str(json_encode($response)));
}

function secret_directory($fileName)
{
    return 'private_folder_authentication/' . $fileName;
}
