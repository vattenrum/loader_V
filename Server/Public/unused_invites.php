<?php
session_start();
date_default_timezone_set('US/Eastern'); // Set time zone for logging.
ini_set('log_errors', 1);
ini_set('display_errors', 0);

include "../include/config.php"; // SQL Server stuff
include "../include/functions.php";
include "../include/auth_funcs.php";

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
        <h3>Unused Invites</h3>
        <h4>
            <?php
            is_valid_user($conn, 0);

            $get_unused_invites = $conn->prepare("SELECT invite_key FROM `invite_keys` WHERE inviter=:inviter AND used=0");
            $get_unused_invites->bindValue(":inviter", $_SESSION["username"]);
            $get_unused_invites->execute();

            echo($get_unused_invites->rowCount() . " open invites:<br>");
            while ($row = $get_unused_invites->fetch()) {
                echo($row["invite_key"] . "<br>");
            }
            ?>
        </h4>
    </form>
</div>
</body>
</html>