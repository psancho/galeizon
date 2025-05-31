<?php
declare(strict_types = 1);

namespace Psancho\Galeizon\Adapter\SlimAdapter\Middleware;

use Nyholm\Psr7\Response;
use Psancho\Galeizon\Adapter\LogAdapter;
use Psancho\Galeizon\View\StatusCode;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpException;
use Slim\Exception\HttpNotFoundException;
use Throwable;
use UnexpectedValueException;

class ErrorHandler
{
    public function __invoke(ServerRequestInterface $request, Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails,
        ?LoggerInterface $logger = null
    ): ResponseInterface
    {
        $headers = [];
        if ($exception instanceof HttpException) {
            $status = $exception->getCode();
            if ($exception instanceof HttpNotFoundException) {
                $headers['Access-Control-Expose-Headers'] = 'X-Status-Reason';
                $headers['X-Status-Reason'] = 'ROUTE_NOT_FOUND';
            }
        } else if ($exception instanceof UnexpectedValueException) {
            $status = StatusCode::HTTP_400_BAD_REQUEST;
        } else {
            $status = StatusCode::HTTP_500_INTERNAL_SERVER_ERROR;
        }
        if ($logErrors && !($exception instanceof HttpException)) {
            LogAdapter::error($exception);
        }
        $response = new Response($status, $headers);
        $origin = $request->getHeader('Origin');
        if (count($origin) > 0) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
        }
        return $response->withStatus($status);
    }
}
