<?php

declare(strict_types=1);

namespace Silnik\Router;

use Silnik\Uri\Uri;

class Router
{
    private array $routes = [];

    public function __construct()
    {
    }

    public function registerRoutesFromControllerAttributes(array $controllers)
    {
        $uri = Uri::getInstance();
        foreach ($controllers as $controller) {
            $reflectionController = new \ReflectionClass($controller);
            $parent = $reflectionController->getParentClass();
            if ($parent != false) {
                $typeResponse = (strtolower(substr($parent->getName(), -3)) == 'api' ? 'JSON' : 'HTML');
            } else {
                $typeResponse = 'HTML';
            }

            foreach ($reflectionController->getMethods() as $method) {
                $call = new \ReflectionMethod($controller, $method->getName());
                if ($call->isPublic() == true) {
                    $attributes = $method->getAttributes(Route::class, \ReflectionAttribute::IS_INSTANCEOF);
                    if (!empty($attributes)) {
                        $comment = '';
                        if ($call->getDocComment() != false) {
                            $comment = preg_match_all("#([a-zA-Z]+\s*[a-zA-Z0-9, ()_].*)#", $call->getDocComment(), $matches, PREG_PATTERN_ORDER);
                            $comment = $matches[0][0];
                        }
                        foreach ($attributes as $attribute) {
                            $route = $attribute->getArguments();
                            $url = $route[0];
                            $params = [];
                            if (mb_strpos($url, '{') !== false) {
                                $url = explode('{', $url)[0];

                                preg_match_all("'{(.*?)}'si", $route[0], $match);
                                foreach ($match[1] as $val) {
                                    $p = $uri->prevSlice(
                                        ref: '{' . $val . '}',
                                        uri: $route[0]
                                    );
                                    if ($p != false) {
                                        $params[$p] = '{' . $val . '}';
                                    }
                                }
                            }
                            if (substr($url, -1) == '/') {
                                $url = substr($url, 0, -1);
                            }
                            $this->register($route['methods'], $url, ['uri' => $route[0], 'namespace' => $controller, 'method' => $method->getName(), 'params' => $params, 'typeResponse' => $typeResponse, 'message' => $comment ? $typeResponse . ' ' . $comment : '']);
                        }
                    }
                }
            }
        }
    }

    public function register(array $requestMethod, string $route, callable|array $action): self
    {
        foreach ($requestMethod as $method) {
            $this->routes[$method][$route] = $action;
        }

        return $this;
    }

    public function routes(): array
    {
        return $this->routes;
    }

    public function resolve(string $requestUri, string $requestMethod)
    {
        $uri = Uri::getInstance();

        if (isset($this->routes[$requestMethod])) {
            foreach ($this->routes[$requestMethod] as $key => $value) {
                if (mb_strpos($uri->getBaseHref() . $requestUri, $uri->getBaseHref() . $key) !== false) {
                    $action = $this->routes[$requestMethod][$key] ?? null;
                }
            }

            if (isset($action)) {
                $method = $action['method'];
                $params = $action['params'];

                putenv('TYPE_RESPONSE=' . $action['typeResponse']);
                $_SERVER['TYPE_RESPONSE'] = $action['typeResponse'];
                $_ENV['TYPE_RESPONSE'] = $action['typeResponse'];

                \Silnik\Logs\LogLoad::setInstance(
                    filename: PATH_LOG . '/loadpage.json',
                    namespace: $action['namespace'],
                    method: $action['method'],
                    actionUri: $action['uri'],
                    methodHttp: $requestMethod
                );
                $controller = new $action['namespace'];
                if (!empty($method) && method_exists($controller, $method) && is_callable([$controller, $method])) {
                    if (is_array($params) && count($params) > 0) {
                        $idSender = null;
                        $paramsSender = [];
                        foreach ($params as $k => $v) {
                            if ($v == '{id}') {
                                $idSender = (int)$uri->nextSlice($k);
                            } else {
                                $paramsSender[$k] = $uri->nextSlice($k);
                            }
                        }
                        if (!is_null($idSender)) {
                            if (!empty($paramsSender)) {
                                $controller->$method($idSender, $paramsSender);
                            } else {
                                $controller->$method($idSender);
                            }
                        } else {
                            if (!empty($paramsSender)) {
                                $controller->$method($paramsSender);
                            } else {
                                $controller->$method();
                            }
                        }
                    } else {
                        $controller->$method();
                    }
                } elseif (method_exists($controller, 'show') && is_callable([$controller, 'show'])) {
                    $method->show();
                }

                return $action;
            }
        }
        $this->pageError(404, $requestMethod);
    }
    public function pageError($code, $requestMethod)
    {
        $action['namespace'] = 'Controller\PageError';

        $action['actionUri'] = $_SERVER['REQUEST_URI'];
        $action['params'] = '';
        if ($_SERVER['TYPE_RESPONSE'] == 'JSON') {
            $action['method'] = 'showJson';
        } else {
            $action['method'] = 'showHtml';
        }

        \Silnik\Logs\LogLoad::setInstance(
            filename: PATH_LOG . '/loadpage.json',
            namespace: $action['namespace'],
            method: $action['method'],
            actionUri: $action['actionUri'],
            methodHttp: $requestMethod
        );
        $controller = new $action['namespace'];
        if ($_SERVER['TYPE_RESPONSE'] == 'JSON') {
            $action['method'] = 'showJson';
            $controller->showJson($code);
        } else {
            $action['method'] = 'showHtml';
            $controller->showHtml($code);
        }

        return $action;
    }
}
