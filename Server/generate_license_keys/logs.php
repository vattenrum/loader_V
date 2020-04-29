<?php
include '../include/config.php';
include '../include/auth_funcs.php';
include '../include/functions.php';
$conn = create_sql_conn($config, true);

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
}

is_valid_user($conn, 2);

?>

<html>
<h1>
    user logs
</h1>    

<?php
$get_events = $conn->prepare("SELECT * FROM events ORDER BY date DESC");
$get_events->execute();
while($row = $get_events->fetch())
{
    echo("<p>" . $row["date"] . " " . $row["event_type"] . " " . $row["user"] . " " . $row["event_info"]);
}
?>

</html>