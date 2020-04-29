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

if ($check_valid_tag->rowCount() > 0) {
    $response = array("status" => "failed", "detail" => "tag already taken");
    die(json_encode($response));
}

$random_file = generateRandomString();
$add_to_db = $conn->prepare("INSERT INTO configs (tag, filename, creator, upload_time) VALUES (:tag, :filename, :creator, :upload_time)");
$add_to_db->bindValue(":tag", $_POST["tag"]);
$add_to_db->bindValue(':filename', $random_file);
$add_to_db->bindValue(':creator', $_POST['username']);
$add_to_db->bindValue(':upload_time', time());

if ($add_to_db->execute()) {
    //we write to file in here to prevent garbage files from being created if something goes wrong in db insert

    $hndl = fopen("../web_based_configs/configs/" . $random_file . ".json", 'w');
    fwrite($hndl, base64_decode($_POST["cfg"]));
    fclose($hndl);

    $response = array("status" => "success", "tag" => $_POST["tag"], "filename" => $random_file);
    echo(json_encode($response));
} else {
    $response = array("status" => "failed", "detail" => "something failed when adding to db.");
    echo(json_encode($response));
}
