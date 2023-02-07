<?php

namespace Silnik\Controller;

class ApiController
{
    private string $namespace = '';

    public function __construct()
    {
        putenv('TYPE_APP=API');
    }

    /**
     * getNamespace function
     *
     * @param array $urlArray
     * @return void
     */
    public function getNamespace($urlArray)
    {
        array_shift($urlArray);
        $APP = array_shift($urlArray);

        $this->namespace = 'Api\\' . ucfirst($APP);
        $this->method = null;
        $path = PATH_ROOT . '/app/' . $APP . '/api';
        $urlApi = '';

        do {
            $file = (($APP == current($urlArray)) ? current($urlArray) : ucfirst(current($urlArray)));
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
                $urlApi .= '/' . current($urlArray);
                $path .= '/' . (is_dir($path . '/' . $folder) ? $folder : ucfirst($folder));
                $this->namespace .= '\\' . ucfirst($folder);
                next($urlArray);
                $find = false;
            //valida se tem arquivo
            } elseif (file_exists($path . '/' . $file . '.php')) {
                $urlApi .= '/' . current($urlArray);
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
            } elseif (file_exists($path . '/Index.php')) {
                $path .= '/Index.php';
                $this->namespace .= '\\Index';
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
            } else {
                $this->namespace = '';
                $find = true;
            }
        } while ($find == false);

        return $this->namespace;
    }



    /**
     * instance function
     *
     * @return string
     */
    private function instance(): void
    {
        if (!empty($this->namespace) && class_exists($this->namespace)) {
            try {
                $this->controller = new $this->namespace();
                $params = $this->http->getParamsREST($urlApi);
                $hasId = (isset($params['id']) && (int)$params['id'] > 0);
                if ($hasId) {
                    $id = $params['id'];
                }

                if ($this->http->isPUT() || $this->http->isPATCH() || $this->http->isDELETE()) {
                    if ($hasId) {
                        unset($params['id']);
                    }
                }
                if (!$hasId && ($this->http->isPUT() || $this->http->isPATCH() || $this->http->isDELETE())) {
                    throw new \Exception('/id/:id is required on URI.');
                }

                switch ($this->http->getMethod()) {
                    case 'GET'   : $this->controller->read($params);

                        break;
                    case 'POST'  : $this->controller->create($params);

                        break;
                    case 'PUT'   : $this->controller->update($id, $params);

                        break;
                    case 'PATCH' : $this->controller->modify($id, $params);

                        break;
                    case 'DELETE': $this->controller->remove($id, $params);

                        break;
                    default: throw new \Exception('HTML method is invalid.');
                }
            } catch (\Exception $exception) {
                ErrosLogs::dump($exception->getMessage() . ' ' . $exception->getFile() . ' ' . $exception->getLine(), 'error');

                header('Content-Type: application/json');
                http_response_code(503);
                echo json_encode(['load' => false, 'message' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
        ob_start('ob_gzhandler');
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['load' => false], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
