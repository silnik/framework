<?php

namespace Silnik\Sessions;

class Sessions
{
    private $path = '';
    private $expiteFullSessDays = 30;
    private $expiteEmptySessDays = 1;

    public function __construct($PATH_SESS)
    {
        $this->path = $PATH_SESS;
    }
    public function start()
    {
        session_cache_limiter('private');
        session_cache_expire($this->expiteFullSessDays * 24 * 60);
        ini_set('session.gc_maxlifetime', ($this->expiteFullSessDays * 24 * 60 * 60));

        session_save_path($this->path);
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        ob_start();
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
