<?php

namespace Silnik;

use Silnik\Http;

class Sessions
{
    public function __construct(
        private $path = '',
        private $expiteFullSessDays = 5,
        private $expiteEmptySessDays = 1,
        private $limitInSeconds = 3,
        private $maxRequest = 10
    ) {
    }
    public function start()
    {
        session_cache_limiter('private');
        session_cache_expire($this->expiteFullSessDays * 24 * 60);
        ini_set('session.gc_maxlifetime', ($this->expiteFullSessDays * 24 * 60 * 60));
        ini_set('session.cookie_secure', 0);
        ini_set('session.use_strict_mode', 0);
        session_save_path($this->path);
        if (!empty(Http::getInstance()->header('Authorization')) && strlen(Http::getInstance()->header('Authorization')) > 32) {
            session_id(substr(strtolower(preg_replace('/[^a-zA-Z0-9]/', '', Http::getInstance()->header('Authorization'))), -32));
            session_start();
        } else if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $this->limitRequest();
        ob_start();
    }
    public function limitRequest()
    {
        if (!isset($_SESSION['REQUEST'])) {
            $_SESSION['REQUEST'] = [
                'LAST' => time(),
                'CONT' => 1
            ];
        }
        $fast_request_check = ($_SESSION['REQUEST']['LAST'] > time() - $this->limitInSeconds);
        if ($fast_request_check && ($_SESSION['REQUEST']['CONT'] < $this->maxRequest)) {
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

    public function clearEmptySessions()
    {
        if (!empty($this->path) && is_dir($this->path)) {
            if (opendir($this->path)) {
                foreach (glob($this->path . '/sess_*') as $filename) {
                    if (file_exists($filename) && filesize($filename) == 0) {
                        if (filemtime($filename) + ($this->expiteEmptySessDays * 24 * 60) < time()) {
                            unlink($filename);
                        }
                    }
                }
            }
        }
    }
}