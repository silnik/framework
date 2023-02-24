<?php

namespace Silnik\Dotenv;

class Dotenv
{
    public function __construct(
        private array $env = []
    ) {
        $this->env = [
            'APP_ENV' => 'development',
            'APP_URL' => 'http://localhost/',
            'API_ENDPOINT' => 'http://localhost/api/',

            'DB_DRIVER' => 'pdo_mysql',
            'DB_PORT' => '3306',
            'DB_HOST' => '',
            'DB_USERNAME' => '',
            'DB_PASSWORD' => '',
            'DB_DATABASE' => '',

            'PATH_UPLOAD_PUBLIC' => '/public/uploads',
            'PATH_UPLOAD_PRIVARTE' => '/.storage/uploads',
            'PATH_SESSIONS' => '/.storage/sess',
            'PATH_TMP' => '/.storage/tmp',
            'PATH_LOG' => '/.storage/log',
            'PATH_MIGRATIONS' => '/.storage/migrations',
            'PATH_DATABASE' => '/.storage/database',
            'PATH_CACHE' => '/.storage/cache',

            'SESSION_LIFETIME' => 120,
            'PRIVATE_KEY' => md5(time()),
            'PREFIXE_KEY' => '',

            'CACHE_TWIG' => false,
        ];
    }

    /**
     * Summary of load
     * @param string $path
     * @return Dotenv
     */
    public function load(string $path): self
    {
        $data = [];
        $lines = file($path . '/.env');

        foreach ($lines as $line) {
            if (mb_strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $data[trim($key)] = str_replace(['"', "'"], '', trim($value));
            }
        }
        $this->mergeEnv($data);

        return $this;
    }

    /**
     * Summary of mergeEnv
     * @param array $params
     * @return Dotenv
     */
    public function mergeEnv(array $params = []): void
    {
        $this->env = (array_merge($this->env, $params));
        $this->build();
    }


    /**
     * Summary of build
     * @return Dotenv
     */
    public function build(): self
    {
        foreach ($this->env as $k => $v) {
            putenv(sprintf('%s=%s', $k, $v));
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
        }

        return $this;
    }
}
