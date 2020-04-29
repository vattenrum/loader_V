<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: index.php");
}

include "../include/config.php";
include "../include/auth_funcs.php";
include "../include/functions.php";

$conn = create_sql_conn($config, true);

$login_result = is_valid_user($conn, 1);
$user_type = (int)$login_result["user_type"];
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
    <div id="generate">
        <h3>VER$ACE admin panel</h3>
        <h4>permission level: <?php echo($user_type); ?> </h4>
        <fieldset>
            <a href="./admin_panel.php" target="_self">
                <button type="submit">User Moderation</button>
            </a>
        </fieldset>
        <fieldset>
            <a href="./view_password_resets.php" target="_self">
                <button type="submit">Password Resets</button>
            </a>
        </fieldset>
        <fieldset>
            <a href="./pw_reset_mod.php" target="_self">
                <button type="submit">Accept / Deny resets</button>
            </a>
        </fieldset>
        <?php
        if ($user_type < 2) {
            die();
        }
        ?>
        <fieldset>
            <a href="./give_invites.php" target="_self">
                <button type="submit">Invite Wave</button>
            </a>
        </fieldset>
        <fieldset>
            <a href="./generate.php" target="_self">
                <button type="submit">License Generation</button>
            </a>
        </fieldset>
        <fieldset>
            <a href="./user_stats.php" target="_self">
                <button type="submit">User Stats</button>
            </a>
        </fieldset>
        <fieldset>
            <a href="./logs.php" target="_self">
                <button type="submit">User Logs</button>
            </a>
        </fieldset>
    </div>
</div>
</body>
</html>