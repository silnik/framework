<?php

namespace Silnik\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\DBAL\DriverManager;

class EntityManagerFactory
{
    private $connection = [];

    public function getEntityManager(): EntityManagerInterface|bool
    {
        if (
            !empty(getenv('DB_USERNAME')) &&
            !empty(getenv('DB_USERNAME')) &&
            !empty(getenv('DB_USERNAME'))
        ) {
            $this->connection = [
                'driver' => getenv('DB_DRIVER'),
                'host' => getenv('DB_HOST'),
                'port' => getenv('DB_PORT'),
                'user' => getenv('DB_USERNAME'),
                'password' => getenv('DB_PASSWORD'),
                'dbname' => getenv('DB_DATABASE'),
                'charset' => getenv('DB_CHARSET'),
            ];
        } elseif (file_exists(PATH_ROOT . getenv('PATH_DATABASE') . '/db.sqlite')) {
            $this->connection = [
                'driver' => getenv('DB_DRIVER'),
                'path' => PATH_ROOT . getenv('PATH_DATABASE') . '/db.sqlite',
            ];
        }
        if ($this->connection) {
            $isDevMode = (getenv('APP_ENV') != 'production');

            $config = ORMSetup::createAttributeMetadataConfiguration(
                [dirname(__DIR__, 4) . '/src/Entity'],
                $isDevMode,
                null,
                null
            );
            $config->setProxyDir(dirname(__DIR__, 4) . '/src/DoctrineProxies');

            return new EntityManager(
                DriverManager::getConnection($this->connection, $config),
                $config
            );
        } else {
            return false;
        }
    }
}
