<?php
date_default_timezone_set('US/Eastern'); // Set time zone for logging.
ini_set('log_errors', 1);
ini_set('display_errors', 0);

include "../include/config.php"; // SQL Server stuff
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

$update_order_status = $conn->prepare("UPDATE finished_orders SET user_notified=2 WHERE order_id=:order_id");
$update_order_status->bindValue(":order_id", $_GET["order"]);
$update_order_status->execute();

if ($update_order_status->rowCount() > 0) {
    die("worked");
} else {
    die("no such order");
}
function secret_directory($fileName)
{
    return '../authentication/private_folder_authentication/' . $fileName;
}
