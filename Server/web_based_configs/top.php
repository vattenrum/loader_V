<?php
//list most recent configs

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

$get_most_recent_cfgs = $conn->prepare("SELECT * FROM configs LIMIT 10");
$get_most_recent_cfgs->execute();
while ($row = $get_most_recent_cfgs->fetch(PDO::FETCH_ASSOC)) {
    echo(json_encode($row));
    echo("\n");
}

//die(json_encode($recent_cfgs));
