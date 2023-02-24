<?php


declare(strict_types=1);

namespace Silnik\Foundation;

use Silnik\Sessions\Sessions;
use Silnik\Cache\Cache;
use Silnik\Uri\Uri;
use Silnik\Http\Http;
use Silnik\Logs\{ErrorPhp};
use Silnik\Dotenv\{Dotenv};

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
            define(
                constant_name: 'PATH_ROOT',
                value: dirname(
                    path: __FILE__,
                    levels: ($type == 'terminal') ? 6 : 1
                )
            );
        }
        self::varEnviroment();
        self::setDefines();
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
        define(
            constant_name: 'UPLOAD_PUBLIC',
            value: str_replace(search: '/public', replace: '', subject: $_ENV['PATH_UPLOAD_PUBLIC'])
        );
        define(
            constant_name: 'PATH_UPLOAD_PUBLIC',
            value: PATH_ROOT . $_ENV['PATH_UPLOAD_PUBLIC']
        );
        define(
            constant_name: 'PATH_UPLOAD_PRIVARTE',
            value: PATH_ROOT . $_ENV['PATH_UPLOAD_PRIVARTE']
        );
        define(
            constant_name: 'PATH_SESSIONS',
            value: PATH_ROOT . $_ENV['PATH_SESSIONS']
        );
        define(
            constant_name: 'PATH_TMP',
            value: PATH_ROOT . $_ENV['PATH_TMP']
        );
        define(
            constant_name: 'PATH_LOG',
            value: PATH_ROOT . $_ENV['PATH_LOG']
        );
        define(
            constant_name: 'PATH_CACHE',
            value: PATH_ROOT . $_ENV['PATH_CACHE']
        );


        $makeDirectoryENV = [
            PATH_UPLOAD_PUBLIC,
            PATH_UPLOAD_PRIVARTE,
            PATH_SESSIONS,
            PATH_TMP,
            PATH_LOG,
            PATH_CACHE,
        ];

        foreach ($makeDirectoryENV as $dir) {
            if (!is_dir(filename: $dir)) {
                mkdir(
                    directory: $dir,
                    permissions: 0777,
                    recursive: true
                );
            }
        }
    }

    private function sessions()
    {
        return (new Sessions(
            path: PATH_SESSIONS
        ))->start();
    }
    private function logs(): ErrorPhp
    {
        return new ErrorPhp();
    }
    private function cache(): Cache
    {
        return new Cache();
    }
    private static function varEnviroment(): void
    {
        (new Dotenv())->load(
            path: PATH_ROOT
        );
    }
    private function database(): void
    {
        if (
            class_exists(class: '\Silnik\ORM\EntityManagerFactory') &&
            isset($_ENV['DB_USERNAME']) && !empty($_ENV['DB_USERNAME']) &&
            isset($_ENV['DB_PASSWORD']) && !empty($_ENV['DB_PASSWORD']) &&
            isset($_ENV['DB_DATABASE']) && !empty($_ENV['DB_DATABASE'])

        ) {
            \Silnik\ORM\ORM::startEntityManager();
        }
    }

    private function mount(): void
    {
        // path
        if (!preg_match(
            pattern: '/^' . str_replace(search: '/', replace: '\/', subject: '/') . // INVALID_PATH_RE
            '(.*?)?' . // pathURI [1]
            '([^\/?]*\..*)?' . // elemento com "." [2]
            '(\?.*)?$/',  // elemento QS [3]
            subject: $this->uri->getUri(),
            matches: $arrayURI
        )
        ) {
            header(header: 'Location: ' . $this->uri->getBaseHref());
            exit;
        } else {
            $router = new \Silnik\Router\Router();
            $router->registerRoutesFromControllerAttributes(
                controllers: require_once PATH_ROOT . '/src/routes.php'
            );

            try {
                $router->resolve(
                    requestUri: $this->uri->getUri(),
                    requestMethod: $this->http->method()
                );
            } catch (\Throwable $th) {
                ErrorPhp::registerError(
                    message: $th->getMessage(),
                    level:'ERROR'
                );
                $router->pageError(
                    code: 500,
                    requestMethod: $this->http->method()
                );
            }
        }
    }
    public static function afterDeploy()
    {
        if (!defined(constant_name: 'PATH_ROOT')) {
            define(
                constant_name: 'PATH_ROOT',
                value: dirname(
                    path: __FILE__,
                    levels: 6
                )
            );
        }
        self::varEnviroment();
        self::setDefines();

        Cache::clearPath(dir: PATH_TMP);

        $hash = substr(string: md5(string: (string)time()), offset: 0, length: 7);
        file_put_contents(
            filename: PATH_LOG . '/deploy.log',
            data: json_encode(
                value: [
                    'hash' => $hash,
                    'generatedTime' => date(format: 'Y-m-d H:i:s'),
                ],
                flags: JSON_PRETTY_PRINT
            )
        );

        return $hash;
    }
}
