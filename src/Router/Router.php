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
                            $this->register($route['methods'], $url, ['uri' => $route[0], 'namespace' => $controller, 'method' => $method->getName(), 'params' => $params, 'typeResponse' => $typeResponse]);
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
                $controller = new $action['namespace'];
                $method = $action['method'];
                $params = $action['params'];
                $typeResponse = $action['typeResponse'];
                $uriformat = $action['params'];

                putenv('TYPE_RESPONSE=' . $typeResponse);
                $_SERVER['TYPE_RESPONSE'] = $typeResponse;
                $_ENV['TYPE_RESPONSE'] = $typeResponse;

                if (!empty($method) && method_exists($controller, $method) && is_callable([$controller, $method])) {
                    if (is_array($params) && count($params) > 0) {
                        $idSender = null;
                        $paramsSender = [];
                        foreach ($uriformat as $k => $v) {
                            if ($v == '{id}') {
                                $idSender = (int)$uri->nextSlice($k);
                            } else {
                                $paramsSender[substr($v, 1, -1)] = $uri->nextSlice($k);
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

                return $action['namespace'];
            }

            return null;
        }
    }
}
