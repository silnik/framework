<?php

    namespace Silnik\Logs;

    class LogLoad
    {
        public $filename = '/loadpage.log';
        public $limitRegister;
        public function __construct($conf = ['path' => '/tmp', 'limit' => '5'])
        {
            $this->filename = $conf['path'] . $this->filename;
            $this->limitRegister = $conf['limit'];
        }

        public function register($page = '')
        {
            if (defined('MICROTIME') == true) {
                $timer = microtime(true) - MICROTIME;
                if ($this->limitRegister != 0) {
                    if (!file_exists($this->filename)) {
                        file_put_contents($this->filename, '');
                    }
                    $text = '';
                    $addLine = true;
                    $f = fopen($this->filename, 'r+');
                    while (!feof($f)) {
                        //pega conteudo da linha
                        $line = fgets($f);
                        if ($line != '') {
                            $lines = explode('|', $line);
                            if (isset($lines[0]) && $lines[0] == $page) {
                                //define que o arquivo será reescrito
                                $addLine = false;
                                $text = substr($text, 0, -1);
                                $lines[1] = explode(';', $lines[1]);
                                array_pop($lines[1]);
                                $limitData = $this->limitRegister - 1;
                                if (count($lines[1]) >= $limitData) {
                                    while (count($lines[1]) > $limitData) {
                                        array_shift($lines[1]);
                                    }
                                }
                                if (strlen($text) != 0) {
                                    $text .= "\n";
                                }
                                $text .= $page . '|' . implode(';', $lines[1]) . ';' . $timer . ';' . "\n";
                            } else {
                                $text .= $line;
                            }
                        }
                    }
                    rewind($f);
                    ftruncate($f, 0);
                    if ($addLine == true) {
                        $text .= $page . '|' . $timer . ';' . "\n";
                    }
                    fwrite($f, $text);
                    fclose($f);
                }
            }
        }
    }
