<?php

namespace Silnik\Utils\Abstracts;

abstract class Server
{
    /**
     * Verifica se a página está sendo servidor por SSL ou não
     *
     * @return boolean
     */
    public static function isHttps($trust_proxy_headers = false)
    {
        // Verifique o cabeçalho HTTPS padrão
        if (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
            return isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off';
        }
        // Verifique os cabeçalhos de proxy, se permitido
        return $trust_proxy_headers && isset($_SERVER['X-FORWARDED-PROTO']) && $_SERVER['X-FORWARDED-PROTO'] == 'https';
        // Padrão para não SSL
    }

    public function CheckEmail($email)
    {
        if (preg_match("/^[\-\!\#\$\%\&\'\*\+\.\/0-9\=\?A-Z\^\_\`a-z\{\|\}\~]+\@([\-\!\#\$\%\&\'\*\+\/0-9\=\?A-Z\^\_\`a-z\{\|\}\~]+\.)+[a-zA-Z]{2,6}$/", $email)) {
            $explodeMail = explode('@', $email);

            return (checkdnsrr(array_pop($explodeMail), 'MX'));
        } else {
            return false;
        }
    }

    // Returns used memory (either in percent (without percent sign) or free and overall in bytes)
    public static function getServerMemoryUsage()
    {
        $memoryTotal = ((int)ini_get('memory_limit') * 1024 * 1024);
        $memoryUsage = memory_get_peak_usage();
        $memoryFree = $memoryTotal - $memoryUsage;

        if (is_null($memoryTotal) || is_null($memoryFree)) {
            return null;
        } else {
            return [
                'pct' => round((1 - ($memoryFree * 1 / $memoryTotal)), 5),
                'total' => \Silnik\Utils\Measure::sizeFormat($memoryTotal),
                'free' => \Silnik\Utils\Measure::sizeFormat($memoryFree),
            ];
        }
    }
}
