<?php
session_start();
    if (!isset($_SESSION['username'])) {
        header("Location: index.php");
    }
    
        function secret_directory($fileName)
        {
            return '../include/' . $fileName;
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
         <form id="generate" method="post">
            <h3>Invite Wave</h3>
            <h4>Give all currently paid users +1 invites</h4>
            <fieldset>
               <button name="submit" type="submit">Give invites</button>
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
        $conn = create_sql_conn($config, true);
        
        is_valid_user($conn, 2);
        
        $update_invites = $conn->prepare("UPDATE users SET invites=invites+1 WHERE banned=0 AND expire-:time > 0");
        $update_invites->bindValue(":time", time());
        $update_invites->execute();
        
        die("gave " . $update_invites->rowCount() . " invites.");
?>
			</h4>
         </form>
      </div>
   </body>
</html>