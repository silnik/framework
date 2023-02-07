<?php

namespace Silnik\Controller;

class WebController
{
    public function __construct()
    {
        putenv('TYPE_APP=WEB');
    }

    /**
     * getNamespace function
     *
     * @param array $urlArray
     * @return void
     */
    public function getNamespace($urlArray)
    {
        $path = PATH_ROOT . '/app/' . strtolower(getenv('APP_NAME')) . '/web';
        $this->namespace = 'Web\\' . ucfirst(strtolower(getenv('APP_NAME')));
        $this->namespace404 = 'Web\\' . ucfirst(strtolower(getenv('APP_NAME'))) . '\\NotFound';
        $this->method = null;

        do {
            $file = ((strtolower(getenv('APP_NAME')) == current($urlArray)) ? current($urlArray) : ucfirst(current($urlArray)));
            if (strpos($file, '-') !== false) {
                $f = explode('-', $file);
                $newName = '';
                foreach ($f as $key => $value) {
                    $newName .= ucfirst($value);
                }
                $file = $newName;
            }

            $folder = current($urlArray);
            //valida se tem diretório
            if ((is_dir($path . '/' . $folder) || is_dir($path . '/' . ucfirst($folder))) && !empty($folder)) {
                $path .= '/' . (is_dir($path . '/' . $folder) ? $folder : ucfirst($folder));
                $this->namespace .= '\\' . ucfirst($folder);
                next($urlArray);
                $find = false;
            //valida se tem arquivo
            } elseif (file_exists($path . '/' . $file . '.php')) {
                $path .= '/' . $file . '.php';
                $this->namespace .= '\\' . $file;
                next($urlArray);

                if (isset($urlArray[key($urlArray)])) {
                    $m = $urlArray[key($urlArray)];
                    $this->method = $m;
                    if (strpos($m, '-') !== false) {
                        $f = explode('-', $m);
                        $newName = '';
                        foreach ($f as $key => $value) {
                            $newName .= $value;
                        }
                        $this->method = $newName;
                    }
                }
                $find = true;
            } elseif (file_exists($path . '/Index.php') && empty($file)) {
                $path .= '/Index.php';
                $this->namespace .= '\\Index';
                $find = true;
            } else {
                $path = PATH_ROOT . '/app/Controllers/' . strtolower(getenv('APP_NAME')) . '/NotFound.php';
                $this->namespace = $this->namespace404;
                $find = true;
            }
        } while ($find == false);

        return $this->namespace;
    }

    public function instance()
    {
        if (class_exists($this->namespace)) {
            $this->controller = new $this->namespace();
            if (!empty($this->method) && method_exists($this->controller, $this->method) && is_callable([$this->controller, $this->method])) {
                $m = $this->method;
                $this->controller->$m();
            } elseif (method_exists($this->controller, 'show') && is_callable([$this->controller, 'show'])) {
                $this->controller->show();
            } else {
                new \Core\PrintCodeError(410);
            }
        } else {
            new \Core\PrintCodeError(404);
        }
    }
}
