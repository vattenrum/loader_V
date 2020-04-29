<?php
session_start();
date_default_timezone_set('US/Eastern'); // Set time zone for logging.
ini_set('log_errors', 1);
ini_set('display_errors', 0);

include "../include/config.php"; // SQL Server stuff
include "../include/functions.php";
include "../include/auth_funcs.php";

$conn = create_sql_conn($config, true);
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
        <h3>Password Resets</h3>
        <table>
        <tr>
            <th> request id </th>
            <th> user </th>
            <th> request ip </th>
            <th> last login ip </th>
        </tr>
            <?php
            is_valid_user($conn, 1);

            $get_password_resets = $conn->prepare("SELECT * FROM password_resets");
            $get_password_resets->execute();

            while ($row = $get_password_resets->fetch()) {
                $get_user_info = $conn->prepare("SELECT last_ip FROM users WHERE username=:username");
                $get_user_info->bindValue(":username", $row["username"]);
                $get_user_info->execute();

                $user_info = $get_user_info->fetch();

                echo("<tr>");
                echo("<td>" . $row["id"] . "</td>");
                echo("<td>" . $row["username"] . "</td>");
                echo("<td>" . $row["ip"] . "</td>");
                echo("<td>" . $user_info["last_ip"] . "</td>");
                echo("</tr>");
            }
            ?>
        </table>
    </form>
</div>
</body>
</html>