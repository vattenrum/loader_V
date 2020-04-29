<?php
if (!isset($_POST['invite_key'])) {
    die(file_get_contents('invited.html'));
} else {
    // Logged in successfully.

    include "../include/config.php"; // SQL Server stuff
    $server_server = $config['server'];
    $server_username = $config['username'];
    $server_password = $config['password'];
    $server_dbname = $config['dbname'];

    try {
        $conn = new PDO('mysql:host=' . $server_server . ';dbname=' . $server_dbname, $server_username, $server_password, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    } catch (Exception $e) {
        die('Error connecting to database: ' . $e);
    }

    $check_key = $conn->prepare('SELECT * FROM invite_keys WHERE invite_key=:invite_key');
    $check_key->bindValue(':invite_key', $_POST['invite_key']);
    $check_key->execute();
    $check_key_result = $check_key->fetch();
    if ($check_key->rowCount() < 1) {
        $response = "invalid invite key.";
        die($response);
    }

    if ($check_key_result['used'] == "1") {
        $check_login = $conn->prepare("SELECT * FROM users WHERE user=:username");
        $check_login->bindValue(':username', $check_key_result['user']);
        $check_login->execute();
        $result = $check_login->fetch();
        if ($check_login->rowCount() <= 0) {
            // Account doesn't exist.
            $response = "Invite owner doesn't exist.";
            die($response);
        }

        if ($result['banned'] == 1) {
            $response = "Invite owner is banned.";
            die($response);
        }
    }

    include '../authentication/private_folder_authentication/config.php';
    header("Content-Description: File Transfer");
    header("Content-Type: application/octet-stream");
    header('Content-Disposition: attachment; filename="loader.exe"');
    $response = readfile($config['download_link']);
    die($response);
}

function secret_directory($fileName)
{
    return '../generate_license_keys/priv/' . $fileName;
}

?>