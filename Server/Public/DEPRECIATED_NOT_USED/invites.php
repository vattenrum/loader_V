<?php
session_start();
// Logged in successfully.

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

include '../include/functions.php';
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
    <form id="generate">
        <h3>Invite Creator</h3>
        <h4>Hit submit to get an invite.</h4>
        <fieldset>
            <button name="submit" type="submit">Generate invite</button>
        </fieldset>
        <h4>
            <?php

            include "../include/auth_funcs.php";
            if (!isset($_POST["submit"]))
                die();

            $check_user_result = is_valid_user($conn, 0);

            if (intval($check_user_result['invites']) > 0) {
                //lower the invite owner's total invites by 1
                $update_invites = $conn->prepare("UPDATE users SET invites=invites-1 WHERE username=:username");
                $update_invites->bindValue(':username', $check_user_result['username']);
                $update_invites->execute();

                //generate random invite key, insert into db
                $generated_key = 'VER$ACE-inv-' . generateRandomString();
                $save_key = $conn->prepare("INSERT INTO invite_keys (id, invite_key, used, inviter, user) VALUES ('', :invite_key, '0', :inviter, '');");
                $save_key->bindValue(':invite_key', $generated_key);
                $save_key->bindValue(':inviter', $check_user_result['username']); //inviter - creator of invite
                if ($save_key->execute()) {
                    die($generated_key);
                } else {
                    die("issue while generating key.");
                }
            } else {
                $response = "no invites available.";
                die($response);
            }

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