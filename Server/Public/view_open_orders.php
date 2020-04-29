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
        <h3>Open Orders</h3>
        <h4>
            <?php
            is_valid_user($conn, 0);

            $get_open_orders = $conn->prepare("SELECT order_id FROM `orders` WHERE username=:username");
            $get_open_orders->bindValue(":username", $_SESSION["username"]);
            $get_open_orders->execute();

            echo($get_open_orders->rowCount() . " open orders:<br>");
            while ($row = $get_open_orders->fetch()) {
                echo($row["order_id"] . "<br>");
            }
            ?>
        </h4>
    </form>
</div>
</body>
</html>