<?php
namespace App\Core;


use Carbon\Carbon;

class Storage
{
    protected static string $tempKey = '_temp_';

    /**
     * Set cookie
     *
     * @param $name string
     * @param $value mixed
     * @param $expiration Carbon
     * @return bool
     */
    public static function setCookie(string $name, mixed $value, Carbon $expiration): bool
    {
        return setcookie($name, $value, $expiration->getTimestamp());
    }

    /**
     * Delete cookie
     *
     * @param $name string
     * @return bool
     */
    public static function deleteCookie(string $name): bool
    {
        return setcookie($name, '', time() - 3600);
    }

    /**
     * Get cookie by name
     *
     * @param string $name
     * @return mixed
     */
    public static function getCookie(string $name): mixed
    {
        return $_COOKIE[$name] ?? null;
    }

    /**
     * Set session
     *
     * @param $name
     * @param $value
     * @return bool
     */
    public static function setSession($name, $value): bool
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
    public static function getSession($name): mixed
    {
        return $_SESSION[$name] ?? false;
    }

    /**
     * Has session
     *
     * @param string $name
     * @return boolean
     */
    public static function hasSession(string $name): bool
    {
        return isset($_SESSION[$name]);
    }

    /**
     * Delete session
     *
     * @param $name
     * @return bool
     */
    public static function deleteSession($name): bool
    {
        if (self::hasSession($name)) {
            unset($_SESSION[$name]);

            return true;
        }

        return false;
    }

    /**
     * Set session for once
     *
     * @param string $name
     * @param mixed $value
     * @return boolean
     */
    public static function temp(string $name, mixed $value): bool
    {
        if (!self::hasSession(self::$tempKey)) {
            self::setSession(self::$tempKey, []);
        }

        $_SESSION[self::$tempKey][$name] = $value;

        return true;
    }

    /**
     * Remove temp data
     *
     * @return boolean
     */
    public static function removeTemp(): bool
    {
        return self::deleteSession(self::$tempKey);
    }

    /**
     * Get temp data by name
     *
     * @param string $name
     * @return mixed
     */
    public static function getTemp(string $name): mixed
    {
        if (!self::hasSession(self::$tempKey)) {
            return false;
        }

        if (isset($_SESSION[self::$tempKey][$name])) {
            return $_SESSION[self::$tempKey][$name];
        }

        return false;
    }
}
