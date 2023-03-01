<?php

namespace Silnik\Dotenv;

class Loader
{
    public function __construct(
        private array $env = []
    ) {
        $this->env = [
            'APP_ENV' => 'production',
            'APP_URL' => 'http://localhost/',

            'ACCESS_ORIGIN' => '*',
            'ACCESS_HEADERS' => 'Origin, Content-Type, Accept, Authorization',
            'ACCESS_METHODS' => 'POST, PUT, GET, PATCH, DELETE',

            'DB_DRIVER' => 'pdo_mysql',
            'DB_PORT' => '3306',
            'DB_HOST' => '',
            'DB_USERNAME' => '',
            'DB_PASSWORD' => '',
            'DB_DATABASE' => '',
            'DB_CHARSET' => 'utf8',

            'PATH_UPLOAD_PUBLIC' => '/public/uploads',
            'PATH_UPLOAD_PRIVARTE' => '/.storage/uploads',
            'PATH_SESSIONS' => '/.storage/sess',
            'PATH_TMP' => '/.storage/tmp',
            'PATH_LOG' => '/.storage/log',
            'PATH_MIGRATIONS' => '/src/Migrations',
            'PATH_DATABASE' => '',
            'PATH_CACHE' => '/.storage/cache',

            'SESSION_LIFETIME' => 120,
            'PRIVATE_KEY' => md5(string: time()),
            'CACHE_TWIG' => false,
        ];

        if (file_exists(filename: PATH_ROOT . $this->env['PATH_LOG'] . '/deploy.log')) {
            $dataDeploy = json_decode(
                json: file_get_contents(
                    filename: PATH_ROOT . $this->env['PATH_LOG'] . '/deploy.log'
                ),
                associative: true
            );
            if (isset($dataDeploy['name'])) {
                $this->env['APP_NAME'] = $dataDeploy['name'];
            }
            if (isset($dataDeploy['version'])) {
                $this->env['APP_VERSION'] = $dataDeploy['version'];
            }
        }
    }

    /**
     * Summary of load
     * @param string $path
     * @return Loader
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
     * @return Loader
     */
    public function mergeEnv(array $params = []): void
    {
        $this->env = (array_merge($this->env, $params));
    }

    /**
     * Summary of build
     * @return Loader
     */
    public function build(): self
    {
        if (
            !file_exists(PATH_ROOT . '/.storage/cache/.cert.enc') &&
            file_exists(PATH_ROOT . '/.env')
        ) {
            $this->load(PATH_ROOT);
        }

        foreach ($this->env as $k => $v) {
            putenv(sprintf('%s=%s', $k, $v));
        }

        if (file_exists(PATH_ROOT . '/.storage/cache/.cert.enc')) {
            (new SecureEnvPHP())->parse(PATH_ROOT . '/.storage/cache/.cert.enc', PATH_ROOT . '/.storage/cache/.cert.key');
        }

        return $this;
    }
}
