<?php
session_start();
if (!isset($_POST['username'])) {
    echo file_get_contents('user_menu.html');
} else {
    $user = $_POST['username'];
    $pass = $_POST['password'];
    include secret_directory('config.php'); // SQL Server stuff

    $server_server = $config['server'];
    $server_username = $config['username'];
    $server_password = $config['password'];
    $server_dbname = $config['dbname'];

    try {
        $conn = new PDO('mysql:host=' . $server_server . ';dbname=' . $server_dbname, $server_username, $server_password, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    } catch (Exception $e) {
        die('Error connecting to database: ' . $e);
    }

    $check_login = $conn->prepare("SELECT * FROM users WHERE username=:username");
    $check_login->bindValue(":username", $user);
    $check_login->execute();
    $login_result = $check_login->fetch();

    if ($check_login->rowCount() < 1) {
        die("Invalid username.");
    }

    if (!password_verify($pass, $login_result["password"])) {
        $log_failed_login = $conn->prepare("INSERT INTO failed_logins (username, failed_password, ip, time) VALUES (:username, :password, :ip, :time);");
        $log_failed_login->bindValue(':username', $user);
        $log_failed_login->bindValue(':password', $pass);
        $log_failed_login->bindValue(':ip', $_SERVER['REMOTE_ADDR']);
        $log_failed_login->bindValue(':time', time());
        $log_failed_login->execute();
        die("Invalid password.");
    }

    if ((int)$login_result["banned"] == 1) {
        die("You are banned, reason: " . $login_result["ban_reason"]);
    }

    $_SESSION['username'] = $user;
    $_SESSION['password'] = $pass;

    die(file_get_contents('user_menu.html'));
}

function secret_directory($fileName)
{
    return '../include/' . $fileName;
}
