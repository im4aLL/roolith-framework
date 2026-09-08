<?php
namespace App\Utils;


use App\Core\Lang;
use App\Core\Settings;

class Str
{
    /**
     * Get a message
     *
     * @param string $name Message key in dot notation.
     * @return mixed Message value or null when missing.
     */
    public static function getMessage(string $name): mixed
    {
        $lang = Settings::getLang();
        $languageInstance = Lang::getInstance();
        $messages = $languageInstance->getMessages($lang);

        return _::get($messages, $name);
    }
}
