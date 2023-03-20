<?php

declare(strict_types=1);

namespace Silnik\Router;

use Silnik\Uri;
use Silnik\Route;

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
            $reflectionController = new \ReflectionClass(objectOrClass: $controller);
            $parent = $reflectionController->getParentClass();

            foreach ($reflectionController->getMethods() as $method) {
                $call = new \ReflectionMethod($controller, $method->getName());
                if ($call->isPublic() == true) {
                    $attributes = $method->getAttributes(Route::class, \ReflectionAttribute::IS_INSTANCEOF);
                    if (!empty($attributes)) {
                        $comment = '';
                        if ($call->getDocComment() != false) {
                            $comment = preg_match_all(
                            pattern: "#([a-zA-Z]+\s*[a-zA-Z0-9, ()_].*)#",
                            subject: $call->getDocComment(),
                            matches: $matches,
                            flags: PREG_PATTERN_ORDER
                            );
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
                            $this->register($route['methods'], $url, ['uri' => $route[0], 'namespace' => $controller, 'method' => $method->getName(), 'params' => $params, 'message' => $comment]);
                        }
                    }
                }
            }
        }
    }

    public function register(array $requestMethod, string $route, callable |array $action): self
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
        $combinationStrength = 0;
        $uri = Uri::getInstance();
        $baseHref = rtrim(string: $uri->getBaseHref(), characters: '/');
        if (isset($this->routes[$requestMethod])) {
            foreach ($this->routes[$requestMethod] as $key => $value) {
                $s = mb_strpos(
                haystack: $baseHref . $requestUri,
                needle: $baseHref . $key
                );
                if ($s !== false && $combinationStrength <= (int) strlen($baseHref . $key)) {
                    $combinationStrength = (int) strlen($baseHref . $key);
                    $action = $this->routes[$requestMethod][$key] ?? null;
                }
            }
            if (isset($action)) {
                $method = $action['method'];
                $params = $action['params'];

                \Silnik\Logs\LogLoad::setInstance(
                filename: PATH_LOG . '/loadpage.json', namespace
                    : $action['namespace'],
                method: $action['method'],
                actionUri: $action['uri'],
                methodHttp: $requestMethod
                );
                $controller = new $action['namespace'];
                if (
                    !empty($method) && method_exists(
                    object_or_class: $controller,
                    method: $method
                    ) && is_callable(value: [$controller, $method])
                ) {
                    if (is_array(value: $params) && count(value: $params) > 0) {
                        $idSender = null;
                        $paramsSender = [];
                        foreach ($params as $k => $v) {
                            if ($v == '{id}') {
                                $idSender = (int) $uri->nextSlice(ref: $k);
                            } else {
                                $paramsSender[$k] = $uri->nextSlice(ref: $k);
                            }
                        }
                        if (!is_null(value: $idSender)) {
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
                } elseif (
                    method_exists(
                    object_or_class: $controller,
                    method: 'show'
                    ) && is_callable(
                    value: [$controller, 'show']
                    )
                ) {
                    $method->show();
                }
                return $action;
            }
        }
        $this->pageError(code: 404, requestMethod: $requestMethod);
    }
    public function pageError($code, $requestMethod)
    {
        $action['namespace'] = 'Controller\PageError';

        $action['actionUri'] = $_SERVER['REQUEST_URI'];
        $action['params'] = '';

        \Silnik\Logs\LogLoad::setInstance(
        filename: PATH_LOG . '/loadpage.json', namespace
            : $action['namespace'],
        method: 'show',
        actionUri: $action['actionUri'],
        methodHttp: $requestMethod
        );

        $controller = new $action['namespace'];
        $action['method'] = 'show';
        $controller->show($code);

        return $action;
    }
}