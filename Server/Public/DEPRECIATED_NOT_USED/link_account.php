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
        <h3>Discord Linking</h3>
        <h4>Click to enable discord account linking.</h4>
        <fieldset>
            <button name="submit" type="submit">Enable Linking</button>
        </fieldset>
        <h4>
            <?php
            include "../include/auth_funcs.php";
            include '../include/functions.php';

            if (!isset($_POST["submit"]))
                die();

            is_valid_user($conn, 0);

            $set_discord_id = $conn->prepare("UPDATE users SET discord_id=:discord_id, discord_link_time=:discord_link_time WHERE username=:username");
            $set_discord_id->bindValue(':username', $_SESSION['username']);
            $set_discord_id->bindValue(':discord_id', NULL);
            $set_discord_id->bindValue(':discord_link_time', time());
            $set_discord_id->execute();

            $response = "discord linking is enabled.";
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