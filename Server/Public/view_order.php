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
        <h3>View Order Info</h3>
        <h4>Enter order id (must be linked to this account).</h4>
        <fieldset>
            <input placeholder="order id" name="order-id" id="order-id" type="text" tabindex="1" required autofocus>
        </fieldset>
        <fieldset>
            <button name="submit" type="submit">view order info</button>
        </fieldset>
        <h4>
            <?php
            // Successfully connected to the database.

            if (!isset($_POST["submit"])) {
                die();
            }

            include "../include/auth_funcs.php";

            $check_username_result = is_valid_user($conn, 0);

            $check_order_id = $conn->prepare('SELECT * FROM orders WHERE order_id=:order_id');
            $check_order_id->bindValue(':order_id', $_POST['order-id']);
            $check_order_id->execute();
            $check_order_result = $check_order_id->fetch();

            if ($check_order_id->rowCount() == 0) {
                // Key doesn't exist
                $response = "invalid order id.";
                die($response);
            }
			
			if($check_order_result["username"] != $check_username_result["username"])
			{
				$response = "order not linked to this account. ";
				die($response);
			}
			
			$response = "btc: " . $check_order_result["value_in_btc"] . "<br> address: " . $check_order_result["address"] . "<br> sub time: " . $check_order_result["sub_time"] . " months";
			die($response);

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