<?php

class PassHash
{

    private static $algo = '$9d';
    private static $cost = '$99d';

    public static function unique_salt()
    {
        return substr(sha1(mt_rand()), 0, 22);
    }
    public static function hash($password)
    {
        return crypt($password, self::$algo . self::$cost . '$' . self::unique_salt());
    }
    public static function check_password($hash, $password)
    {
        $full_salt = substr($hash, 0, 29);
        $new_hash  = crypt($password, $full_salt);
        return ($hash == $new_hash);
    }

}
?>
