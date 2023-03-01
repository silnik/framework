<?php

namespace Silnik;

class Sessions
{
    public function __construct(
        private $path = '',
        private $expiteFullSessDays = 30,
        private $expiteEmptySessDays = 1,
        private $limitInSeconds = 5,
        private $maxRequest = 10
    ) {
    }
    public function start()
    {
        session_cache_limiter('private');
        session_cache_expire($this->expiteFullSessDays * 24 * 60);
        ini_set('session.gc_maxlifetime', ($this->expiteFullSessDays * 24 * 60 * 60));
        ini_set('session.cookie_secure', 1);

        session_save_path($this->path);
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
            $this->limitRequest();
        }
        ob_start();
    }
    public function limitRequest()
    {
        // Create our requests array in session scope if it does not yet exist
        if (!isset($_SESSION['requests'])) {
            $_SESSION['requests'] = [];
        }

        // Create a shortcut variable for this array (just for shorter & faster code)
        $requests = $_SESSION['requests'];

        $countRecent = 0;
        $repeat = false;
        foreach ($requests as $request) {
            // See if the current request was made before
            if ($request['session_id'] == session_id()) {
                $repeat = true;
            }
            // Count (only) new requests made in last minute
            if ($request['time'] >= time() - $this->limitInSeconds) {
                $countRecent++;
            }
        }

        // Only if this is a new request...
        if (!$repeat) {
            // Check if limit is crossed.
            // NB: Refused requests are not added to the log.
            if ($countRecent >= $this->maxRequest) {
                http_response_code(429);
                echo json_encode('Too many requests in a short time');
                exit;
            }
            // Add current request to the log.
            $countRecent++;
            $requests[] = ['time' => time(), 'session_id' => session_id()];
        }

        // Debugging code, can be removed later:
        //echo  count($requests) . " unique ID requests, of which $countRecent in last minute.<br>";

        // if execution gets here, then proceed with file content lookup as you have it.
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
