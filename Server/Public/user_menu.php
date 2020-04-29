<?php
session_start();
include "../include/config.php"; // SQL Server stuff
include "../include/functions.php";
include "../include/auth_funcs.php";

$conn = create_sql_conn($config);

$login_result = is_valid_user($conn, 0);
$invites = $login_result["invites"];
$uid = $login_result["id"];
$user = $login_result["username"];

$open_orders = $conn->prepare("SELECT * FROM orders WHERE username=:username");
$open_orders->bindValue(":username", $user);
$open_orders->execute();
$open_orders = $open_orders->rowCount();

$unused_invites = $conn->prepare("SELECT * FROM invite_keys WHERE inviter=:username AND used=0");
$unused_invites->bindValue(":username", $user);
$unused_invites->execute();
$unused_invites = $unused_invites->rowCount();

$epoch_expiry = $login_result["expire"];
$dt = new DateTime("@$epoch_expiry");
$expire_time = $dt->format('Y-m-d H:i:s');

if ($epoch_expiry < time())
    $expire_time = $expire_time . " (expired!)";

if ($epoch_expiry >= 2000000000) {
    $expire_time = "never";
}
if ($epoch_expiry <= 0) {
    $expire_time = "no active sub";
}

function link_discord($conn)
{
    if (!isset($_POST["link_disc"])) {
        return 0;
    } //user did not ask for this

    is_valid_user($conn, 0);

    $set_discord_id = $conn->prepare("UPDATE users SET discord_id=:discord_id, discord_link_time=:discord_link_time WHERE username=:username");
    $set_discord_id->bindValue(':username', $_SESSION['username']);
    $set_discord_id->bindValue(':discord_id', null);
    $set_discord_id->bindValue(':discord_link_time', time());
    $set_discord_id->execute();
    echo("discord linking enabled.");
}


function generate_invite($conn)
{
    if (!isset($_POST["generate_inv"])) {
        return 0;
    }

    $check_user_result = is_valid_user($conn, 0);

    if (intval($check_user_result['invites']) <= 0) {
        echo("no invites available.");
    }

    $update_invites = $conn->prepare("UPDATE users SET invites=invites-1 WHERE username=:username");
    $update_invites->bindValue(':username', $_SESSION["username"]);
    $update_invites->execute();

    //generate random invite key, insert into db
    $generated_key = 'VER$ACE-inv-' . generateRandomString();
    $save_key = $conn->prepare("INSERT INTO invite_keys (id, invite_key, used, inviter, user) VALUES ('', :invite_key, '0', :inviter, '');");
    $save_key->bindValue(':invite_key', $generated_key);
    $save_key->bindValue(':inviter', $_SESSION["username"]); //inviter - creator of invite
    if ($save_key->execute()) {
        echo($generated_key);
    } else {
        echo("issue while generating key.");
    }
}

function get_valid_response($conn)
{
    $generate_invite_result = generate_invite($conn);
    if ($generate_invite_result != 0) {
        return $generate_invite_result;
    }

    $discord_link_result = link_discord($conn);
    if ($discord_link_result != 0) {
        return $discord_link_result;
    }
}

function get_unique_loader($conn)
{
    $check_user_result = is_valid_user($conn, 0);
    $get_loader = $conn->prepare("SELECT * FROM loaders WHERE username=:username ORDER BY creation_time DESC LIMIT 1");
    $get_loader->bindValue(":username", $check_user_result["username"]);
    $get_loader->execute();
    
    if($get_loader->rowCount() <= 0)
        return "https://www.google.com";
        
    $res = $get_loader->fetch()["file_name"];
    if($res == "")
        return "https://versacehack.xyz";
    
    return ("https://versacehack.xyz/polymorphic/builds/" . $res);
}

$download = "none";
if($login_result["expire"] > time())
{
	$download = $config["download_link"];
	$download = get_unique_loader($conn);
}
?>

<!DOCTYPE html>
<html>
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    
    <link rel="shortcut icon" type="image/x-icon" href="./favicon_green.png">
    <title>VER$ACE</title>
    <link rel="stylesheet" type="text/css" href="../css/general.css">
</head>
<script>
    function show_info()
    {
        alert("Password for archive is VER$ACE.");
    }
</script>
<body>
<div class="container">
    <form id="generate" method="post">
        <h3>VER$ACE Panel - <?php echo($user); ?></h3>
        <h4>uid: <?php echo($uid); ?> <br> expiry
            time: <?php echo($expire_time); ?></h4>
        <?php
        if (intval($invites) > 0) { ?>
            <fieldset>
                <button type="submit" name="generate_inv">Generate Invites (<?php echo($invites); ?>)</button>
            </fieldset>
        <?php }
        ?>
        <fieldset>
            <button type="submit" name="link_disc">Link Discord</button>
        </fieldset>
        <fieldset>
            <a href="<?php echo($download); ?>" onclick="show_info()">
                <button type="button">Download Loader</button>
            </a>
        </fieldset>
        <fieldset>
            <a href="./redeem.php" target="_self">
                <button type="button">Redeem License</button>
            </a>
        </fieldset>
        <fieldset>
            <a href="./extend_sub.php" target="_self">
                <button type="button">Extend Sub</button>
            </a>
        </fieldset>
        <?php
        if(intval($unused_invites) > 0) { ?>
        <fieldset>
            <a href="./unused_invites.php" target="_self">
                <button type="button">Unused Invites (<?php echo($unused_invites); ?>)</button>
            </a>
        </fieldset>
        <?php }
        ?>
		<fieldset>
            <a href="./view_order.php" target="_self">
                <button type="button">View Order Info</button>
            </a>
        </fieldset>
        <?php
        if(intval($open_orders) > 0) { ?>
        <fieldset>
            <a href="./view_open_orders.php" target="_self">
                <button type="button">View Open Orders (<?php echo($open_orders); ?>)</button>
            </a>
        </fieldset>
        <?php }
        ?>
        <h4>
            <?php echo(get_valid_response($conn)); ?>
        </h4>
    </form>
</div>
</body>
</html>