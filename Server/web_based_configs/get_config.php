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

$check_valid_tag = $conn->prepare("SELECT * FROM configs WHERE tag=:tag");
$check_valid_tag->bindValue(":tag", $_POST["tag"]);
$check_valid_tag->execute();
$tag_results = $check_valid_tag->fetch();

if ($check_valid_tag->rowCount() < 1) {
    $response = array("status" => "failed", "detail" => "no such tag exists.");
    die(json_encode($response));
}

$response = file_get_contents("../web_based_configs/configs/" . $tag_results["filename"] . ".json"); //get the actual file
die($response);
