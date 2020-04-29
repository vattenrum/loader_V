<?php
    date_default_timezone_set('US/Eastern'); // Set time zone for logging.
    ini_set('log_errors', 1);
    ini_set('display_errors', 0);

    if ($_SERVER['HTTP_USER_AGENT'] != 'VER$ACE') {
        $response = array('status' => 'failed', 'detail' => 'connection error');
        die(json_encode($response));
    }

    include "../include/config.php"; // SQL Server stuff
    include "../include/auth_funcs.php";
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
        $conn = new PDO('mysql:host=' . $server_server . ';dbname=' . $server_dbname, $server_username, $server_password, [PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    } catch (PDOException $e) {
        $response = array('status' => 'failed', 'detail' => 'connection error');
        die(json_encode($response));
    }
    
    if(!isset($_POST["username"]) || !isset($_POST["sha256"])){
        $response = array('status' => 'failed', 'detail' => 'not set.');
        die(json_encode($response));
    }
    
    $client_hash = $_POST["sha256"];
    $client_username = $_POST["username"];
    
    $resp = is_valid_hash($conn, $client_hash, $client_username);
    die($resp);
    
    function secret_directory($fileName)
    {
        return 'private_folder_authentication/' . $fileName;
    }
