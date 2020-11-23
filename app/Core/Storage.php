<?php
namespace App\Core;


use Carbon\Carbon;

class Storage
{
    /**
     * Set cookie
     *
     * @param $name string
     * @param $value mixed
     * @param $expiration Carbon
     * @return bool
     */
    public static function setCookie($name, $value, $expiration)
    {
        return setcookie($name, $value, $expiration->getTimestamp());
    }

    /**
     * Delete cookie
     *
     * @param $name string
     * @return bool
     */
    public static function deleteCookie($name)
    {
        return setcookie($name, '', time() - 3600);
    }

    /**
     * Set session
     *
     * @param $name
     * @param $value
     * @return bool
     */
    public static function setSession($name, $value)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION[$name] = $value;

        return true;
    }

    /**
     * Get session value by name
     *
     * @param $name
     * @return false|mixed
     */
    public static function getSession($name)
    {
        return isset($_SESSION[$name]) ? $_SESSION[$name] : false;
    }

    /**
     * Delete session
     *
     * @param $name
     * @return bool
     */
    public static function deleteSession($name)
    {
        if (isset($_SESSION) && isset($_SESSION[$name])) {
            unset($_SESSION[$name]);

            return true;
        }

        return false;
    }
}
