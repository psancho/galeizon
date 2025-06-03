<?php
declare(strict_types = 1);

namespace Psancho\Galeizon\Adapter\SlimAdapter;

use OutOfBoundsException;
use Psancho\Galeizon\Control\SlimController;
use Psancho\Galeizon\Pattern\Singleton;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionMethod;
use RegexIterator;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\FileIteratorSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use SplFileInfo;
use SplFileObject;

/** @phpstan-type CallableMiddleware callable(ServerRequestInterface, RequestHandlerInterface): ResponseInterface */
class RoutesMapper extends Singleton
{
    protected ?SplFileObject $scriptFile = null;
    /** @var array<string, MiddlewareInterface|string|CallableMiddleware> */
    public protected(set) array $handlers = [];
    /** @var list<string> */
    protected array $controllerDirs = [];
    /** @var list<string> */
    protected array $routes = [];

    #[\Override]
    protected function build(): void
    {
        if (file_exists($path = dirname(__DIR__, 6) . "/src/Control")) {
            $this->controllerDirs[] = $path;
        }
        if (file_exists($path = dirname(__DIR__, 3) . "/src/Control")) {
            $this->controllerDirs[] = $path;
        }

        $defaultHandlers = [
            // 'adminSchema' => new AuthorizationHandler((new Requirements)->forScope('admin_schema')),
        ];

        $customHandlersFilepath = dirname(__DIR__, 6) . "/routes/authorizationHandlers.php";
        /** @var array<string, MiddlewareInterface|string|callable> $handlers */
        $handlers = file_exists($customHandlersFilepath)
        ? require $customHandlersFilepath : [];

        $this->handlers = array_merge($defaultHandlers, $handlers);
    }

    public function mapControllerRoutes(): self
    {
        $source = $this->getControllersSourceLocator();
        $reflect = new DefaultReflector($source);
        $classes = $reflect->reflectAllClasses();

        // ségrégation des classes ayant un enfant
        $filteredClasses = [];
        foreach ($classes as $class) {
            if (in_array(SlimController::class, $parents = $class->getParentClassNames(), true)) {
                // @phpstan-ignore argument.type
                $filteredClasses[$class->getName()] = new class(false, array_diff($parents, [SlimController::class]))
                {
                    /** @param list<string> $parents */
                    function __construct(public bool $isExtended, public array $parents) {}
                };
            }
        }
        foreach ($filteredClasses as $childClass) {
            foreach ($childClass->parents as $parent) {
                if (array_key_exists($parent, $filteredClasses)) {
                    $filteredClasses[$parent]->isExtended = true;
                }
            }
        }
        $filteredClasses = array_filter($filteredClasses, fn ($v) => !$v->isExtended);

        foreach ($classes as $class) {
            if (array_key_exists($class->getName(), $filteredClasses)) {
                $this->mapClassRoutes($class);
            }
        }

        return $this;
    }

    protected function getControllersSourceLocator(): SourceLocator
    {
        $locator = (new BetterReflection)->astLocator();

        /** @var list<FileIteratorSourceLocator> $sources */
        $sources = [];
        foreach ($this->controllerDirs as $dir) {
            /** @var RegexIterator<int, SplFileInfo, RecursiveIteratorIterator<RecursiveDirectoryIterator>> $it */
            $it = new RegexIterator(
                new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir)
                ),
                '/^.+Controller\.php$/i'
            );
            $sources[] = new FileIteratorSourceLocator(
                $it,
                $locator,
            );
        }

        return new AggregateSourceLocator($sources);
    }

    protected function prepareScriptFile(): SplFileObject
    {
        if (!$this->scriptFile instanceof SplFileObject) {
            $this->scriptFile = new SplFileObject(dirname(__DIR__, 6) . "/routes/routesMap.php", "w");
            $this->scriptFile->fwrite(<<<PHP
            <?php

            declare(strict_types=1);

            /**
             * auto-generated script
             */


            PHP);
        }
        return $this->scriptFile;
    }

    /**
     * ATTENTION : une classe directement ajoutée peut doubloner la classe qu'elle étend.
     *             ne l'utiliser que pour le dev
     *
     * @example :
     * RoutesMapper::getSingleton()
     *     ->mapClassRoutes((new BetterReflection)
     *     ->reflector()
     *     ->reflectClass(MyClass::class)
     * );
     */
    protected function mapClassRoutes(ReflectionClass $class): void
    {
        $scriptFile = $this->prepareScriptFile();

        $endpointDefaultArgs = self::getEndpointDefaultArgs();

        $className = $class->getName();

        $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

        $hasEndpoints = false;
        foreach ($methods as $methodName => $method) {
            if ($method->isAbstract() || $method->isConstructor() || $method->isStatic()) {
                continue;
            }

            $Endpoints = $method->getAttributesByName(Endpoint::class);
            foreach ($Endpoints as $endpoint) {
                /**
                 * @var string       $verb
                 * @var string       $path
                 * @var bool         $secure
                 * @var ?string       $authz
                 * @var list<string> $middlewares
                 */
                list('verb' => $verb, 'path' => $path, 'secure' => $secure, 'authz' => $authz, 'middlewares' => $middlewares)
                    = array_merge($endpointDefaultArgs, $endpoint->getArguments());
                $verbMethod = strtolower($verb);
                if (!in_array($verbMethod, ['get', 'post', 'put', 'patch', 'delete'], true)) {
                    continue;
                }

                if (!is_null($authz) && !array_key_exists($authz, $this->handlers)) {
                    throw new EndpointDefinitionException("Endpoint: undefined authz handler '$authz' in $className::$methodName()", 1);
                }

                $route = "$verbMethod $path";
                if (in_array($route, $this->routes, true)) {
                    $instruction = "\$app->$verbMethod('$path', '$className:$methodName')";
                    $scriptFile->fwrite(sprintf("\nthrow new %s(\"%s\");\n",
                        EndpointDefinitionException::class,
                        ($reason = "Endpoint: duplicate route ($route)")
                    ));
                    throw new EndpointDefinitionException($reason, 1);
                }
                $this->routes[] = $route;

                $instruction = "\$app->$verbMethod('$path', '$className:$methodName')";

                foreach ($middlewares as $middleware) {
                    if (!array_key_exists($middleware, $this->handlers)) {
                        throw new EndpointDefinitionException("Endpoint: undefined middleware handler '$middleware' in $className::$methodName()", 1);
                    }
                    $instruction .= "->add(\$$middleware)";
                }
                if (!is_null($authz)) {
                    $instruction .= "->add(\$$authz)";
                }
                if ($secure) {
                    $instruction .= "->add(\$securityHandler)";
                }

                $scriptFile->fwrite("$instruction;\n");

                $hasEndpoints = true;
            }
        }
        if (!$hasEndpoints) {
            $scriptFile->fwrite("// $className: no endpoints");
        }
        $scriptFile->fwrite("\n");
    }

    /** @return array<string, int|float|string|bool|array<string|int, int|float|string|bool>|null> */
    protected static function getEndpointDefaultArgs() : array
    {
        $endpointClass = (new BetterReflection)
        ->reflector()
        ->reflectClass(Endpoint::class)
        ;

        $defaultArgs = [];

        $constructor = $endpointClass->getMethod('__construct');

        if (!is_null($constructor)) {
            $params = $constructor->getParameters();

            foreach ($params as $param) {
                if ($param->isOptional()) {
                    $defaultArgs[$param->getName()] = $param->getDefaultValue();
                }
            }
        }

        /** @var array<non-empty-string, int|float|string|bool|array<string|int, int|float|string|bool>|null> $defaultArgs */
        return $defaultArgs;
    }
}

class EndpointDefinitionException extends OutOfBoundsException {}
