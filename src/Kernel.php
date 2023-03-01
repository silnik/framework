<?php


declare(strict_types=1);

namespace Silnik;

use Silnik\Logs\ErrorPhp;

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
                    levels: ($type == 'terminal') ? 5 : 1
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
        $this->logs();
        $this->cache();
        $this->database();
        $this->mount();
    }

    private static function setDefines()
    {
        define(
            constant_name: 'UPLOAD_PUBLIC',
            value: str_replace(search: '/public', replace: '', subject: getenv('PATH_UPLOAD_PUBLIC'))
        );
        define(
            constant_name: 'PATH_UPLOAD_PUBLIC',
            value: PATH_ROOT . getenv('PATH_UPLOAD_PUBLIC')
        );
        define(
            constant_name: 'PATH_UPLOAD_PRIVARTE',
            value: PATH_ROOT . getenv('PATH_UPLOAD_PRIVARTE')
        );
        define(
            constant_name: 'PATH_SESSIONS',
            value: PATH_ROOT . getenv('PATH_SESSIONS')
        );
        define(
            constant_name: 'PATH_TMP',
            value: PATH_ROOT . getenv('PATH_TMP')
        );
        define(
            constant_name: 'PATH_LOG',
            value: PATH_ROOT . getenv('PATH_LOG')
        );
        define(
            constant_name: 'PATH_CACHE',
            value: PATH_ROOT . getenv('PATH_CACHE')
        );
        define(
            constant_name: 'PATH_MIGRATIONS',
            value: PATH_ROOT . getenv('PATH_MIGRATIONS')
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
        (new Dotenv\Loader())->build();
    }
    private function database(): void
    {
        if (
            class_exists(class: '\Silnik\ORM\EntityManagerFactory') &&
           !empty(getenv('DB_USERNAME')) &&
           !empty(getenv('DB_PASSWORD')) &&
           !empty(getenv('DB_DATABASE'))

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
