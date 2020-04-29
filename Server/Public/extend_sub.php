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
        <h3>Extend Sub</h3>
        <h4>Bitcoin autobuy for subscriptions.</h4>
        <fieldset>
            <select name="sub_time" id="sub_time" required autofocus="">
                <option value="" disabled selected hidden>License Type</option>
                <option value="1">1 Month</option>
                <option value="3">3 Months</option>
                <option value="6">6 Months</option>
                <option value="12">1 Year</option>
            </select>
        </fieldset>
        <fieldset>
            <input placeholder="discount_code" name="discount" id="discount" type="text">
        </fieldset>
        <fieldset>
            <button name="submit" type="submit">Buy</button>
        </fieldset>
        <h4>
            <?php

            if (!isset($_POST["submit"])) {
                die();
            }

            is_valid_user($conn, 0);

            $discount_name = "";
            $discount_val = 0.00;

            if (isset($_POST["discount"]) && $_POST["discount"] != "") {
                $discount_val = $conn->prepare("SELECT * FROM discounts WHERE name=:discount_name");
                $discount_val->bindValue(":discount_name", $_POST["discount"]);
                $discount_val->execute();
                if ($discount_val->rowCount() == 0) {
                    die("discount code is invalid - order cancelled.");
                }

                $discount_results = $discount_val->fetch();

                $discount_name = $discount_results["name"]; //using results instead of post to prevent SQL bullshit
                $discount_val = $discount_results["discount_amt"];
            }

            $create_order = $conn->prepare("INSERT INTO orders (order_id, username, discord_id, time, value_in_btc, address, sub_time, discount_name, discount_amount) VALUES (:order_id, :username, :discord_id, :time, :value_in_btc, :address, :sub_time, :discount_name, :discount_amount);");
            $t = time();
            $temp_order_id = md5($_SESSION['username'] . "USED_WEBSITE") . '-' . $t;
            $create_order->bindValue(':order_id', $temp_order_id);
            $create_order->bindValue(':username', $_SESSION['username']);
            $create_order->bindValue(':discord_id', "USED_WEBSITE");
            $create_order->bindValue(':time', $t);
            $create_order->bindValue(':sub_time', $_POST["sub_time"]); //how many months of sub
            $create_order->bindValue(':discount_name', $discount_name);
            $create_order->bindValue(':discount_amount', $discount_val);

            $cost = file_get_contents("https://blockchain.info/tobtc?currency=USD&value=" . (17 * intval($_POST["sub_time"])));
            $cost = $cost * ((100 - $discount_val) / 100);
            $create_order->bindValue(':value_in_btc', $cost); //17USD * time_in_months to BTC^

            $btc_addr = get_unique_btc_addr($conn); //need to make this changeable between native segwit and non native
            $create_order->bindValue(':address', $btc_addr);
            $create_order->execute();

            $cost_in_usd = (17 * intval($_POST["sub_time"])) * ((100 - $discount_val) / 100);
            $message_info = $_SESSION["username"] . " started a new order for " . $_POST["sub_time"] . " months at $" . $cost_in_usd;

            send_email("versacehack.xyz - new order started", $message_info);
            $response = "cost in btc: " . $cost . "<br> btc address: " . $btc_addr . "<br> order id: " . $temp_order_id . "<br> sub time (in months): " . $_POST["sub_time"] . " cost in usd: " . $cost_in_usd;
            die(json_encode($response));

            function get_unique_btc_addr($conn)
            {
                $get_addr = $conn->prepare("SELECT addr FROM addresses LIMIT 1");
                $get_addr->execute();
                $result = $get_addr->fetch();

                $remove_addr = $conn->prepare("DELETE FROM addresses WHERE addr=:addr");
                $remove_addr->bindValue(':addr', $result['addr']);
                $remove_addr->execute();

                return $result['addr'];
            }

            ?>
        </h4>
    </form>
</div>
</body>
</html>