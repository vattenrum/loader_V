<?php
include "../include/config.php";
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

$is_user = $conn->prepare("SELECT * FROM user_to_steam WHERE steam_id_64=:steam_id_64 LIMIT 1");
$is_user->bindValue(":steam_id_64", $_POST["steam_id"]);
$is_user->execute();

if ($is_user->rowCount() <= 0) {
    $response = array("status" => "failed", "detail" => "NOT_USER");
    die(json_encode($response));
}

$is_user_result = $is_user->fetch();

$response = array("status" => "success", "detail" => $is_user_result["user"]);
die(json_encode($response));
