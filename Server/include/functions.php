<?php
function prepend($string, $orig_filename)
{
    $string .= ("<br>" . PHP_EOL);
    $context = stream_context_create();
    $orig_file = fopen($orig_filename, 'r', 1, $context);

    $temp_filename = tempnam(sys_get_temp_dir(), 'php_prepend_');
    file_put_contents($temp_filename, $string);
    file_put_contents($temp_filename, $orig_file, FILE_APPEND);

    fclose($orig_file);
    unlink($orig_filename);
    rename($temp_filename, $orig_filename);
    chmod($orig_filename, 0777);
}

function create_sql_conn(array $config, $bypass_offline_check = false)
{
    $server_status = $config['status'];
    $server_username = $config['username'];
    $server_password = $config['password'];
    $server_dbname = $config['dbname'];
    $server_server = $config['server'];

    if ($server_status != 'online' && $bypass_offline_check == false) {
        $response = array('status' => 'failed', 'detail' => 'server offline', 'reason' => $config['reason']);
        die(json_encode($response));
    }

    try {
        $conn = new PDO('mysql:host=' . $server_server . ';dbname=' . $server_dbname, $server_username, $server_password, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    } catch (Exception $e) {
        die('Error connecting to database: ' . $e);
    }
    return $conn;
}

function generateRandomString($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $characters_length = strlen($characters);
    $random_string = '';
    for ($i = 0; $i < $length; $i++) {
        $random_string .= $characters[rand(0, $characters_length - 1)];
    }
    return $random_string;
}

function send_email($title, $log_message)
{
    $receiving_email = "nullflex@gmail.com";
    $headers = 'From: '.'auth@versacehack.xyz'."\r\n".
                    'Reply-To: '. $receiving_email ."\r\n" .
                    'X-Mailer: PHP/' . phpversion();
    @mail('nullflex@gmail.com', $title, $log_message, $headers);
}

function log_event($conn, $event_type, $user, $event_info)
{
    $insert_event = $conn->prepare("INSERT INTO events (event_type, user, event_info) VALUES (:event_type, :user, :event_info);");
    $insert_event->bindValue(":event_type", $event_type);
    $insert_event->bindValue(":user", $user);
    $insert_event->bindValue(":event_info", $event_info);
    $insert_event->execute();
}

$encryption_pass = 'VER$ACE_HACK_ENCRYPTION_KEY';
$method = 'aes-256-cbc';
$key = substr(hash('sha256', $encryption_pass, true), 0, 32);
$iv = chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0); //replace this eventually

function encrypt_str($text)
{
    global $method;
    global $key;
    global $iv;
    return base64_encode(openssl_encrypt($text, $method, $key, OPENSSL_RAW_DATA, $iv));
}

function decrypt_str($text)
{
    global $method;
    global $key;
    global $iv;
    return openssl_decrypt(base64_decode(str_replace(" ", "+", $text)), $method, $key, OPENSSL_RAW_DATA, $iv);
}
