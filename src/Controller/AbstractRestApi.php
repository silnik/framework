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
     *   403 Forbidden
     *   404 Not Found
     *   405 Method Not Allowed
     * Server Error
     *   500 Internal Server Error
     *
     */

    final public const SUCCESS_OK = 200;
    final public const SUCCESS_CREATED = 201;
    final public const SUCCESS_NOCONTENT = 204;
    final public const ERRO_BADREQUEST = 400;
    final public const ERRO_UNAUTHORIZED = 401;
    final public const ERRO_FORBIDDEN = 403;
    final public const ERRO_NOTFOUND = 404;
    final public const ERRO_NOTALLOWED = 404;
    final public const ERRO_SERVERERROR = 500;


    private $cacheExpiresMins = 0;
    private $code = self::SUCCESS_OK;
    private $data = [];
    private $customHeader = [];

    public function __construct()
    {
    }
    /**
     * Summary of auth
     * @param array $consult
     * @return AbstractRestApi
     */
    public function requireAuth(array $consult = [])
    {
        if ((!isset($consult['auth']) || empty($consult['auth'])) && !Http::getInstance()->isOPTIONS()) {
            $this->permission(false);
            exit;
        }

        return $this;
    }

    /**
     * setHeaderMessage
     *
     * @param string $val
     * @return AbstractRestApi
     */
    public function setHeaderMessage($val = '')
    {
        $this->customHeaderMessage('Custom-Message', $val);
        return $this;
    }

    /**
     * customHeaderMessage function
     *
     * @param string $key
     * @param string $val
     * @return void
     */
    private function customHeaderMessage($key, $val = '')
    {
        if (isset($this->customHeader[$key]) && is_null($val)) {
            return $this->customHeader[$key];
        } elseif (!is_null($val)) {
            $this->customHeader[$key] = $val;
            return $this;
        } else {
            return null;
        }
    }
    /**
     * Undocumented function
     *
     * @param string $key
     * @param string|null $val
     */
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

    /**
     * status http code
     *
     * @param int $code
     * @return void
     */
    public function status($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * cacheExpiresMins function
     *
     * @param integer $v
     * @return void
     */
    public function cacheExpiresMins($v = 1)
    {
        $this->cacheExpiresMins = (getenv('APP_ENV') !== 'production' ? $v : 0);

        return $this;
    }

    /**
     *
     * @param bool $can
     * @return void
     */
    public function authorized($can): self
    {
        if (!$can) {
            $this->status(self::ERRO_UNAUTHORIZED);
            $this->defaultHeaderMessage(Http::getInstance()->method(), self::ERRO_UNAUTHORIZED)->dumpJson();
        }

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
            $this->status(self::ERRO_FORBIDDEN);
            $this->defaultHeaderMessage(Http::getInstance()->method(), self::ERRO_FORBIDDEN)->dumpJson();
        }

        return $this;
    }

    /**
     * @param string $method
     * @param integer $code
     * @return self
     */
    public function defaultHeaderMessage(string $method, int $code): self
    {
        $msg = match ($code) {
            200 => match ($method) {
                    'PUT', 'PATCH' => 'Registro alterado com sucesso.',
                    'DELETE' => 'Registro removido com sucesso.',
                    default => 'A requisição foi bem sucedida.',
                },
            201 => match ($method) {
                    'POST' => 'Registro criado com sucesso.',
                    default => '',
                },
            204 => 'Sem conteúdo para retornar.',
            400 => 'Requisição mal formatada.',
            401 => 'Você precisa fazer login.',
            403 => match ($method) {
                    'POST' => 'Você não tem permissão para criar.',
                    'PUT', 'PATH' => 'Você não tem permissão para alterar.',
                    'DELETE' => 'Você não tem permissão para remover.',
                    default => 'Você não tem permissão',
                },
            404 => 'Rota não encontrada.',
            405 => 'Solicitação não aceita pelo servidor.',
            500 => 'Erro interno do servidor.',
            default => '',
        };
        $this->setHeaderMessage($msg);
        return $this;
    }

    public function dumpJson()
    {
        if (!isset($this->customHeader['Custom-Message']) || empty($this->customHeader['Custom-Message'])) {
            $this->defaultHeaderMessage(Http::getInstance()->method(), $this->code);
        }
        //ob_start('ob_gzhandler');

        $exposeHeaders = '';
        foreach ($this->customHeader as $key => $description) {
            if (!empty($description)) {
                $exposeHeaders .= ',' . $key;
            }
        }
        if (!empty($exposeHeaders)) {
            header('Access-Control-Expose-Headers: ' . substr($exposeHeaders, 1));
        }
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: must-revalidate');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + ($this->cacheExpiresMins * 60)) . ' GMT');
        foreach ($this->customHeader as $key => $description) {
            if (!empty($description)) {
                header($key . ': ' . mb_convert_encoding($description, 'ISO-8859-1', 'UTF-8'));
            }
        }

        http_response_code($this->code);
        if (!empty($this->data)) {
            echo json_encode(value: $this->data, flags: JSON_UNESCAPED_UNICODE);
        }
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
            if (empty($th->getMessage())) {
                $this->defaultHeaderMessage(Http::getInstance()->method(), $th->getCode());
            } else {
                $this->setHeaderMessage($th->getMessage());
            }
            $this->status($th->getCode())->dumpJson();
        } else {
            \Silnik\Logs\ErrorPhp::registerError(
                message: $th->getMessage(),
                level: 'ERROR',
                debug: debug_backtrace()
            );
            $this->status(self::ERRO_SERVERERROR)->setHeaderMessage('Erro interno do servidor.')->dumpJson();
        }
    }
    final public function end($recordsLoadPage = null)
    {
        (\Silnik\Logs\LogLoad::getInstance())->register($recordsLoadPage);
    }

}