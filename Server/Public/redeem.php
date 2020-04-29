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
        <h3>Redeem</h3>
        <h4>Enter username and license key and hit submit.</h4>
        <fieldset>
            <input placeholder="License Key" name="regkey" id="regkey" type="text" tabindex="1" required autofocus>
        </fieldset>
        <fieldset>
            <button name="submit" type="submit">Redeem license</button>
        </fieldset>
        <h4>
            <?php
            // Successfully connected to the database.

            if (!isset($_POST["submit"])) {
                die();
            }

            include "../include/auth_funcs.php";

            $check_username_result = is_valid_user($conn, 0);

            $check_key = $conn->prepare('SELECT * FROM regkeys WHERE regkey=:regkey');
            $check_key->bindValue(':regkey', $_POST['regkey']);
            $check_key->execute();
            $check_key_result = $check_key->fetch();

            if ($check_key->rowCount() == 0) {
                // Key doesn't exist
                $response = "invalid key.";
                die($response);
            }

            if ((int)$check_key_result['used'] == 1) {
                // Key has already been used
                $response = "key already used.";
                die($response);
            }


            $current_expire_val = (int)$check_username_result['expire'];
            if ($current_expire_val >= 2000000000) {
                // User already has lifetime.
                $response = "user already lifetime.";
                die($response);
            }

            // Key hasn't been used yet, username is valid. Establish expiration date.
            $expire_val = 0;
            if ((int)$check_key_result['lifetime'] == 1) {
                // Value >= 2 billion is considered lifetime
                $expire_val = 2000000000;
            } else {
                // Add days/months/years to current time (add weeks * 7 to days, since DateInterval doesn't accept weeks.)
                $days_to_add = (int)$check_key_result['days'];
                $weeks_to_add = (int)$check_key_result['weeks'];
                $months_to_add = (int)$check_key_result['months'];
                $years_to_add = (int)$check_key_result['years'];
                $days_to_add += $weeks_to_add * 7;
                $date = new DateTime();
                $date->setTimestamp($current_expire_val);
                $date->add(new DateInterval("P{$years_to_add}Y{$months_to_add}M{$days_to_add}D"));
                $expire_val = $date->getTimestamp();
                if ($current_expire_val < time()) {
                    $expire_val = $expire_val + (time() - $current_expire_val);
                }
            }

            // Update users expiration time.
            $update_account = $conn->prepare("UPDATE users SET expire=:expire WHERE username=:username");
            $update_account->bindValue(':username', $_SESSION['username']);
            $update_account->bindValue(':expire', $expire_val);

            // Mark the key as used, let it know who the user was.
            $used_key = $conn->prepare("UPDATE regkeys SET used='1', user=:user WHERE regkey=:regkey");
            $used_key->bindValue(':regkey', $_POST['regkey']);
            $used_key->bindValue(':user', $_SESSION['username']);

            // Execute statement to update expiration date and key info.
            if ($update_account->execute() && $used_key->execute()) {
                $log_message = $_SESSION['username'] . ' used the registration key ' . $_POST['regkey'] . ' from ' . $_SERVER['REMOTE_ADDR'];

                send_email("versacehack.xyz - license key redeemed", $log_message);

                $response = "new expiry val: " . $expire_val . " for " . $_SESSION['username'];
                die($response);
            } else {
                $response = array('status' => 'failed', 'detail' => 'connection error');
                die(json_encode($response));
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