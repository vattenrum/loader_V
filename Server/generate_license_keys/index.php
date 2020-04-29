<?php
        session_start();

        function secret_directory($fileName)
        {
            return '../include/' . $fileName;
        }

        include secret_directory('config.php'); // SQL Server stuff
        include secret_directory('functions.php');
        $conn = create_sql_conn($config, true);
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
		<form method="post" action="">
            <fieldset>
               <input placeholder="Username" name="username" type="text" tabindex="1" required autofocus>
            </fieldset>
            <fieldset>
               <input placeholder="Password" name="password" type="password" tabindex="2" required autofocus>
            </fieldset>
			<fieldset>
               <button name="submit" type="submit" id="submit">Login to panel</button>
            </fieldset>
			<h4>
			<?php
            if (!isset($_POST["submit"])) {
                die();
            }
            $user = $_POST['username'];
            $pass = $_POST['password'];
            $check_login = $conn->prepare("SELECT * FROM users WHERE username=:username");
            $check_login->bindValue(":username", $user);
            $check_login->execute();
            $login_result = $check_login->fetch();
                
            if ($check_login->rowCount() < 1) {
                die("Invalid username.");
            }
        
            if (!password_verify($pass, $login_result["password"])) {
                $log_failed_login = $conn->prepare("INSERT INTO failed_logins (username, failed_password, ip, time) VALUES (:username, :password, :ip, :time);");
                $log_failed_login->bindValue(':username', $user);
                $log_failed_login->bindValue(':password', $pass);
                $log_failed_login->bindValue(':ip', $_SERVER['REMOTE_ADDR']);
                $log_failed_login->bindValue(":time", time());
                $log_failed_login->execute();
                die("Invalid password.");
            }

            if ((int)$login_result["banned"] == 1) {
                die("You are banned, reason: " . $login_result["ban_reason"]);
            }

            if ((int)$login_result["user_type"] < 1) {
                die("Invalid permissions.");
            }
        
            $_SESSION['username'] = $user;
            $_SESSION['password'] = $pass;

            header("Location: index2.php");
            ?>
			</h4>
		</form>
         </div>
      </div>
   </body>
</html>
