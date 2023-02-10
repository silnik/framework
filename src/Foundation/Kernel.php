<?php

declare(strict_types=1);

namespace Silnik\Foundation;

use Silnik\Sessions\Sessions;
use Silnik\Cache\Cache;
use Silnik\Uri\Uri;
use Silnik\Http\Http;
use Silnik\Logs\{ErrorPhp,LogLoad};
use Silnik\Dotenv\{Dotenv,DefaultEnv};

class Kernel
{
    private static $path = [];
    public $env = [];
    public $namespaceController;
    public $methodController;
    public $http;
    public $uri;
    public $alias = [];

    public function __construct(string $type = 'web')
    {
        if (!defined('PATH_ROOT')) {
            define('PATH_ROOT', dirname(__FILE__, ($type == 'terminal') ? 6 : 1));
        }
        self::varEnviroment();
        self::setDefines();
        define('DEPLOY_HASH', $this->getHashDeploy());
    }

    public function bootstrap()
    {
        $this->uri = Uri::getInstance();
        $this->http = Http::getInstance();
        $this->sessions();
        $this->database();
        $this->logs();
        $this->cache();
        $this->mount();
    }

    private static function setDefines()
    {
        define('UPLOAD_PUBLIC', str_replace('/public', '', $_ENV['PATH_UPLOAD_PUBLIC']));
        define('PATH_UPLOAD_PUBLIC', PATH_ROOT . $_ENV['PATH_UPLOAD_PUBLIC']);
        define('PATH_UPLOAD_PRIVARTE', PATH_ROOT . $_ENV['PATH_UPLOAD_PRIVARTE']);
        define('PATH_SESSIONS', PATH_ROOT . $_ENV['PATH_SESSIONS']);
        define('PATH_TMP', PATH_ROOT . $_ENV['PATH_TMP']);
        define('PATH_LOG', PATH_ROOT . $_ENV['PATH_LOG']);
        define('PATH_MIGRATIONS', PATH_ROOT . $_ENV['PATH_MIGRATIONS']);
        define('PATH_DATABASE', PATH_ROOT . $_ENV['PATH_DATABASE']);
        define('PATH_CACHE', PATH_ROOT . $_ENV['PATH_CACHE']);


        $makeDirectoryENV = [
            PATH_UPLOAD_PUBLIC,
            PATH_UPLOAD_PRIVARTE,
            PATH_SESSIONS,
            PATH_TMP,
            PATH_LOG,
            PATH_MIGRATIONS,
            PATH_DATABASE,
            PATH_CACHE,
        ];

        foreach ($makeDirectoryENV as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0777);
            }
        }
    }

    private function sessions()
    {
        return (new Sessions($path = PATH_SESSIONS))->start();
    }
    private function logs()
    {
        return new ErrorPhp();
    }
    private function cache()
    {
        return new Cache();
    }
    private static function varEnviroment()
    {
        (new Dotenv())->mergeEnv(
            (new DefaultEnv())->getData()
        )->load(PATH_ROOT)->build();
    }
    private function database()
    {
        if (
            class_exists('\Silnik\ORM\EntityManagerFactory') &&
            isset($_ENV['DB_USERNAME']) && !empty($_ENV['DB_USERNAME']) &&
            isset($_ENV['DB_PASSWORD']) && !empty($_ENV['DB_PASSWORD']) &&
            isset($_ENV['DB_DATABASE']) && !empty($_ENV['DB_DATABASE'])

        ) {
            \Silnik\ORM\ORM::startEntityManager();
        }
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
                $router = new \Silnik\Router\Router();
                $router->registerRoutesFromControllerAttributes(require_once PATH_ROOT . '/config/routes.php');
                $namespace = $router->resolve($this->uri->getUri(), $this->http->method());
                if (!is_null($namespace)) {
                    $LogLoad = (new LogLoad(['path' => PATH_LOG, 'limit' => 5]));
                    $LogLoad->register($namespace . ':' . $this->http->method());
                } else {
                    $controller = new \Controller\NotFound();
                    if ($_SERVER['TYPE_RESPONSE'] == 'JSON') {
                        $controller->showJson();
                    } else {
                        $controller->showHtml();
                    }
                }
            }
        }
    }
    public static function getHashDeploy()
    {
        if (file_exists(PATH_LOG . '/deploy.log')) {
            $deploylog = json_decode(file_get_contents(PATH_LOG . '/deploy.log'), true);
            if (!is_null($deploylog) && is_array($deploylog) && isset($deploylog['hash']) && !empty($deploylog['hash'])) {
                return $deploylog['hash'];
            }
        }
    }

    public static function afterDeploy()
    {
        if (!defined('PATH_ROOT')) {
            define('PATH_ROOT', dirname(__FILE__, 6));
        }
        self::varEnviroment();
        self::setDefines();

        Cache::clearPath(PATH_CACHE);
        Cache::clearPath(PATH_TMP);


        $hash = substr(md5((string)time()), 0, 7);
        file_put_contents(
            PATH_LOG . '/deploy.log',
            json_encode(['hash' => $hash,
                'generatedTime' => date('Y-m-d H:i:s'),
            ], JSON_PRETTY_PRINT)
        );

        return $hash;
    }
}
