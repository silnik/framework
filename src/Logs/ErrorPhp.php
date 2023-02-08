<?php

declare(strict_types=1);

namespace Silnik\Logs;

use Whoops\Run;
use Whoops\Handler\CallbackHandler;
use Whoops\Handler\PrettyPageHandler;

use Datetime;

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
            ini_set('error_log', PATH_LOG . '/php-error.log');
            ini_set('error_reporting', E_ALL);
        } else {
            ini_set('log_errors', 0);
            ini_set('display_errors', 0);
            ini_set('display_startup_errors', 0);
            ini_set('error_log', PATH_LOG . '/php-error.log');
            ini_set('error_reporting', E_ALL);
        }
        if (getenv('APP_ENV') != 'production' && getenv('TYPE_REQUEST') == 'WEB') {
            $whoops = new Run;
            $whoops->pushHandler(new PrettyPageHandler)->register();
        } else {
            $whoops = new Run;
            $whoops->pushHandler(new CallbackHandler(function ($error) {
                file_put_contents(
                    PATH_LOG . '/php-error.log',
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
    public static function dumpError($text, $level = 'i', $dir = '')
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
        error_log($tag . ":\t" . $text . "\n", 3, ini_get('error_log'));
    }
}
