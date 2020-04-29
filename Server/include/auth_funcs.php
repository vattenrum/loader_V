<?php
function is_valid_user($conn, $perm_level)
{
    if (!isset($_SESSION)) {
        session_start();
    }

    if (!isset($_SESSION["username"]) || !isset($_SESSION["password"])) {
        die("Invalid username / password.");
    }

    $user = $_SESSION["username"];
    $pass = $_SESSION["password"];
    $check_login = $conn->prepare("SELECT * FROM users WHERE username=:username");
    $check_login->bindValue(":username", $user);
    $check_login->execute();
    $login_result = $check_login->fetch();

    if ($check_login->rowCount() < 1) {
        die("Invalid username.");
    }

    if (!password_verify($pass, $login_result["password"])) {
        $log_failed_login = $conn->prepare("INSERT INTO failed_logins (username, failed_password, ip) VALUES (:username, :password, :ip);");
        $log_failed_login->bindValue(':username', $_SESSION['username']);
        $log_failed_login->bindValue(':password', $_SESSION['password']);
        $log_failed_login->bindValue(':ip', $_SERVER['REMOTE_ADDR']);
        $log_failed_login->execute();
        die("Invalid password.");
    }

    if ((int)$login_result["banned"] == 1) {
        die("You are banned, reason: " . $login_result["ban_reason"]);
    }

    if ((int)$login_result["user_type"] < $perm_level) { //owner
        die("Perms not high enough to use this feature.");
    }

    return $login_result;
}

function is_valid_hash($conn, $hash, $user)
{
	$valid_hash = $conn->prepare("SELECT * FROM loaders WHERE hash=:hash");
	$valid_hash->bindValue(":hash", $hash);
	$valid_hash->execute();
	if($valid_hash->rowCount() <= 0)
	{
		$response = array('status' => 'failed', 'detail' => 'invalid hash');
		return json_encode($response);
	}
	
	$vh_results = $valid_hash->fetch();
	if($vh_results["username"] != $user)
	{
		$response = array('status' => 'failed', 'detail' => 'invalid build');
		return json_encode($response);
	}
	
	    $recent_build = $conn->prepare("SELECT * FROM loaders WHERE username=:username ORDER BY creation_time DESC");
    $recent_build->bindValue(":username", $user);
    $recent_build->execute();
    if($recent_build->rowCount() <= 0)
    {
		$response = array('status' => 'failed', 'detail' => 'no build');
		return json_encode($response);
    }
    
    $rb_results = $recent_build->fetch();
    if($rb_results["hash"] != $hash)
    {
        $response = array('status' => 'update', 'detail' => "https://versacehack.xyz/polymorphic/builds/" . $rb_results["file_name"]);
		return json_encode($response);
    }
	
	$response = array('status' => 'success', 'detail' => "valid normal loader");
    return json_encode($response);
}