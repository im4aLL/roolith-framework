<?php
namespace App\Core;


use App\Utils\FS;

class Language
{
    protected $messages;

    public function __construct()
    {
        $this->messages = [];
    }

    /**
     * Load message by lang
     *
     * @param $lang
     * @return $this
     */
    public function loadMessageByLang($lang)
    {
        $filePath = APP_ROOT . '/lang/' . $lang . '/message.php';

        if (FS::exists($filePath)) {
            $this->messages[$lang] = include $filePath;
        }

        return $this;
    }

    /**
     * Get message array
     *
     * @param null $lang
     * @return array
     */
    public function getMessages($lang = null)
    {
        $defaultLang = Settings::LANG_DEFAULT;
        $selectedLang = $lang ? $lang : $defaultLang;

        if (!isset($this->messages[$selectedLang])) {
            $this->loadMessageByLang($selectedLang);
        }

        return $this->messages[$selectedLang];
    }
}
