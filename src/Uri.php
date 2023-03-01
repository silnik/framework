<?php

namespace Silnik;

class Uri
{
    private static $instance;
    private $https;
    private $hostname;
    private $fullUri;
    private $slices;
    private $baseAppUrl;
    private $baseHref;

    private function __construct()
    {
        $uriApp = parse_url(url: getenv('APP_URL'));

        $this->https = ($uriApp['scheme'] == 'https');
        $this->hostname = $uriApp['host'];
        $this->baseAppUrl = ($uriApp['path'] != '/' ? $uriApp['path'] : '');

        $this->slices = explode(separator: '/', string: preg_replace(pattern: '/^[\/]*(.*?)[\/]*$/', replacement: '\\1', subject: str_replace(search: $this->baseAppUrl, replace: '', subject: getenv('REQUEST_URI'))));
        $this->baseHref = ($this->https ? 'https://' : 'http://') . $this->hostname . $this->baseAppUrl . '/';
        $this->fullUri = ($this->https ? 'https://' : 'http://') . $this->hostname . getenv('REQUEST_URI');

        putenv(assignment: 'APP_URL=' . $this->baseHref);

        $this->forceLocations();
    }

    public static function getInstance()
    {
        if (is_null(value: self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function forceLocations()
    {
        if (!empty(getenv('REQUEST_SCHEME')) && getenv('REQUEST_SCHEME') == 'http' && $this->https == true) {
            header(header: 'Location: ' . ($this->https == true ? 'https://' : 'http://') . $this->hostname . getenv('REQUEST_URI'));
            exit;
        }

        if ($this->hostname == getenv('HTTP_HOST') . '/') {
            header(header: 'Location: ' . ($this->https == true ? 'https://' : 'http://') . $this->hostname);
            exit;
        }
    }

    public function nextSlice($ref = '', $uri = null, $removeGets = true): bool|string
    {
        if ($removeGets == true) {
            $this->slices = explode(separator: '/', string: explode(separator: '?', string: (is_null(value: $uri) ? getenv('REQUEST_URI') : $uri))[0]);
        }
        if (in_array(needle: $ref, haystack: $this->slices)) {
            $pointer = reset(array: $this->slices);
            do {
                $pointer = next(array: $this->slices);
            } while ($pointer != $ref);
            $pointer = next(array: $this->slices);

            return $pointer;
        } else {
            return false;
        }
    }

    public function prevSlice($ref = '', $uri = null, $removeGets = true): mixed
    {
        if ($removeGets == true) {
            $slices = explode(separator: '/', string: $uri);
        }
        if (in_array(needle: $ref, haystack: $slices)) {
            $pointer = reset(array: $slices);
            do {
                $pointer = next(array: $slices);
            } while ($pointer != $ref);
            $pointer = prev(array: $slices);

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
