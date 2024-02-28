<?php

namespace Silnik\Logs;

class LogLoad
{
    private $recordsDefault = 20;
    public static $instance = null;
    public function __construct(
        private string $filename = '',
        private string $app = '',
        private string $namespace = '',
        private string $method = '',
        private string $actionUri = '',
    ) {
    }

    /**
     * Undocumented function
     *
     * @param [type] $filename
     * @param [type] $namespace
     * @param [type] $method
     * @param string $methodHttp
     * @param string $actionUri
     * @return self
     */
    public static function setInstance(
        $filename,
        $namespace,
        $method,
        $methodHttp = '',
        $actionUri = ''
    ): self {
        $methodHttp = empty($methodHttp) ? 'GET' : $methodHttp;
        $actionUri = empty($actionUri) ? $_SERVER['REQUEST_URI'] : $actionUri;
        $app = explode('\\', $namespace)[1];

        self::$instance = new self(
            $filename,
            $app,
            $namespace,
            $method,
            '[' . $methodHttp . ']' . $actionUri,
        );

        return self::$instance;
    }

    /**
     *
     * @return self
     */
    public static function getInstance(): self
    {
        return self::$instance;
    }

    /**
     *
     * @param [type] $numRegisters
     * @return void
     */
    public function register($records = null): void
    {
        if (is_null($records)) {
            $records = $this->recordsDefault;
        }
        if (defined('MICROTIME') == true) {
            $milliseconds = round((microtime(true) - MICROTIME), 3);
            if ($records > 0) {
                if (file_exists($this->filename)) {
                    $register = json_decode(file_get_contents($this->filename), true);
                } else {
                    $register = [];
                }

                $memory = \Silnik\Utils\Server::getServerMemoryUsage();
                if ($this->app == 'NotFound') {
                    $register[$this->app][$this->actionUri][] = ['milliseconds' => $milliseconds, 'memory_pct' => $memory['pct'], 'datetime' => date('Y-m-d H:i:s')];
                    $max = 10;
                    $count = count($register[$this->app]);
                    if ($count > $max) {
                        $remove = $count - $max;
                        for ($i = 0; $i < $remove; $i++) {
                            array_shift($register[$this->app]);
                        }
                    }
                } else {
                    $register[$this->app][$this->namespace][$this->actionUri][$this->method][] = ['milliseconds' => $milliseconds, 'memory_pct' => $memory['pct'], 'datetime' => date('Y-m-d H:i:s')];

                    $count = count($register[$this->app][$this->namespace][$this->actionUri][$this->method]);
                    if ($count > $records) {
                        $remove = $count - $records;
                        for ($i = 0; $i < $remove; $i++) {
                            array_shift(
                                $register[$this->app][$this->namespace][$this->actionUri][$this->method]
                            );
                        }
                    }
                }

                file_put_contents(
                    $this->filename,
                    json_encode($register, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
                );
            }
        }
        exit;
    }
}