<?php
session_start();

include "../include/config.php"; // SQL Server stuff
$server_server = $config['server'];
$server_username = $config['username'];
$server_password = $config['password'];
$server_dbname = $config['dbname'];

try {
    $conn = new PDO('mysql:host=' . $server_server . ';dbname=' . $server_dbname, $server_username, $server_password, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (Exception $e) {
    die('Error connecting to database.');
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" type="image/x-icon" href="./favicon_green.png">
    <title>VER$ACE</title>
    <link rel="stylesheet" type="text/css" href="../css/general.css">
</head>
<body>
<div class="container">
    <form id="generate" method="post">
        <h3>Loader</h3>
        <h4>Click download to get loader.</h4>
        <fieldset>
            <button name="submit" type="submit">Download Loader</button>
        </fieldset>
        <h4>
            <?php
            if (!isset($_POST["submit"]))
                die();


            include "../include/auth_funcs.php";

            is_valid_user($conn, 0);

            header("Content-Description: File Transfer");
            header("Content-Type: application/octet-stream");
            header('Content-Disposition: attachment; filename="loader.exe"');
            $response = readfile($config['download_link']);
            die($response);

            function secret_directory($fileName)
            {
                return '../generate_license_keys/priv/' . $fileName;
            }

            ?>
        </h4>
    </form>
</div>
</body>
</html>