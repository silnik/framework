<?php

namespace Silnik;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;
use Silnik\ORM\EntityManagerFactory;

class ORM
{
    private static $em = null;
    public static function startEntityManager(): void
    {
        self::$em = (new EntityManagerFactory())->getEntityManager();
    }

    /**
     * @param string $class
     * @return EntityRepository
     */
    public static function rep($class): EntityRepository
    {
        return self::$em->getRepository($class);
    }

    /**
     * @return EntityManager
     */
    public static function em(): EntityManager
    {
        return self::$em;
    }

    public static function dumpQqlToLog($query): void
    {
        error_log(self::printSQL($query));
    }
    public static function dumpQql($query): void
    {
        echo self::printSQL($query);
        exit;
    }
     /**
     * Get SQL from query
     *
     * @author Yosef Kaminskyi
     * @param $query
     * @return string
     */
    public static function printSQL($query): string
    {
        $sql = $query->getSql();
        $paramsList = self::getListParamsByDql($query->getDql());
        $paramsArr = self::getParamsArray($query->getParameters());
        $fullSql = '';
        for ($i = 0;$i < strlen($sql);$i++) {
            if ($sql[$i] == '?') {
                $nameParam = array_shift($paramsList);

                if (is_string($paramsArr[$nameParam])) {
                    $fullSql .= '"' . addslashes($paramsArr[$nameParam]) . '"';
                } elseif (is_array($paramsArr[$nameParam])) {
                    $sqlArr = '';
                    foreach ($paramsArr[$nameParam] as $var) {
                        if (!empty($sqlArr)) {
                            $sqlArr .= ',';
                        }

                        if (is_string($var)) {
                            $sqlArr .= '"' . addslashes($var) . '"';
                        } else {
                            $sqlArr .= $var;
                        }
                    }
                    $fullSql .= $sqlArr;
                } elseif (is_object($paramsArr[$nameParam])) {
                    switch(get_class($paramsArr[$nameParam])) {
                        case 'DateTime':
                            $fullSql .= "'" . $paramsArr[$nameParam]->format('Y-m-d H:i:s') . "'";

                            break;
                        default:
                            $fullSql .= $paramsArr[$nameParam]->getId();
                    }
                } else {
                    $fullSql .= $paramsArr[$nameParam];
                }
            } else {
                $fullSql .= $sql[$i];
            }
        }

        return $fullSql;
    }

    /**
     * Get query params list
     *
     * @author Yosef Kaminskyi <yosefk@spotoption.com>
     * @param  \Doctrine\ORM\Query\Parameter $paramObj
     * @return array
     */
    protected static function getParamsArray($paramObj)
    {
        $parameters = [];
        foreach ($paramObj as $val) {
            /* @var $val Doctrine\ORM\Query\Parameter */
            $parameters[$val->getName()] = $val->getValue();
        }

        return $parameters;
    }
    public static function getListParamsByDql($dql)
    {
        $parsedDql = preg_split('/:/', $dql);
        $length = count($parsedDql);
        $parmeters = [];
        for ($i = 1;$i < $length;$i++) {
            if (ctype_alpha($parsedDql[$i][0])) {
                $param = (preg_split("/[' ' )]/", $parsedDql[$i]));
                $parmeters[] = $param[0];
            }
        }

        return $parmeters;
    }
}
