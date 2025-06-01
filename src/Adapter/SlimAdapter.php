<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Adapter;

use Psancho\Galeizon\Adapter\SlimAdapter\Container;
use Psancho\Galeizon\Adapter\SlimAdapter\Middleware\CacheHandler;
use Psancho\Galeizon\Adapter\SlimAdapter\Middleware\CorsHandler;
use Psancho\Galeizon\Adapter\SlimAdapter\Middleware\ErrorHandler;
use Psancho\Galeizon\Adapter\SlimAdapter\RoutesMapper;
use Psancho\Galeizon\Model\Conf;
use Psancho\Galeizon\Pattern\Singleton;
use Psancho\Galeizon\View\StatusCode;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Slim\App;
use Slim\Factory\AppFactory;

abstract class SlimAdapter extends Singleton
{
    /** @var App<ContainerInterface|null> */
    protected App $app;// @phpstan-ignore property.uninitialized

    #[\Override]
    protected function build(): void
    {
        self::_manageUncaughtErrors();

        $container = new Container;

        AppFactory::setContainer($container);

        $this->app = AppFactory::create();

        $slimConf = Conf::getInstance()->slim;
        $basepath = $slimConf?->basepath;
        if (is_null($basepath) || strlen($basepath) === 0) {
            throw new RuntimeException("CONF: slim.basepath not set", 1);
        }
        $this->app->setBasePath($basepath);

        $this->app->addRoutingMiddleware();

        // le dernier middleware ajouté est invoqué en 1er
        // les globaux sont insérés après ceux des routes puis des groupes

        /* Liste des endpoints privés (même nom de domaine), c-à-d pour lesquels le CORS n'est pas actif. */
        $privatePathList = [
            'authc',
            'authc/users',
            'authc/users/register',
            'password',
        ];
        $this->app->add(new CorsHandler($privatePathList, $slimConf->whiteList ?? [], $slimConf->blackList ?? []));

        $this->app->add(new CacheHandler);
        $errorMiddleware = $this->app->addErrorMiddleware(true, true, true);
        $errorMiddleware->setDefaultErrorHandler(new ErrorHandler);

        static::mapRoutes($this->app);
    }

    public function listen(): void
    {
        $this->app->run();
    }

    /** @param App<ContainerInterface|null> $app */
    protected static function mapRoutes(App $app): void
    {
        $handlers = RoutesMapper::getInstance()->handlers;
        extract($handlers);

        include dirname(__DIR__, 5) . "/routes/routesMap.php";
    }

    private static function _manageUncaughtErrors(): void
    {
        register_shutdown_function(function (): void
        {
            /** @phpstan-var ?array{type: int, message: string, file: string, line: int} $error */
            $error = error_get_last();
            $phpBug67881 = 'DateTime::__construct(): Failed to parse time string';
            if (!is_null($error) && substr($error['message'], 0, strlen($phpBug67881)) !== $phpBug67881) {
                $e = new \ErrorException($error["message"], $error["type"], 0, $error["file"], $error["line"]);
                LogAdapter::error($e);
                if (!is_bool(ob_get_length())) {
                    ob_clean();
                }
                if (!headers_sent()) {
                    header('HTTP/1.1 ' . StatusCode::getMessageForCode(StatusCode::HTTP_500_INTERNAL_SERVER_ERROR));
                }
            }
        });
    }
}
