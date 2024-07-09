<?php

namespace Silnik;

use Silnik\Http;

class Sessions
{
    private static $expiteFullSessDays = 5;
    private static $expiteEmptySessDays = 1;
    private static $limitInSeconds = 10;
    private static $maxRequest = 50;
    public static function start()
    {
        session_cache_expire(self::$expiteFullSessDays * 24 * 60);
        session_cache_limiter('private');
        session_save_path(PATH_SESSIONS);
        if (!empty(Http::getInstance()->header('Authorization')) && strlen(Http::getInstance()->header('Authorization')) > 32) {
            session_id(substr(strtolower(preg_replace('/[^a-zA-Z0-9]/', '', Http::getInstance()->header('Authorization'))), -32));
            session_start();
            $_SESSION['ADDRESS_IP'] = $_SERVER['SERVER_ADDR'];
        }
        self::limitRequest();
        self::clearEmptySessions();
        ob_start();
    }
    public static function limitRequest()
    {
        if (!isset($_SESSION['REQUEST'])) {
            $_SESSION['REQUEST'] = [
                'LAST' => time(),
                'CONT' => 1
            ];
        }
        $fast_request_check = ($_SESSION['REQUEST']['LAST'] > time() - self::$limitInSeconds);
        if ($fast_request_check && ($_SESSION['REQUEST']['CONT'] < self::$maxRequest)) {
            $_SESSION['REQUEST']['CONT']++;
        } elseif ($fast_request_check) {
            sleep(1);
        } else {
            $_SESSION['REQUEST'] = [
                'LAST' => time(),
                'CONT' => 1
            ];
        }
    }

    public static function clearEmptySessions()
    {
        if (!empty(PATH_SESSIONS) && is_dir(PATH_SESSIONS)) {
            if (opendir(PATH_SESSIONS)) {
                foreach (glob(PATH_SESSIONS . '/sess_*') as $filename) {
                    if (is_file($filename) && (filemtime($filename) + (self::$expiteEmptySessDays * 24 * 60)) < time()) {
                        unlink($filename);
                    }
                }
            }
        }
    }
}