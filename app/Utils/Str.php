<?php
namespace App\Utils;


use App\Core\Lang;
use App\Core\Settings;

class Str
{
    /**
     * Get message
     *
     * @param $name
     * @return mixed|null
     */
    public static function getMessage($name)
    {
        $lang = Settings::getLang();
        $languageInstance = Lang::getInstance();
        $messages = $languageInstance->getMessages($lang);

        return _::get($messages, $name);
    }
}
