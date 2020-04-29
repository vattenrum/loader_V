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
               <input placeholder="entity_username" name="entity_username" id="entity_username" type="text" tabindex="3" required autofocus>
               <input placeholder="ban_reason" name="ban_reason" id="ban_reason" type="text" tabindex="3" required autofocus>
            </fieldset>
            <fieldset>
               <select name="type" id="type" required autofocus="">
                  <option value="" disabled selected hidden>type</option>
                  <option value="unban">unban</option>
                  <option value="ban">ban</option>
                  <option value="hwid_reset">reset hwid</option>
               </select>
            </fieldset>
            <fieldset>
               <button name="submit" type="submit">do action</button>
            </fieldset>
            <h4>
			<?php
    if (!isset($_POST["submit"])) {
        die();
    }

        $user = $_SESSION['username'];
        $pass = $_SESSION['password'];
        include secret_directory('config.php'); // SQL Server stuff
        include secret_directory('functions.php');
        include secret_directory('auth_funcs.php');

        $conn = create_sql_conn($config, true);
        $login_result = is_valid_user($conn, 1);

        $check_valid_user = $conn->prepare("SELECT * FROM users WHERE username=:username");
        $check_valid_user->bindValue(":username", $_POST['entity_username']);
        $check_valid_user->execute();
        $valid_user_res = $check_valid_user->fetch();

        if ($check_valid_user->rowCount() < 1) {
            die($_POST['entity_username'] . " is not a valid user.");
        }

        if ($check_valid_user->rowCount() > 1) {
            die("multiple occurences of " . $_POST['entity_username'] . "(HOW???)");
        }

        if ((int)$valid_user_res["user_type"] >= (int)$login_result["user_type"]) {
            die("User has a higher or equal perm to you.");
        }

        $set_banned = $conn->prepare("UPDATE users SET banned=:banned WHERE username=:username");
        $set_banned->bindValue(':username', $_POST['entity_username']);

        $set_ban_reason = $conn->prepare("UPDATE users SET ban_reason=:ban_reason WHERE username=:username");
        $set_ban_reason->bindValue(':username', $_POST['entity_username']);

        //im too lazy / autistic to figure out how to merge these
        $set_dll_hwid = $conn->prepare("UPDATE users SET dll_hwid=:dll_hwid WHERE username=:username");
        $set_dll_hwid->bindValue(':username', $_POST['entity_username']);
        
        $set_hwid = $conn->prepare("UPDATE users SET hwid=:hwid WHERE username=:username");
        $set_hwid->bindValue(':username', $_POST['entity_username']);
        
        switch ($_POST['type']) {
            case 'unban':
                $set_banned->bindValue(':banned', '0');
                $set_banned->execute();
                break;
            case 'ban':
                $set_banned->bindValue(':banned', '1');
                $set_ban_reason->bindValue(':ban_reason', $_POST['ban_reason']);
                $set_ban_reason->execute();
                $set_banned->execute();
                break;
            case 'hwid_reset':
               $set_hwid->bindValue(':hwid', '0');
               $set_dll_hwid->bindValue(':dll_hwid', '0');
               $set_hwid->execute();
               $set_dll_hwid->execute();
                break;
        }
        die("action performed on user " . $_POST['entity_username']);
?>
			</h4>
         </form>
      </div>
   </body>
</html>