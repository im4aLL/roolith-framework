<?php
namespace App\Core;


use App\Core\Interfaces\FileInterface;
use App\Core\Interfaces\RequestInterface;
use App\Utils\_;
use JetBrains\PhpStorm\NoReturn;

class Request implements RequestInterface
{

    /**
     * @inheritDoc
     */
    public static function input($name, $default = null)
    {
        if (isset($_POST[$name])) {
            return Sanitize::any($_POST[$name]);
        }

        if (isset($_GET[$name])) {
            return Sanitize::param($_GET[$name]);
        }

        $steamInput = self::steamInput($name);
        if ($steamInput) {
            return $steamInput;
        }

        return $default;
    }

    /**
     * Get steam input
     *
     * @param $name
     * @return false|mixed
     */
    protected static function steamInput($name): mixed
    {
        $var = self::steamInputs();

        return $var[$name] ?? false;
    }

    /**
     * Get steam inputs
     *
     * @return array
     */
    protected static function steamInputs(): array
    {
        parse_str(file_get_contents("php://input"), $var);

        return $var;
    }

    /**
     * @inheritDoc
     */
    public static function has($name): bool
    {
        $steamInput = self::steamInput($name);

        return isset($_POST[$name]) || isset($_GET[$name]) || $steamInput;
    }

    /**
     * @inheritDoc
     */
    public static function all(): iterable
    {
        if (self::isMethod('POST')) {
            return Sanitize::items($_POST);
        }

        if (self::isMethod('GET')) {
            return Sanitize::items($_GET);
        }

        $inputs = self::steamInputs();

        return Sanitize::items($inputs);
    }

    /**
     * @inheritDoc
     */
    public static function only($name): array
    {
        $inputs = self::all();

        return _::only($inputs, $name);
    }

    /**
     * @inheritDoc
     */
    public static function except($name): array
    {
        $inputs = self::all();

        return _::except($inputs, $name);
    }

    /**
     * @inheritDoc
     */
    public static function redirect($url): void
    {
        header('Location: '. $url);
        exit();
    }

    /**
     * @inheritDoc
     */
    public static function cookie($name)
    {
        return $_COOKIE[$name] ?? null;
    }

    /**
     * @inheritDoc
     */
    public static function file($name): bool|FileInterface
    {
        if (self::hasFile($name)) {
            $fileInstance = new File();

            return $fileInstance->setFile($_FILES[$name]);
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public static function hasFile($name): bool
    {
        return isset($_FILES[$name]);
    }

    /**
     * @inheritDoc
     */
    public static function ajax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * @inheritDoc
     */
    public static function url(): bool|string
    {
        return strtok(self::fullUrl(), '?');
    }

    /**
     * @inheritDoc
     */
    public static function fullUrl(): string
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }

    /**
     * @inheritDoc
     */
    public static function method(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * @inheritDoc
     */
    public static function isMethod($methodName): bool
    {
        return self::method() === $methodName;
    }
}
