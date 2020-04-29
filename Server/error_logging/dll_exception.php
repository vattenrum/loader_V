<?php
include '../include/functions.php';

$current_time = '[' . date('Y-m-d H:i:s') . '] ';
$user = $_POST["username"];
$last_fn = $_POST["last_func"];
$exc_cause = $_POST["exc_cause"];
$exc_detail = $_POST["exc_detail"];
$eip = $_POST["eip"];

prepend($current_time . $user . " experienced a crash calling " . $last_fn . " cause: " . $exc_cause . " detail: " . $exc_detail . " eip: " . $eip, "exceptions.data");
