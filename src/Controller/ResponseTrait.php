<?php

namespace Silnik\Controller;

trait ResponseTrait
{
    private $message = [
        'GET' => [
            200 => '',
            201 => '',
            204 => '',
            400 => '',
            401 => '',
            404 => '',
            406 => '',
            500 => '',
        ],
        'POST' => [
            200 => '',
            201 => '',
            204 => '',
            400 => '',
            401 => '',
            404 => '',
            406 => '',
            500 => '',
        ],
        'PUT' => [
            200 => '',
            201 => '',
            204 => '',
            400 => '',
            401 => '',
            404 => '',
            406 => '',
            500 => '',
        ],
        'PATH' => [
            200 => '',
            201 => '',
            204 => '',
            400 => '',
            401 => '',
            404 => '',
            406 => '',
            500 => '',
        ],
        'DELETE' => [
            200 => '',
            201 => '',
            204 => '',
            400 => '',
            401 => '',
            404 => '',
            406 => '',
            500 => '',
        ],
    ];

    public function defaultMessage($method, $code)
    {
        return (isset($this->message[$method][$code]) && empty($this->message[$method][$code]) ? $this->message[$method][$code] : '');
    }
}
