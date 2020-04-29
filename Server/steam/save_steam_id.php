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

$check_unused_id = $conn->prepare("SELECT * FROM user_to_steam WHERE steam_id_64=:steam_id_64");
$check_unused_id->bindValue(":steam_id_64", $_POST["steam_id"]);
$check_unused_id->execute();

if ($check_unused_id->rowCount() > 0) {
    $used_id_user = $check_unused_id->fetch();
    if ($used_id_user["user"] != $_POST["username"]) {
        $add_id_to_db = $conn->prepare("INSERT INTO user_to_steam (steam_id_64, user, attach_time) VALUES (:steam_id_64, :user, :attach_time)");
        $add_id_to_db->bindValue(":steam_id_64", $_POST["steam_id"]);
        $add_id_to_db->bindValue(":user", $_POST["username"]);
        $add_id_to_db->bindValue(":attach_time", time());
        if ($add_id_to_db->execute()) {
            $response = array("status" => "success", "detail" => "linked id to account");
            die(json_encode($response));
        } else {
            $response = array("status" => "failed", "detail" => "failed to add to db");
            die(json_encode($response));
        }
    } else {
        $response = array("status" => "success", "detail" => "id already linked to account");
        die(json_encode($response));
    }
}

$add_id_to_db = $conn->prepare("INSERT INTO user_to_steam (steam_id_64, user) VALUES (:steam_id_64, :user)");
$add_id_to_db->bindValue(":steam_id_64", $_POST["steam_id"]);
$add_id_to_db->bindValue(":user", $_POST["username"]);
if ($add_id_to_db->execute()) {
    $response = array("status" => "success", "detail" => "linked id to account");
    die(json_encode($response));
} else {
    $response = array("status" => "failed", "detail" => "failed to add to db");
    die(json_encode($response));
}
