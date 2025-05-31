<?php
declare(strict_types = 1);

namespace Psancho\Galeizon\Adapter\SlimAdapter\Middleware;

use Psancho\Galeizon\View\StatusCode;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Authentification et autorisations
 *
 * inspiré de https://github.com/slimphp/Slim-HttpCache
 *
 * et http://www.mobify.com/blog/beginners-guide-to-http-cache-headers/
 *
 * @category Takoma
 * @package  Lizy\SlimInterface
 *
 * @since version 1.0.0
 */
class CacheHandler
{
    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        /** @var \Slim\Http\ServerRequest $request */
        $nocache = array_key_exists('nocache', $request->getParams());
        // Cache-Control header
        if (!$response->hasHeader('Cache-Control')) {
            if ($nocache) {
                $response = $response
                    ->withHeader('Cache-Control', 'private, max-age=0, no-cache, no-store')
                    ->withHeader('Pragma', 'no-cache');
            } else {
                // TODO: d'après config.ini
                $response = $response
                    ->withHeader('Cache-Control', 'private, max-age=300, must-revalidate');
            }
        }

        // Last-Modified header and conditional GET check
        $lastModified = $response->getHeaderLine('Last-Modified');
        if (strlen($lastModified) > 0) {
            $lastModified = is_numeric($lastModified) ? intval($lastModified) : strtotime($lastModified);
            $ifModifiedSince = $request->getHeaderLine('If-Modified-Since');
            if (strlen($ifModifiedSince) > 0 && $lastModified <= strtotime($ifModifiedSince)) {
                return $response->withStatus(StatusCode::HTTP_304_NOT_MODIFIED);
            }
        } else {
            // je crée si nécessaire un header Last-Modified basé sur la modif du fichier composer
            $file = realpath(__DIR__ . '/../../../../../composer.lock') ?: '';
            $timestamp =  filemtime($file) ?: 0;
            $defaultTZ = date_default_timezone_get();
            date_default_timezone_set('GMT');
            $response = $response->withHeader('Last-Modified', date('D, d M Y H:i:s e', $timestamp));
            date_default_timezone_set($defaultTZ);
        }

        // ETag header and conditional GET check
        $etag = $response->getHeader('ETag');
        $etag = reset($etag);
        if (!is_bool($etag)) {
            $ifNoneMatch = $request->getHeaderLine('If-None-Match');
            if (strlen($ifNoneMatch) > 0) {
                $etagList = preg_split('@\s*,\s*@', $ifNoneMatch);
                if (is_array($etagList) && (in_array($etag, $etagList, true) || in_array('*', $etagList, true))) {
                    return $response->withStatus(StatusCode::HTTP_304_NOT_MODIFIED);
                }
            }
        }

        return $response;
    }
}
