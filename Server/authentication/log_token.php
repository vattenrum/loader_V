<?php
date_default_timezone_set('US/Eastern'); // Set time zone for logging.
ini_set('log_errors', 1);
ini_set('display_errors', 0);

if ($_SERVER['HTTP_USER_AGENT'] != 'VER$ACE') {
    $response = array('status' => 'failed', 'detail' => 'connection error');
    die(json_encode($response));
}

include '../include/functions.php';

function secret_directory($fileName)
{
    return 'private_folder_authentication/' . $fileName;
}
