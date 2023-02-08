<?php

namespace Silnik\Cache;

class Cache
{
    public function __construct()
    {
        header('Content-type: text/html; charset=utf-8');
        header('Cache-Control: max-age=0');
        header('Expires: Fri, 14 Mar 1980 20:53:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
    }

    /**
     * Summary of clearPath
     * @param string $dir
     * @return void
     */
    public function clearPath(string $dir)
    {
        $this->rrmdir($dir);
    }

    /**
     * Summary of rrmdir
     * @param string $src
     * @return void
     */
    private function rrmdir(string $src)
    {
        if (file_exists($src)) {
            $dir = opendir($src);
            while (false !== ($file = readdir($dir))) {
                if ($file != '.' && $file != '..' && $file != '.ignore') {
                    $full = $src . '/' . $file;
                    if (is_dir($full)) {
                        $this->rrmdir($full);
                        rmdir($full);
                    } else {
                        unlink($full);
                    }
                }
            }
            closedir($dir);
        }
    }
}
