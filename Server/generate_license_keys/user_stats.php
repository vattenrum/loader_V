<?php
include secret_directory('config.php');
include secret_directory('auth_funcs.php');
include secret_directory('functions.php');
$conn = create_sql_conn($config, true);

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
}

is_valid_user($conn, 2);

function secret_directory($fileName)
{
    return '../include/' . $fileName;
}

function get_user_count($conn)
{
    $list_users = $conn->prepare("SELECT * FROM users");
    $list_users->execute();
    echo($list_users->rowCount());
}

function get_banned_user_count($conn)
{
    $list_users = $conn->prepare("SELECT * FROM users WHERE banned=1");
    $list_users->execute();
    echo($list_users->rowCount());
}

function get_lifetime_user_count($conn)
{
    $list_users = $conn->prepare("SELECT * FROM users WHERE expire >= 2000000000 AND banned=0");
    $list_users->execute();
    echo($list_users->rowCount());
}

function get_active_users_count($conn)
{
    $list_users = $conn->prepare("SELECT * FROM users WHERE (expire - :cur_time) > 0 AND banned=0");
    $list_users->bindValue(':cur_time', time());
    $list_users->execute();
    echo($list_users->rowCount());
}

function get_btc_addr_count($conn)
{
    $list_addr = $conn->prepare("SELECT * FROM addresses");
    $list_addr->execute();
    echo($list_addr->rowCount());
}

function get_invite_keys($conn)
{
    $list_keys = $conn->prepare("SELECT * FROM invite_keys");
    $list_keys->execute();
    echo($list_keys->rowCount());
}

function get_unused_invite_keys($conn)
{
    $list_keys = $conn->prepare("SELECT * FROM invite_keys WHERE used=0");
    $list_keys->execute();
    echo($list_keys->rowCount());
}

function get_moderators($conn)
{
    $list_mods = $conn->prepare("SELECT * FROM users WHERE user_type=1");
    $list_mods->execute();
    echo($list_mods->rowCount());
}

function get_owners($conn)
{
    $list_owners = $conn->prepare("SELECT * FROM users WHERE user_type>=2");
    $list_owners->execute();
    echo($list_owners->rowCount());
}

?>

<html>
<h1>
    admin stats and info
</h1>    

<h2>
    user info
</h2>
<p>total users: <?php get_user_count($conn) ?></p>
<p>banned users: <?php get_banned_user_count($conn) ?></p>
<p>lifetime users: <?php get_lifetime_user_count($conn) ?></p>
<p>users with active subs (including lifetime): <?php get_active_users_count($conn) ?></p>
<p>unused btc addresses: <?php get_btc_addr_count($conn) ?> </p>
<p>total invite keys (used and unused): <?php get_invite_keys($conn) ?> </p>
<p>total unused invite keys: <?php get_unused_invite_keys($conn) ?> </p>
<p>moderators: <?php get_moderators($conn) ?></p>
<p>owners: <?php get_owners($conn) ?></p>

</html>