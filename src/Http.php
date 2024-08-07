<?php

namespace Silnik;

class Http
{
    private static $instance;
    private $headers = [];
    private $get = [];
    private $body = [];
    private $params = null;
    private $ebableOptions = [];
    private $method = 'GET';

    public function __construct()
    {
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
        if (isset($k) && !empty($k)) {
            return isset($this->headers[$k]) ? $this->headers[$k] : '';
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
        return ($this->method === 'GET');
    }
    public function isHEAD()
    {
        return ($this->method === 'HEAD');
    }
    public function isPOST()
    {
        return ($this->method === 'POST');
    }
    public function isPUT()
    {
        return ($this->method === 'PUT');
    }
    public function isPATCH()
    {
        return ($this->method === 'PATCH');
    }
    public function isDELETE()
    {
        return ($this->method === 'DELETE');
    }
    public function isOPTIONS()
    {
        return ($this->method === 'OPTIONS');
    }

    public function isBodyEmpty()
    {
        return empty($this->body) &&
            (
                isset($_POST) && empty($_POST)
            ) &&
            (
                isset($_FILES) && empty($_FILES)
            );
    }

    public function setOption($method, $use)
    {
        if ($method) {
            $this->ebableOptions[$method] = $use;
        }
        return $this;
    }

    public function getOptions()
    {
        return $this->ebableOptions;
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
            if (strpos($params, '.') !== false) {
                $e = explode('.', $params);
                $ret = $this->dataArray($e[0], $e[1], (count($e) > 2 ? $e[2] : null));

                if (is_null($ret) && $required === true) {
                    throw new \Exception($e[0] . ' is required', 400);
                }
            } else {
                $ret = $this->data($params);
            }
        } elseif (is_array($params)) {
            $ret = $this->data($params);
        }
        if (is_null($ret) && $required == true) {
            throw new \Exception($params . ' is required', 400);
        } else {
            if (is_null($ret)) {
                return null;
            }

            if (
                ($required === true && ($forceType === 'date' || $forceType === 'datetime')) ||
                (!empty($ret) && ($forceType === 'date' || $forceType === 'datetime'))
            ) {
                if (!\Silnik\Utils\DateTimeMaker::validate(substr($ret['date'], 0, ($forceType === 'datetime' ? 19 : 10)), ($forceType === 'datetime' ? 'Y-m-d H:i:s' : 'Y-m-d'))) {
                    throw new \Exception($params . ' is invalid format ' . ($forceType === 'datetime' ? 'Y-m-d H:i:s' : 'Y-m-d'), 400);
                }
            }

            return match ($forceType) {
                'int' => (int) $ret,
                'string' => (string) $ret,
                'date' => (string) substr($ret['date'], 0, 10),
                'datetime' => (string) substr($ret['date'], 0, 19),
                'bool' => (bool) $ret,
                'float' => (float) $ret,
                'money' => number_format(str_replace(',', '', $ret), 2, '.', ''),
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
            throw new \InvalidArgumentException('Invalid Json');
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

    public function dataArray($k = null, $pos = null, $pos2 = null)
    {
        if (is_null($pos)) {
            return $this->data($k);
        } elseif (!is_null($k) && is_array($this->data($k)) && isset($this->data($k)[$pos]) && !is_null($pos2) && isset($this->data($k)[$pos][$pos2])) {
            return $this->data($k)[$pos][$pos2];
        } elseif (!is_null($k) && is_array($this->data($k)) && isset($this->data($k)[$pos])) {
            return $this->data($k)[$pos];
        } else {
            return null;
        }
    }
}