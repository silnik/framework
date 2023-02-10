<?php

namespace Silnik\Http;

class Http
{
    private static $instance;
    private $headers = [];
    private $get = [];
    private $body = [];
    private $params = null;
    private $method = 'GET';

    public function __construct()
    {
        header('Access-Control-Allow-Methods: POST, PUT, GET, PATCH, DELETE');
        header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');

        if (getenv('APP_ENV') != 'production') {
            header('Access-Control-Allow-Origin: *');
        }
        foreach (getallheaders() as $k => $v) {
            $this->headers[$k] = $v;
        }
        $this->method = getenv('REQUEST_METHOD');
        $this->get = $this->contentGET();
        $this->body = $this->contentBodyJson();
    }
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }
    public function header($k)
    {
        if (isset($this->headers[$k])) {
            return $this->headers[$k];
        } else {
            return $this->headers;
        }
    }
    public function method()
    {
        return $this->method;
    }
    public function isGET()
    {
        return ($this->method == 'GET');
    }
    public function isPOST()
    {
        return ($this->method == 'POST');
    }
    public function isPUT()
    {
        return ($this->method == 'PUT');
    }
    public function isPATCH()
    {
        return ($this->method == 'PATCH');
    }
    public function isDELETE()
    {
        return ($this->method == 'DELETE');
    }


    /**
     *
     * @param string|array $params
     * @param $forceType
     * @return mixed
     */
    public function dataParams(string|array $params, $forceType = null, $required = false): mixed
    {
        $ret = null;
        if (is_string($params)) {
            $ret = $this->data($params);
        } elseif (is_array($params)) {
            $ret = $this->data($params);
        }
        if (is_null($ret)) {
            throw new \Exception($params . ' is required', 400);
        } else {
            return match ($forceType) {
                'int' => (int) $ret,
                'string' => (string) $ret,
                'bool' => (bool) $ret,
                'float' => (float) $ret,
                'array' => (array) $ret,
                default => trim($ret)
            };
        }
    }

    public function getParamsREST($uriSplit = '')
    {
        if (!empty($uriSplit) && is_null($this->params)) {
            $p = [];
            if (str_contains(getenv('REQUEST_URI'), $uriSplit)) {
                $spl = explode($uriSplit, getenv('REQUEST_URI'));
                if (str_contains($spl[1], '/')) {
                    $spl = explode('/', $spl[1]);
                    if (is_array($spl) && count($spl) > 0) {
                        $n = count($spl);
                        for ($i = 0; $i < $n; $i++) {
                            if ($i % 2 != 0 && isset($spl[$i + 1])) {
                                $p[$spl[$i]] = $spl[$i + 1];
                            }
                        }
                    }
                }
            }
            $this->params = $p;
        }

        return $this->params;
    }
    private function contentGET()
    {
        $get = [];
        if (isset($_GET) && count($_GET) > 0) {
            foreach ($_GET as $name => $value) {
                $v = null;
                if (is_array($value)) {
                    $v = filter_input_array(INPUT_GET, FILTER_SANITIZE_ENCODED);
                    $get[$name] = $v[$name];
                } else {
                    $v = filter_input(INPUT_GET, $name, FILTER_SANITIZE_SPECIAL_CHARS);
                    $get[$name] = $v;
                }
            }
        }

        return $get;
    }
    public function getArray($k = null, $pos = 0)
    {
        if (!is_null($k) && is_array($this->get($k)) && isset($this->get($k)[$pos])) {
            return $this->get($k)[$pos];
        } else {
            return null;
        }
    }
    public function get($k = null, $v = null)
    {
        if (isset($k) && isset($v)) {
            $this->get[$k] = $v;

            return $this;
        } elseif (isset($k) && !is_null($k)) {
            if (!isset($this->get[$k]) || is_null($this->get[$k])) {
                return null;
            }

            return $this->get[$k];
        } else {
            return $this->get;
        }
    }

    private function contentBodyJSON()
    {
        try {
            $postJson = json_decode(file_get_contents('php://input'), true);
        } catch (\JsonException $exception) {
            throw new \InvalidArgumentException('Json inválido');
        }
        if (is_array($postJson) && count($postJson) > 0) {
            return $postJson;
        }
    }
    public function data($k = null, $v = null)
    {
        if (!is_null($k) && !is_null($v)) {
            $this->body[$k] = $v;

            return $this;
        } elseif (!is_null($k) && is_null($v) && isset($this->body[$k])) {
            return $this->body[$k];
        } else {
            return null;
        }
    }

    public function dataArray($k = null, $pos = null)
    {
        if (is_null($pos)) {
            return $this->data($k);
        } elseif (!is_null($k) && is_array($this->data($k)) && isset($this->data($k)[$pos])) {
            return $this->data($k)[$pos];
        } else {
            return null;
        }
    }
}
