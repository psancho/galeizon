<?php
declare(strict_types = 1);

namespace Psancho\Galeizon\Adapter\SlimAdapter\Middleware;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Uri;
use Psancho\Galeizon\View\StatusCode;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Gestionnaire CORS (cross origin)
 *
 * @category Takoma
 * @package  Lizy\SlimInterface
 *
 * @since version 1.0.0
 */
class CorsHandler
{
    /** @var list<string> */
    protected $privatePathList = [];
    /** @var list<string> */
    protected $whiteList = [];
    /** @var list<string> */
    protected $blackList = [];

    /**
     * @param list<string> $privatePathList liste des endpoints privés, non soumis au CORS
     * @param list<string> $whiteList       liste des origines autorisées
     * @param list<string> $blackList       liste des origines interdites
     */
    public function __construct(array $privatePathList = [], array $whiteList = [], array $blackList = [])
    {
        $this->privatePathList = $privatePathList;
        $this->whiteList = $whiteList;
        $this->blackList = $blackList;
    }

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        if (in_array($path, $this->privatePathList, true)) {
            return $handler->handle($request);
        }

        $origins = $request->getHeader('Origin');
        $originHosts = [];
        foreach ($origins as $key => $origin) {
            $originUri = new Uri($origin);
            $originHosts[$key] = $originUri->getHost();
        }
        $allowedOrigin = null;
        foreach ($originHosts as $key => $host) {
            if ($host !== ''
                && (count($this->whiteList) === 0 || in_array($host, $this->whiteList, true))
                && !in_array($host, $this->blackList, true)
            ) {
                $allowedOrigin = $origins[$key];
            }
        }
        if (count($origins) > 0 && is_null($allowedOrigin)) {
            return new Response(StatusCode::HTTP_403_FORBIDDEN);
        }
        $headers = [];
        if (!is_null($allowedOrigin)) {
            $headers = ['Access-Control-Allow-Origin' => $allowedOrigin];
        }

        /** @var ServerRequestInterface $request */
        if (method_exists($request, 'isOptions') && $request->isOptions()) {
            if (count($requestedMethods = $request->getHeader('Access-Control-Request-Method')) > 0) {
                $headers['Access-Control-Allow-Methods'] = $requestedMethods;
            }
            if (count($requestedHeaders = $request->getHeader('Access-Control-Request-Headers')) > 0) {
                $headers['Access-Control-Allow-Headers'] = $requestedHeaders;
            }

            return new Response(StatusCode::HTTP_204_NO_CONTENT, $headers);
        }

        $response = $handler->handle($request);
        foreach ($headers as $header => $content) {
            $response = $response->withHeader($header, $content);
        }

        return $response;
    }

}
