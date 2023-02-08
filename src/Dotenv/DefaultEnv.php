<?php

namespace Silnik\Dotenv;

class DefaultEnv
{
    public function __construct(
        protected array $data = []
    ) {
        $this->data = [
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
            'PATH_UPLOAD_PRIVARTE' => '/storage/uploads',
            'PATH_SESSIONS' => '/storage/sess',
            'PATH_TMP' => '/storage/tmp',
            'PATH_LOG' => '/storage/log',
            'PATH_MIGRATIONS' => '/storage/migrations',
            'PATH_DATABASE' => '/storage/database',
            'PATH_CACHE' => '/storage/cache',

            'SESSION_LIFETIME' => 120,
            'PRIVATE_KEY' => md5(time()),
            'PREFIXE_KEY' => '',

            'CACHE_TWIG' => false,
        ];
    }
    /**
     * Summary of getData
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}
