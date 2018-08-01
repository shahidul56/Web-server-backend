<?php

// server / database configuration
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'root');
define('DB_HOST', 'localhost');
define('DB_NAME', 'weeki');
define('HOST', 'localhost');

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
