<?php

namespace Silnik\Uri;

class Uri
{
    private static $instance;
    private $https;
    private $hostname;
    private $fullUri;
    private $slices;
    private $baseAppUrl;
    private $baseHref;

    private function __construct($parseUrl)
    {
        $this->https = ($parseUrl['scheme'] == 'https');
        $this->baseAppUrl = ($parseUrl['path'] != '/' ? '/' . $parseUrl['path'] : '');
        $this->hostname = $parseUrl['host'];
        $this->slices = explode('/', preg_replace('/^[\/]*(.*?)[\/]*$/', '\\1', getenv('REQUEST_URI')));
        $this->baseHref = ($this->https ? 'https://' : 'http://') . $this->hostname . $this->baseAppUrl;
        $this->fullUri = ($this->https ? 'https://' : 'http://') . $this->hostname . getenv('REQUEST_URI');

        $_SERVER['APP_URL'] = $_ENV['APP_URL'] = $this->baseHref;
        putenv('APP_URL=' . $this->baseHref);

        $this->forceLocations();
    }

    public static function getInstance($params = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($params);
        }

        return self::$instance;
    }

    private function forceLocations()
    {
        if (!empty(getenv('REQUEST_SCHEME')) && getenv('REQUEST_SCHEME') == 'http' && $this->https == true) {
            header('Location: ' . ($this->https == true ? 'https://' : 'http://') . $this->hostname . getenv('REQUEST_URI'));
            exit;
        }

        if ($this->hostname == getenv('HTTP_HOST') . '/') {
            header('Location: ' . ($this->https == true ? 'https://' : 'http://') . $this->hostname);
            exit;
        }
    }

    public function nextSlice($ref = '', $removeGets = true)
    {
        if ($removeGets == true) {
            $this->slices = explode('/', explode('?', getenv('REQUEST_URI'))[0]);
        }
        if (in_array($ref, $this->slices)) {
            $pointer = reset($this->slices);
            do {
                $pointer = next($this->slices);
            } while ($pointer != $ref);
            $pointer = next($this->slices);

            return $pointer;
        } else {
            return false;
        }
    }

    public function posURI($pos = 0)
    {
        $URL = $this->slices;

        return (isset($URL[$pos]) && !empty($URL[$pos]) ? $URL[$pos] : '');
    }


    public function getSlices()
    {
        return $this->slices;
    }

    public function getUri()
    {
        return getenv('REQUEST_URI');
    }
    public function getFulluri()
    {
        return $this->fullUri;
    }
    public function getBaseHref()
    {
        return $this->baseHref;
    }
}
