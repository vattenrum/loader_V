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
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" type="image/x-icon" href="./favicon_green.png">
    <title>VER$ACE</title>
    <link rel="stylesheet" type="text/css" href="../css/general.css">
</head>
<body>
<div class="container">
    <form id="generate" method="post">
        <h3>Reset Password</h3>
        <h4>Enter username and new password.</h4>
        <fieldset>
            <input placeholder="username" name="username" id="username" type="text">
        </fieldset>
        <fieldset>
            <input placeholder="new_password" name="password" id="password" type="password">
        </fieldset>
        <fieldset>
            <button name="submit" type="submit">Apply for Password Reset</button>
        </fieldset>
        <h4>
            <?php

            if (!isset($_POST["submit"])) {
                die();
            }

            if(!isset($_POST["username"]))
            {
                die("username not set.");
            }

            if(!isset($_POST["password"]))
            {
                die("password not set.");
            }

            $check_valid_user = $conn->prepare("SELECT * FROM users WHERE username=:username");
            $check_valid_user->bindValue(":username", $_POST["username"]);
            $check_valid_user->execute();

            if($check_valid_user->rowCount() <= 0)
            {
                die("invalid username.");
            }

            $apply_for_pw_reset = $conn->prepare("INSERT INTO password_resets (id, time, username, new_password, ip) VALUES (:id, :time, :username, :new_password, :ip)");
            $apply_for_pw_reset->bindValue(":id", substr(md5(time() . $_POST["username"]), 0, 16));
            $apply_for_pw_reset->bindValue(":time", time());
            $apply_for_pw_reset->bindValue(":username", $_POST["username"]);
            $apply_for_pw_reset->bindValue(":new_password", password_hash($_POST["password"], PASSWORD_BCRYPT));
            $apply_for_pw_reset->bindValue(":ip", $_SERVER['REMOTE_ADDR']);
            
            if($apply_for_pw_reset->execute())
            {
                die("Applied for password reset. Wait for staff to accept.");
            }
            else
            {
                die("Failed to apply for password reset, server error.");
            }

            ?>
        </h4>
    </form>
</div>
</body>
</html>