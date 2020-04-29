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
            <h3>Generate a License</h3>
            <h4>Create keys for users to redeem.</h4>
            <fieldset>
               <select name="licensetype" id="licensetype" required autofocus="">
                  <option value="" disabled selected hidden>License Type</option>
                  <option value="1day">1 Day</option>
                  <option value="1week">1 Week</option>
                  <option value="1month">1 Month</option>
                  <option value="3months">3 Months</option>
                  <option value="6months">6 Months</option>
                  <option value="1year">1 Year</option>
                  <option value="lifetime">Lifetime</option>
               </select>
            </fieldset>
            <fieldset>
               <select name="game_type" id="game_type" required autofocus="">
                  <option value="" disabled selected hidden>Game</option>
                  <option value="csgo">CSGO</option>
                  <option value="gtav">GTA V</option>
               </select>
            </fieldset>
            <fieldset>
               <button name="submit" type="submit">Generate</button>
            </fieldset>
			<h4>
			<?php
            if (!isset($_POST["submit"])) {
                die();
            }
            
            if (!isset($_POST['licensetype'])) {
                die("no license type selected.");
            }
            
        $user = $_SESSION['username'];
        $pass = $_SESSION['password'];
        include secret_directory('config.php'); // SQL Server stuff
        include secret_directory('functions.php');
        include secret_directory('auth_funcs.php');

        $conn = create_sql_conn($config, true);

        is_valid_user($conn, 2);

        $generatedKey = 'VER$ACE-' . generateRandomString();

        $saveKey = $conn->prepare("INSERT INTO regkeys (id, regkey, used, user, days, weeks, months, years, lifetime, game_type) VALUES ('', :regkey, '0', '0', :days, :weeks, :months, :years, :lifetime, :game_type);");
        $saveKey->bindValue(':regkey', $generatedKey);
        switch ($_POST['licensetype']) {
            case '1day':
                $saveKey->bindValue(':days', '1');
                $saveKey->bindValue(':weeks', '0');
                $saveKey->bindValue(':months', '0');
                $saveKey->bindValue(':years', '0');
                $saveKey->bindValue(':lifetime', '0');
                break;
            case '1week':
                $saveKey->bindValue(':days', '0');
                $saveKey->bindValue(':weeks', '1');
                $saveKey->bindValue(':months', '0');
                $saveKey->bindValue(':years', '0');
                $saveKey->bindValue(':lifetime', '0');
                break;
            case '1month':
                $saveKey->bindValue(':days', '0');
                $saveKey->bindValue(':weeks', '0');
                $saveKey->bindValue(':months', '1');
                $saveKey->bindValue(':years', '0');
                $saveKey->bindValue(':lifetime', '0');
                break;
            case '3months':
                $saveKey->bindValue(':days', '0');
                $saveKey->bindValue(':weeks', '0');
                $saveKey->bindValue(':months', '3');
                $saveKey->bindValue(':years', '0');
                $saveKey->bindValue(':lifetime', '0');
                break;
            case '6months':
                $saveKey->bindValue(':days', '0');
                $saveKey->bindValue(':weeks', '0');
                $saveKey->bindValue(':months', '6');
                $saveKey->bindValue(':years', '0');
                $saveKey->bindValue(':lifetime', '0');
                break;
            case '1year':
                $saveKey->bindValue(':days', '0');
                $saveKey->bindValue(':weeks', '0');
                $saveKey->bindValue(':months', '0');
                $saveKey->bindValue(':years', '1');
                $saveKey->bindValue(':lifetime', '0');
                break;
            case 'lifetime':
                $saveKey->bindValue(':days', '0');
                $saveKey->bindValue(':weeks', '0');
                $saveKey->bindValue(':months', '0');
                $saveKey->bindValue(':years', '0');
                $saveKey->bindValue(':lifetime', '1');
                break;
        }
        
        switch ($_POST['game_type']) {
            
            case 'csgo':
                $saveKey->bindValue(':game_type', 'csgo');
                break;
            case 'gtav':
                $saveKey->bindValue(':game_type', 'gtav');
                break;
        }

        if ($saveKey->execute()) {
            die($generatedKey);
        } else {
            die('An exception has occurred while submitting the key to the database.');
        }
?>
			</h4>
         </form>
      </div>
   </body>
</html>