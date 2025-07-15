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

        $streamInput = self::streamInput($name);
        if ($streamInput) {
            return $streamInput;
        }

        return $default;
    }

    public static function unsafeInput($name): mixed
    {
        if (isset($_POST[$name])) {
            return $_POST[$name];
        }

        if (isset($_GET[$name])) {
            return $_GET[$name];
        }

        $streamInput = self::streamInput($name);

        if ($streamInput) {
            return $streamInput;
        }

        return null;
    }

    /**
     * Get steam input
     *
     * @param $name
     * @return false|mixed
     */
    protected static function streamInput($name): mixed
    {
        $var = self::streamInputs();

        return $var[$name] ?? false;
    }

    /**
     * Get steam inputs
     *
     * @return array
     */
    protected static function streamInputs(): array
    {
        parse_str(file_get_contents("php://input"), $var);

        return $var;
    }

    /**
     * @inheritDoc
     */
    public static function has($name): bool
    {
        $steamInput = self::streamInput($name);

        return isset($_POST[$name]) || isset($_GET[$name]) || $steamInput;
    }

    /**
     * @inheritDoc
     */
    public static function all(): iterable
    {
        if (self::isMethod('POST')) {
            $items = Sanitize::items($_POST);
            $files = self::allFiles();

            if (count($files) > 0) {
                $items['_files'] = $files;
            }

            return $items;
        }

        if (self::isMethod('GET')) {
            return Sanitize::items($_GET);
        }

        $inputs = self::streamInputs();

        return Sanitize::items($inputs);
    }

    /**
     * Get all files
     *
     * @return array
     */
    public static function allFiles(): array
    {
        $files = $_FILES;
        $result = [];

        if (count($files) == 0) {
            return $result;
        }

        foreach ($files as $key => $value) {
            if (is_array($value['name'])) {
                $multipleFiles = self::splitMultipleFiles($value);

                $result[$key] = [];
                foreach ($multipleFiles as $index => $file) {
                    $result[$key][] = self::file($key, $file);
                }
            } else {
                $result[$key] = self::file($key);
            }
        }

        return $result;
    }

    /**
     * Split multiple uploaded files into a single chuck array
     *
     * @param array $files
     * @return array
     */
    public static function splitMultipleFiles(array $files): array
    {
        $result = [];

        $fileCount = count($files['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            $result[] = [
                'name'     => $files['name'][$i],
                'full_path'=> $files['full_path'][$i] ?? '',
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];
        }

        return $result;
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
    public static function file($name, $fileData = null): bool|FileInterface
    {
        if (self::hasFile($name)) {
            $fileInstance = new File();

            return $fileInstance->setFile($fileData ?? $_FILES[$name]);
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
