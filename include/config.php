<?php
$connectstr_dbhost = '';
$connectstr_dbname = '';
$connectstr_dbusername = '';
$connectstr_dbpassword = '';

foreach ($_SERVER as $key => $value) {
    if (strpos($key, "MYSQLCONNSTR_localdb") !== 0) {
        continue;
    }
    
    $connectstr_dbhost = preg_replace("/^.*Data Source=(.+?);.*$/", "\\1", $value);
    $connectstr_dbname = preg_replace("/^.*Database=(.+?);.*$/", "\\1", $value);
    $connectstr_dbusername = preg_replace("/^.*User Id=(.+?);.*$/", "\\1", $value);
    $connectstr_dbpassword = preg_replace("/^.*Password=(.+?)$/", "\\1", $value);
}

// $link = mysqli_connect($connectstr_dbhost, $connectstr_dbusername, $connectstr_dbpassword,$connectstr_dbname);

// if (!$link) {
//     echo "Error: Unable to connect to MySQL." . PHP_EOL;
//     echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
//     echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
//     exit;
// }

// echo "Success: A proper connection to MySQL was made! The my_db database is great." . PHP_EOL;
// echo "Host information: " . mysqli_get_host_info($link) . PHP_EOL;


// server / database configuration
define('DB_USERNAME', $connectstr_dbusername);
define('DB_PASSWORD', $connectstr_dbpassword);
define('DB_HOST', $connectstr_dbhost);
define('DB_NAME', $connectstr_dbname);
define('HOST', $connectstr_dbhost);

// gcm configuration
define("GCM", "AIzaSyDxglTgcTGx7OVJM8_UHUBp2U55-O5txjk");

// notification types
define('PUSH_TYPE_GROUP', 1);
define('PUSH_TYPE_USER', 2);

// response/error codes
define('USER_ALREADY_EXISTS', 32);
define('USER_INVALID', 31);
define('EMAIL_INVALID', 35);
define('UNKNOWN_ERROR', 404);
define('GCM_UPDATE_SUCCESSFUL', 40);
define('GCM_UPDATE_FAILED', 39);
define('FAILED_MESSAGE_SEND', 30);
define('MESSAGE_SENT', 29);
define('PASSWORD_INCORRECT', 28);
define('ACCOUNT_DISABLED', 27);
define('REQUEST_PASSED', 33);
define('REQUEST_FAILED', 34);

?>
