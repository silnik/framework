<?php

namespace Silnik\Controller;

use Silnik\Http\Http;
use Utils\Request;

abstract class AbstractRestApi
{
    use ResponseTrait;
    /**
       * Success
       *   200 Ok
       *   201 Created
       *   204 No Content
       * Client Erros
       *   400 Bad Request
       *   401 Unauthorized
       *   404 Not Found
       *   406 Not Acceptable
       * Server Error
       *   500 Internal Server Error
       *
    */

    final public const SUCCESS_OK = 200;
    final public const SUCCESS_CREATED = 201;
    final public const SUCCESS_NOCONTENT = 204;
    final public const ERRO_BADREQUEST = 400;
    final public const ERRO_UNAUTHORIZED = 401;
    final public const ERRO_NOTFOUND = 404;
    final public const ERRO_NOTACCEPTABLE = 406;
    final public const ERRO_SERVERERROR = 500;


    private $cacheExpiresMins = 1;
    private $code = self::SUCCESS_OK;
    private $data = [];

    public function __construct()
    {
        $this->response('load', true);
    }

    /**
     * Summary of auth
     * @param array $consult
     * @return AbstractRestApi
     */
    public function auth(array $consult = [])
    {
        if (!isset($consult['auth']) || $consult['auth'] != true) {
            $this->status(static::ERRO_UNAUTHORIZED);
            $this->response('auth', false)->dumpJson();
        } else {
            $this->response('auth', $consult['auth'])
                ->response('activity', $consult['status']);
        }

        return $this;
    }

    public function isRequired(string $param, string $type = 'mixed')
    {
        try {
            $v = Request::data($param);
            if (!is_null($v)) {
                if ($type == 'mixed') {
                    return $v;
                } else {
                    return match ($type) {
                        'int' => (int) $v,
                        'string' => (string) $v,
                        'bool' => (bool) $v,
                        'float' => (float) $v,
                        'array' => (array) $v,
                    };
                }
            } else {
                throw new \Exception($type . ' ' . $param . ' is required', self::ERRO_BADREQUEST);
            }
        } catch (\Throwable $th) {
            $this->dumpJsonError($th);
        }
    }

    public function response($key, $val = null)
    {
        if (isset($this->data[$key]) && is_null($val)) {
            return $this->data[$key];
        } elseif (!is_null($val)) {
            $this->data[$key] = $val;

            return $this;
        } else {
            return null;
        }
    }

    public function status($v)
    {
        $this->code = $v;

        return $this;
    }

    public function cacheExpiresMins($v = 1)
    {
        $this->cacheExpiresMins = $v;

        return $this;
    }

    public function defaultMessage(int $code, string $method): void
    {
        if (isset($this->code[$code][$method])) {
            $this->response('message', $this->code[$code][$method]);
        }
    }

    public function dumpJsonError(\Throwable $th)
    {
        if ($th->getCode() > 0) {
            $this->status($th->getCode())->response('message', $th->getMessage())->dumpJson();
        } else {
            \Silnik\Logs\ErrorPhp::registerError(
                message: $th->getMessage(),
                level: 'ERROR',
                debug: debug_backtrace()
            );
            $this->status(500)->response('message', 'Internal Server Error')->dumpJson();
        }
    }
    public function dumpJson()
    {
        unset($this->data['load']);
        // if (is_null($this->response('message'))) {
        //     $this->defaultMessage($this->code, Http::method());
        // }
        ob_start('ob_gzhandler');
        header('Content-Type: application/json');
        header('Cache-Control: must-revalidate');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + ($this->cacheExpiresMins * 60)) . ' GMT');
        http_response_code($this->code);
        echo json_encode($this->data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
