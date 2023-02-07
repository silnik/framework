<?php

namespace Silnik\Kernel;

use Silnik\Sessions\Sessions;
use Silnik\Cache\Cache;
use Silnik\Uri\Uri;
use Silnik\Http\Http;
use Silnik\Logs\{ErrorPhp,LogLoad};
use Dotenv\Dotenv;

class Kernel
{
    private static $path = [];
    public $env = [];
    public $method;
    public $http;
    public $uri;

    public function __construct()
    {
        if (!defined('PATH_ROOT')) {
            define('PATH_ROOT', dirname(__FILE__, 1));
        }
        $this->varEnviroment();
        $this->autoloadHelpers();
    }

    public function bootstrap()
    {
        $appuri = $this->readEnv();
        $this->httpRequestUri($appuri);
        $this->setDefines();
        $this->sessions();
        $this->logs();
        $this->cache();
        $this->mount();
        define('DEPLOY_HASH', $this->getHashDeploy());
    }
    public function terminal()
    {
        $appuri = $this->readEnv();
        $this->setDefines();
    }

    private function setDefines()
    {
        define('ROOT_UPLOAD', PATH_ROOT . self::$path['publicUploads']);
        define('ROOT_TMP_UPLOAD', PATH_ROOT . self::$path['tmp']);
        define('PATH_UPLOAD', str_replace('/public', '', self::$path['publicUploads']));
        define('PRIVATE_UPLOAD', PATH_ROOT . self::$path['privateUploads']);
        define('PATH_MIGRATIONS', PATH_ROOT . self::$path['migrations']);
        define('PATH_CACHE', PATH_ROOT . self::$path['tmp'] . '/cache/');
        define('PATH_SESS', PATH_ROOT . self::$path['sessions']);
        define('SQL_LOG', PATH_ROOT . self::$path['log'] . '/sql_error.log');
        define('PHP_LOG', PATH_ROOT . self::$path['log'] . '/php_error.log');
        define('DEPLOY_LOG', PATH_ROOT . self::$path['log'] . '/deploy.log');
    }

    private function readEnv()
    {
        $dotenv = Dotenv::createUnsafeImmutable(PATH_ROOT);
        $dotenv->load();

        $appParseUrl = '';
        if (isset($_SERVER['REQUEST_SCHEME']) && isset($_SERVER['SERVER_NAME'])) {
            $parse_uri_request = parse_url($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);

            $apps = (explode('|', $_ENV['APPS']));
            if (is_array($apps) && count($apps) > 0) {
                foreach ($apps as $key => $value) {
                    $uri = $_ENV['APP_URL_' . $value];
                    $parse_uri_app = parse_url($uri);

                    if ($parse_uri_request['host'] == $parse_uri_app['host']) {
                        if ($parse_uri_app['path'] == substr($parse_uri_request['path'], 0, strlen($parse_uri_app['path']))) {
                            $appName = $value;
                            $appParseUrl = $parse_uri_app;
                        }
                    }
                    putenv('APP_URL_' . $value . '=false');
                    unset($_ENV['APP_URL_' . $value], $_SERVER['APP_URL_' . $value]);
                }
            }
            putenv('APPS=false');
            unset($_ENV['APPS'], $_SERVER['APPS']);

            putenv('APP_NAME=' . $appName);
            $_SERVER['APP_NAME'] = $_ENV['APP_NAME'] = $appName;
        }

        return $appParseUrl;
    }

    private function sessions()
    {
        return (new Sessions(PATH_SESS))->start();
    }
    private function logs()
    {
        return new ErrorPhp();
    }
    private function cache()
    {
        return new Cache();
    }
    private function varEnviroment()
    {
        $this->env = require_once '../env.default.php';
        //\ORM::startEntityManager();
    }

    private function httpRequestUri($appParseUrl)
    {
        $this->uri = Uri::getInstance($appParseUrl);
        $this->http = Http::getInstance();
    }
    private function mount()
    {
        // path
        if (!preg_match(
            '/^' . str_replace('/', '\/', '/') . // INVALID_PATH_RE
            '(.*?)?' . // pathURI [1]
            '([^\/?]*\..*)?' . // elemento com "." [2]
            '(\?.*)?$/',  // elemento QS [3]
            $this->uri->getUri(),
            $arrayURI
        )
        ) {
            header('Location: ' . $this->uri->getBaseHref());
            exit;
        } else {
            // element com ponto
            if (!empty($arrayURI[2])) {
                $pos = strripos($arrayURI[2], '?');
                $v = '';
                if ($pos === true) {
                    $ext = explode('?', $arrayURI[2]);
                    $v = $ext[1];
                    $ext = $ext[0];
                } else {
                    $ext = $arrayURI[2];
                }
            } else {
                if (
                    (mb_strpos($this->uri->getFulluri(), '/api/') || mb_strpos($this->uri->getFulluri(), 'api.'))
                ) {
                    $controller = new ApiController();
                } else {
                    $controller = new WebController();
                }
                $namespace = $controller->getNamespace($this->uri->getSlices());
                $controller->instance();
                if (!empty($this->namespace)) {
                    $LogLoad = (new LogLoad(['path' => PATH_ROOT . self::$path['tmp'], 'limit' => 5]))->register($this->namespace . ':' . $this->method);
                }
            }
        }
    }

    private function autoloadHelpers()
    {
        spl_autoload_register(function ($class) {
            $classFileLibrary = PATH_ROOT . '/library/custom/helpers/' . $class . '.php';
            $classFileFramework = __DIR__ . '/helpers/' . $class . '.php';

            if (file_exists($classFileLibrary) && is_file($classFileLibrary)) {
                require_once $classFileLibrary;
            } elseif (file_exists($classFileFramework) && is_file($classFileFramework)) {
                require_once $classFileFramework;
            } elseif (substr(str_replace('\\', '', $class), 0, 6) != 'Models') {
                ErrosLogs::dump("{$class}.php not found", 'error');
                if (getenv('TYPE_APP') != 'API' && PHP_SAPI !== 'cli') {
                    new \Core\PrintCodeError(503);
                }
            }
        });
    }

    public static function createDirectories()
    {
        $d = DIRECTORY_SEPARATOR;
        $root = explode($d, __DIR__);
        array_pop($root);
        array_pop($root);
        array_pop($root);
        $root = implode($d, $root);

        foreach (self::$path as $key => $value) {
            $dir = $root . (implode($d, explode('/', $value)));
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
                file_put_contents($dir . $d . '.ignore', '/');
            }
        }
        file_put_contents(
            dirname(__FILE__, 4) . self::$path['log'] . '/deploy.log',
            ''
        );
    }
    public static function getHashDeploy()
    {
        if (file_exists(dirname(__FILE__, 4) . self::$path['log'] . '/deploy.log')) {
            $deploylog = json_decode(file_get_contents(dirname(__FILE__, 4) . self::$path['log'] . '/deploy.log'), true);
            if (!is_null($deploylog) && is_array($deploylog) && isset($deploylog['hash']) && !empty($deploylog['hash'])) {
                return $deploylog['hash'];
            }
        }

        $runnigAfterDeploy = json_decode(file_get_contents(PATH_ROOT . '/composer.json'), true);
        if (isset($runnigAfterDeploy['scripts']['after-deploy']) && $runnigAfterDeploy['scripts']['after-deploy']) {
            foreach ($runnigAfterDeploy['scripts']['after-deploy'] as $key => $value) {
                eval($value . '();');
            }
        }
    }

    public static function startEnv(\Composer\Script\Event $event)
    {
        $dir = new \DirectoryIterator(dirname(__FILE__, 4));
        foreach ($dir as $fileinfo) {
            if ($fileinfo->isFile()) {
                if (substr($fileinfo->getBasename(), 0, 4) == '.env') {
                    if ($event->getArguments()[0] == substr($fileinfo->getBasename(), 5)) {
                        rename($fileinfo->getBasename(), '_.env');
                    } else {
                        unlink($fileinfo->getBasename());
                    }
                }
            }
        }
        rename('_.env', '.env');
    }
    public static function clearStorageSess()
    {
        date_default_timezone_set('America/Sao_Paulo');
        (new Sessions(
            dirname(__FILE__, 4) . self::$path['sessions']
        )
        )->clearEmptySessions();
    }
    public static function clearStorageCache()
    {
        (new Cache())->clearPath(
            self::$path['tmp'] . '/cache/'
        );
    }
    public static function afterDeploy()
    {
        $hash = substr(md5(time()), 0, 7);
        file_put_contents(
            DEPLOY_LOG,
            json_encode(['hash' => $hash,
                'generatedTime' => date('Y-m-d H:i:s'),
            ], JSON_PRETTY_PRINT)
        );

        return $hash;
    }
}
