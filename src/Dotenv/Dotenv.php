<?php

namespace Silnik\Dotenv;

class Dotenv
{
    public function __construct(
        private array $env = []
    ) {
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
    public function mergeEnv(array $params = []): self
    {
        $this->env = array_unique(array_merge($this->env, $params));

        return $this;
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
