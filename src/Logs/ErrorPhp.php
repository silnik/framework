<?php

namespace Silnik\Logs;

class ErrorPhp
{
    public function __construct()
    {
        if (getenv('APP_ENV') != 'production') {
            error_reporting(E_ALL);
            ini_set('log_errors', 1);
            ini_set('display_errors', 1);
            ini_set('ignore_repeated_errors', true);
            ini_set('display_startup_errors', 1);
            ini_set('error_log', PHP_LOG);
            ini_set('error_reporting', E_ALL);
        } else {
            ini_set('log_errors', 0);
            ini_set('display_errors', 0);
            ini_set('display_startup_errors', 0);
            ini_set('error_log', PHP_LOG);
            ini_set('error_reporting', E_ALL);
        }
        if (getenv('APP_ENV') != 'production') {
            $whoops = new \Whoops\Run;
            $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler)->register();
        } else {
            $whoops = new \Whoops\Run;
            $whoops->pushHandler(new \Whoops\Handler\CallbackHandler(function ($error) {
                file_put_contents(
                    PHP_LOG,
                    date('Y-m-d H:i:s') . ': ' . $error->getMessage() . ' File: ' . $error->getFile() . ' Line: ' . $error->getLine() . PHP_EOL,
                    FILE_APPEND | LOCK_EX
                );
            }))->register();
        }
    }

    # logging
    /*
    [2017-03-20 3:35:43] [INFO] [file.php] Here we are
    [2017-03-20 3:35:43] [ERROR] [file.php] Not good
    [2017-03-20 3:35:43] [DEBUG] [file.php] Regex empty

    mylog ('hallo') -> INFO
    mylog ('fail', 'e') -> ERROR
    mylog ('next', 'd') -> DEBUG
    mylog ('next', 'd', 'debug.log') -> DEBUG file debug.log
    */
    public static function dump($text, $level = 'i', $dir = '')
    {
        switch (strtolower($level)) {
            case 'e':
            case 'error':
                $level = 'ERROR';

                break;
            case 'i':
            case 'info':
                $level = 'INFO';

                break;
            case 'd':
            case 'debug':
                $level = 'DEBUG';

                break;
            default:
                $level = 'INFO';
        }
        $d = new DateTime();

        $timezone = date_default_timezone_get();
        $tag = '[' . $d->format(($timezone == 'America/Sao_Paulo' ? 'd-M-Y H:i:s' : 'Y-m-d H:i:s')) . ' ' . $timezone . '] ' . $level;
        error_log($tag . ":\t" . $text . "\n", 3, PHP_LOG);
    }
}
