<?php

namespace Silnik\Controller;

use Silnik\Http;
use Utils\Request;

abstract class AbstractRestApi
{
    /**
     * Success
     *   200 Ok
     *   201 Created
     *   204 No Content
     * Client Erros
     *   400 Bad Request
     *   401 Unauthorized
     *   404 Not Found
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
    final public const ERRO_SERVERERROR = 500;


    private $cacheExpiresMins = 0;
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
    public function requireAuth(array $consult = [])
    {
        if (!isset($consult['auth']) || $consult['auth'] != true) {
            $this->response('auth', false)->dumpJson();
        } else {
            $this->response('auth', $consult['auth']);
        }

        return $this;
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

    public function status($code)
    {
        $this->code = $code;

        return $this;
    }

    public function cacheExpiresMins($v = 1)
    {
        $this->cacheExpiresMins = (getenv('APP_ENV') !== 'production' ? $v : 0);

        return $this;
    }

    /**
     *
     * @param [type] $can
     * @return void
     */
    public function permission($can): self
    {
        if (!$can) {
            $this->status(self::ERRO_UNAUTHORIZED);
            $this->defaultMessage(Http::getInstance()->method(), self::ERRO_UNAUTHORIZED)->dumpJson();
        }

        return $this;
    }

    /**
     * @param string $method
     * @param integer $code
     * @return self
     */
    public function defaultMessage(string $method, int $code): self
    {
        $msg = match ($code) {
            200 => match ($method) {
                    'PUT', 'PATCH' => 'Registro alterado com sucesso.',
                    'DELETE' => 'Registro removido com sucesso.',
                    default => '',
                },
            201 => match ($method) {
                    'POST' => 'Registro criado com sucesso.',
                    default => '',
                },
            204 => 'Sem conteúdo para retornar.',
            400 => 'Requisição mal formatada.',
            401 => match ($method) {
                    'POST' => 'Você não tem permissão para criar.',
                    'PUT', 'PATH' => 'Você não tem permissão para alterar.',
                    'DELETE' => 'Você não tenm permissão para remover.',
                    default => 'Você não tem permissão',
                },
            404 => 'Rota não encontrada.',
            500 => 'Erro interno do servidor.',
            default => '',
        };
        if (!empty($msg)) {
            $this->response('message', $msg);
        }
        return $this;
    }

    public function dumpJson()
    {
        if (is_null($this->response('message'))) {
            $this->defaultMessage(Http::getInstance()->method(), $this->code);
        }
        //ob_start('ob_gzhandler');
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: must-revalidate');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + ($this->cacheExpiresMins * 60)) . ' GMT');
        http_response_code($this->code);
        echo json_encode(value: $this->data, flags: JSON_UNESCAPED_UNICODE);
        $this->end(10);
    }


    /**
     * Undocumented function
     *
     * @param \Throwable $th
     * @return void
     */
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
    final public function end($recordsLoadPage = null)
    {
        (\Silnik\Logs\LogLoad::getInstance())->register($recordsLoadPage);
    }

}