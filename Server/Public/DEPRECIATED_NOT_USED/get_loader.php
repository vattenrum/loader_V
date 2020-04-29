<?php

if (!isset($_POST['license_key'])) {
    die(file_get_contents('index.html'));
} else {
    // Logged in successfully.

    include '../include/config.php'; // SQL Server stuff
    include '../include/functions.php';
    $conn = create_sql_conn($config);
    $checkKey = $conn->prepare('SELECT * FROM regkeys WHERE regkey=:regkey');
    $checkKey->bindValue(':regkey', $_POST['license_key']);
    $checkKey->execute();
    $checkKeyResult = $checkKey->fetch();
    if ($checkKey->rowCount() < 1) {
        $response = "invalid key.";
        die($response);
    }

    if ($checkKeyResult['used'] == "1") {
        $checkLogin = $conn->prepare("SELECT * FROM users WHERE username=:username");
        $checkLogin->bindValue(':username', $checkKeyResult['user']);
        $checkLogin->execute();
        $result = $checkLogin->fetch();
        if ($checkLogin->rowCount() <= 0) {
            // Account doesn't exist.
            $response = "linked to an account that doesn't exist";
            die($response);
        }

        if ($result['banned'] == 1) {
            $response = "user is banned.";
            die($response);
        }
    }
    header("Content-Description: File Transfer");
    header("Content-Type: application/octet-stream");
    header('Content-Disposition: attachment; filename="loader.exe"');
    $response = readfile($config['download_link']);
    die($response);
}

function secret_directory($fileName)
{
    return '../generate_license_keys/priv/' . $fileName;
}

?>