<?php

namespace Silnik\Controller;

abstract class AbstractController
{
    final public function end($recordsLoadPage = null)
    {
        (\Silnik\Logs\LogLoad::getInstance())->register($recordsLoadPage);
    }
}