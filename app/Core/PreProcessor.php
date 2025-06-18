<?php
namespace App\Core;

class PreProcessor
{
    /**
     * Force non www
     *
     * @return void
     */
    public static function forceNonWww()
    {
        if (substr($_SERVER['HTTP_HOST'], 0, 4) === 'www.') {
            header('Location: http'.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 's':'').'://' . substr($_SERVER['HTTP_HOST'], 4).$_SERVER['REQUEST_URI']);
            exit;
        }
    }

    /**
     * Force www
     *
     * @return void
     */
    public static function forceWww()
    {
        if ((strpos($_SERVER['HTTP_HOST'], 'www.') === false)) {
            header('Location: http'.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ? 's':'').'://www.'.$_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
            exit();
        }
    }
}