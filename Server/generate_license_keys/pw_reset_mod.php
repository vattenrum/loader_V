<?php
session_start();
function secret_directory($fileName)
{
    return '../include/' . $fileName;
}

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
}
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
    <form id="generate" method="post" action="">
        <h3>Unban / ban user</h3>
        <fieldset>
            <input placeholder="id" name="id" id="id" type="text" tabindex="3"
                   required autofocus>
        </fieldset>
        <fieldset>
            <select name="type" id="type" required autofocus="">
                <option value="" disabled selected hidden>type</option>
                <option value="accept">accept</option>
                <option value="deny">deny</option>
            </select>
        </fieldset>
        <fieldset>
            <button name="submit" type="submit">accept / deny ban</button>
        </fieldset>
        <h4>
            <?php
            if (!isset($_POST["submit"])) {
                die();
            }

            include secret_directory('config.php'); // SQL Server stuff
            include secret_directory('functions.php');
            include secret_directory('auth_funcs.php');

            $conn = create_sql_conn($config, true);
            $login_result = is_valid_user($conn, 1);

            if(!isset($_POST["id"]))
                die("id not set.");

            if(!isset($_POST["type"]) || $_POST["type"] == "")
                die("accept/deny not set.");

            $check_valid_id = $conn->prepare("SELECT * FROM password_resets WHERE id=:id");
            $check_valid_id->bindValue(":id", $_POST["id"]);
            $check_valid_id->execute();

            $results = $check_valid_id->fetch();

            if($check_valid_id->rowCount() <= 0)
                die("invalid id.");

            $user_info = $conn->prepare("SELECT * FROM users WHERE username=:username");
            $user_info->bindValue(":username", $results["username"]);
            $user_info->execute();
            $user_info = $user_info->fetch();

            if((int)$user_info["user_type"] >= $login_result["user_type"])
                die("can't make decisions on users higher than or equal to your perm level.");

            if($_POST["type"] == "deny")
            {
                $deny_pw_reset = $conn->prepare("DELETE FROM password_resets WHERE id=:id");
                $deny_pw_reset->bindValue(":id", $_POST["id"]);
                if($deny_pw_reset->execute())
                {
                    die("denied pw reset.");
                }
                else
                {
                    die("failed when attempting to deny pw reset.");
                }
            }

            if($_POST["type"] == "accept")
            {
                $update_user = $conn->prepare("UPDATE users SET password=:password WHERE username=:username");
                $update_user->bindValue(":username", $results["username"]);
                $update_user->bindValue(":password", $results["new_password"]);
                $update_user->execute();

                $clear_record = $conn->prepare("DELETE FROM password_resets WHERE id=:id");
                $clear_record->bindValue(":id", $_POST["id"]);
                $clear_record->execute();

                die("accepted pw reset.");
            }
            ?>
        </h4>
    </form>
</div>
</body>
</html>