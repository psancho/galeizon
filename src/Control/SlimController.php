<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Control;

use Exception;
use Psancho\Galeizon\Adapter\LogAdapter;
use Psancho\Galeizon\Adapter\SlimAdapter\Container;
use Psancho\Galeizon\Model\Conf;
use Psancho\Galeizon\View\Json;
use Psancho\Galeizon\View\StatusCode;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\ServerRequest;
use stdClass;
use UnexpectedValueException;

abstract class SlimController
{
    public function __construct(
        protected Container $container
    )
    {}

    /** @param array<string, string> $args   */
    protected static function notImplemented(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $response
            ->withStatus(StatusCode::HTTP_501_NOT_IMPLEMENTED, $request->getMethod() . ' ' . $request->getUri()->getPath());
    }

    /**
     * Récupère le param requête de la query, ou à défaut, du header,
     * et le "déjsonne" avec la classe fournie
     *
     * @return object|stdClass|null
     *
     * @throws UnexpectedValueException
     */
    protected static function getParamAsObject(ServerRequest $request, string $name, bool $required = false)
    {
        $raw = self::_getParam($request, $name, $required);
        if (is_null($raw)) {
            return null;
        }

        $value = Json::unserialize($raw);

        if (is_object($value)) {
            return $value;

        } else if ($required) {
            throw new UnexpectedValueException(
                sprintf("Param [%s] required, but received in wrong format.", $name));
        }

        return null;
    }

    /** @throws UnexpectedValueException */
    protected static function getBodyAsObject(ServerRequestInterface $request): mixed
    {
        // la lecture d'un param peut perturber le bon fonctionnement
        try {
            $request->getBody()->rewind();
        } catch (Exception) {
        }
        $raw = $request->getBody()->getContents();
        if ($raw === '') {
            throw new UnexpectedValueException("Body is empty, json expected.");
        }

        $value = Json::unserialize($raw);

        if (is_object($value)) {
            return $value;
        }

        return null;
    }

    /** @throws UnexpectedValueException */
    protected static function getParamAsString(ServerRequest $request, string $name, bool $required = false, ?string $default = null): ?string
    {
        $value = self::_getParam($request, $name, $required);
        if (is_null($value)) {
            return $default;
        } else {
            return $value;
        }
    }

    /** @throws UnexpectedValueException */
    protected static function getParamAsInt(ServerRequest $request, string $name, bool $required = false, ?int $default = null): ?int
    {
        $value = self::_getParam($request, $name, $required);
        if (is_null($value) || trim($value) === '') {
            return $default;
        } else {
            return (int) $value;
        }
    }

    /** @throws UnexpectedValueException */
    protected static function getParamAsFloat(ServerRequest $request, string $name, bool $required = false, ?int $default = null): ?float
    {
        $value = self::_getParam($request, $name, $required);
        if (is_null($value) || trim($value) === '') {
            return $default;
        } else {
            return (float) $value;
        }
    }

    /** @throws UnexpectedValueException */
    protected static function getParamAsBool(ServerRequest $request, string $name, bool $required = false, ?bool $default = null): ?bool
    {
        $value = self::_getParam($request, $name, $required);
        if (is_null($value)) {
            return $default;
        } else {
            return in_array(strtolower($value), [1, 'true', 'yes', 'on', 'oui', 'succeed'], true);
        }
    }

    /** @throws UnexpectedValueException */
    private static function _getParam(ServerRequest $request, string $name, bool $required = false): ?string
    {
        $param = $request->getParam($name);

        if (is_null($param)) {
            $header = $request->getHeaderLine($name);
            if ($header === '') {
                if ($required) {
                    throw new UnexpectedValueException(
                        sprintf("Param [%s] required.", $name));
                }
                return null;

            } else {
                return urldecode($header);
            }

        } else {
            return is_string($param) ? urldecode($param) : null;
        }
    }

    protected static function traceRequest(ServerRequest $request): void
    {
        if (Conf::getInstance()->debug->traceRequests) {

            $headers = $request->getHeaders();
            foreach ($headers as &$header) {
                if (count($header) === 1) {
                    $header = reset($header);
                }
            }
            LogAdapter::debug('Request trace', [
                'endpoint' => $request->getMethod() . ' ' . $request->getUri()->getPath(),
                'headers' => $headers,
                'params' => $request->getParams(),
                'body' => $request->getParsedBody(),
            ]);
        }
    }

    protected static function isAccepted(ServerRequestInterface $request, string $type): bool
    {
        $acceptHeader = $request->getHeaderLine('accept');
        $acceptList = array_map('trim', array_unique(explode(',', $acceptHeader)));

        return in_array($type, $acceptList, true) || in_array('*/*', $acceptList, true);
    }

    protected static function isAcceptedJson(ServerRequestInterface $request): bool
    {
        return self::isAccepted($request, Json::MIMETYPE);
    }

    /**
     * Transforme un tableau associatif en chaîne query de la forme
     *     param1=valeur1&param2=valeur2
     *
     * @param array<string, string> $params ensemble clé=>valeur
     */
    protected static function paramToQuery(array $params): string
    {
        $query = implode('&', array_map(
            function ($k, $v) {
                return $k . '=' . urlencode($v);
            },
            array_keys($params), $params));

        return $query;
    }

    protected static function genLinkHeader(int $countItem, int $perPage, int $page): string
    {
        $linkTpl = '<?perPage=%d&page=%d>; rel=%s';
        $linkList = [sprintf($linkTpl, $perPage, 1, 'first')];
        $last = (int) ceil($countItem / $perPage);
        if ($page > 1) {
            $linkList[] = sprintf($linkTpl, $perPage, $page - 1, 'prev');
        }
        if ($page < $last) {
            $linkList[] = sprintf($linkTpl, $perPage, $page + 1, 'next');
        }
        $linkList[] = sprintf($linkTpl, $perPage, $last, 'last');

        return implode(', ', $linkList);
    }
}
