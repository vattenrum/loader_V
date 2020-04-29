<?php
session_start();
date_default_timezone_set('US/Eastern'); // Set time zone for logging.
ini_set('log_errors', 1);
ini_set('display_errors', 0);

include "../include/config.php"; // SQL Server stuff
include "../include/functions.php";
$conn = create_sql_conn($config);
?>

<!DOCTYPE html>
<html>
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    
    <link rel="shortcut icon" type="image/x-icon" href="./favicon_green.png">
    <title>VER$ACE</title>
    <link rel="stylesheet" type="text/css" href="../css/general.css">
</head>
<body>
<div class="container">
    <form id="generate" method="post">
        <h3>Register</h3>
        <h4>Enter username, password, and invite key and hit submit.</h4>
        <fieldset>
            <input placeholder="Username" name="username" id="username" type="text" tabindex="1" required autofocus>
            <input placeholder="Password" name="password" id="password" type="password" tabindex="1" required autofocus>
            <input placeholder="Invite Key" name="invite_key" id="invite_key" type="text" tabindex="1" required
                   autofocus>
        </fieldset>
        <fieldset>
            <button name="submit" type="submit">Register</button>
        </fieldset>
        <h4>
            <?php
            // Successfully connected to the database.

            if (!isset($_POST["submit"])) {
                die();
            }


            $check_invite = $conn->prepare("SELECT * FROM invite_keys WHERE invite_key=:invite_key");
            $check_invite->bindValue(':invite_key', $_POST['invite_key']);
            $check_invite->execute();
            if ($check_invite->rowCount() < 1) {
                $response = "invite key doesn't exist";
                die($response);
            }

            $l_key_list = $check_invite->fetch();
            if ($l_key_list['used'] == "1") {
                $response = "invite key already used";
                die($response);
            }

            $check_username = $conn->prepare("SELECT * FROM users WHERE username=:username");
            $check_username->bindValue(':username', $_POST['username']);
            $check_username->execute();
            if ($check_username->rowCount() > 0) {
                // Username already taken
                $response = "username already taken";
                die($response);
            }

            $check_inviter = $conn->prepare("SELECT * FROM users WHERE username=:username");
            $check_inviter->bindValue(':username', $l_key_list['inviter']);
            $check_inviter->execute();
            $inviter_status = $check_inviter->fetch();
            if ($inviter_status['banned'] == "1") {
                $response = "inviter banned.";
                die($response);
            }

            $set_user = $conn->prepare("UPDATE invite_keys SET user=:user WHERE invite_key=:invite_key");
            $set_user->bindValue(':user', $_POST['username']);
            $set_user->bindValue(':invite_key', $_POST['invite_key']);
            $set_user->execute();

            $set_used = $conn->prepare("UPDATE invite_keys SET used=1 WHERE invite_key=:invite_key");
            $set_used->bindValue(':invite_key', $_POST['invite_key']);
            $set_used->execute();

            // Prepare to create their account.
            $create_account = $conn->prepare("INSERT INTO users (id, username, password, expire, hwid, dll_hwid, registration_time) VALUES ('', :username, :password, '0', '0', '0', :registration_time);");
            $create_account->bindValue(':username', $_POST['username']);
            $create_account->bindValue(':password', password_hash($_POST['password'], PASSWORD_BCRYPT));
            $create_account->bindValue(":registration_time", time());

            // Execute statement to create account and update key info.
            if ($create_account->execute()) {
                log_event($conn, "user_register", $_POST["username"], "user has created an account: " . $_SERVER['REMOTE_ADDR']);
                prepend($message_info, secret_directory('logs/registrations.data'));

                send_email("versacehack.xyz - account created", $message_info);
                $response = "account created - " . $_POST['username'];
                die($response);
            } else {
                $response = "failed - connection error?";
                die($response);
            }

            unset($conn);

            function secret_directory($fileName)
            {
                return '../authentication/private_folder_authentication/' . $fileName;
            }

            ?>
        </h4>
    </form>
</div>
</body>
</html>