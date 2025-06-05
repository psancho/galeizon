<?php
declare(strict_types = 1);

namespace Psancho\Galeizon\Adapter\SlimAdapter\Middleware;

use InvalidArgumentException;
use Nyholm\Psr7\Response;
use Psancho\Galeizon\Adapter\LogAdapter;
use Psancho\Galeizon\Adapter\SlimAdapter\RequestAttr;
use Psancho\Galeizon\Model\Auth\Authorization;
use Psancho\Galeizon\Model\Auth\DuplicateUserException;
use Psancho\Galeizon\Model\Auth\IdentityProvider;
use Psancho\Galeizon\Model\Auth\OwnerType;
use Psancho\Galeizon\Model\Auth\Requirements;
use Psancho\Galeizon\View\StatusCode;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthorizationHandler
{
    /** @param array<string, IdentityProvider> $providers */
    public function __construct(
        protected ?Requirements $requirements = new Requirements,
        protected array $providers = [],
        protected OwnerType $owners = OwnerType::any,
    )
    {}

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authzHeaders = $request->getHeader('Authorization');
        if (count($authzHeaders) === 0) {
            return new Response(StatusCode::HTTP_401_UNAUTHORIZED);
        }

        $tokenAccess = null;
        try {
            list($tokenType, $tokenAccess) = explode(' ', $authzHeaders[0]);

            if (strtolower($tokenType) !== 'bearer') {
                return new Response(StatusCode::HTTP_501_NOT_IMPLEMENTED, [
                    'Access-Control-Expose-Headers' => 'X-Status-Reason',
                    'X-Status-Reason' => 'Unhandled token type.',
                ]);
            }
            unset ($tokenType);

        } catch (\ErrorException $e) {
            // pas assez de données pour list()
            return new Response(StatusCode::HTTP_401_UNAUTHORIZED);
        }

        $user = null;

        if (count($providerHeaders = $request->getHeader('X-provider')) > 0
            && array_key_exists($providerName = $providerHeaders[0], $this->providers)
        ) {
            $provider = $this->providers[$providerName];
            if ($provider->isValid($tokenAccess)) {
                /* $provider::getUserinfo() DOIT gérer les exceptions éventuelles en cas d'http 401, par ex */
                $user = $provider->getUserinfo($tokenAccess);
            } else {
                return new Response(StatusCode::HTTP_401_UNAUTHORIZED);
            }

        } else {
            $authz = new Authorization;

            try {
                $authz = Authorization::decryptToken($tokenAccess);
            } catch (InvalidArgumentException $e) {
                LogAdapter::error($e);
                return new Response(StatusCode::HTTP_401_UNAUTHORIZED,
                    ['X-Status-Reason' => "AUTHZ_ERROR"]);
            }
            if (is_null($authz)) {
                return new Response(StatusCode::HTTP_401_UNAUTHORIZED,
                    ['X-Status-Reason' => "AUTHZ_MISSING"]);
            }
            if (!$authz->isValid()) {
                return new Response(StatusCode::HTTP_401_UNAUTHORIZED,
                    ['X-Status-Reason' => "AUTHZ_INVALID"]);
            }
            $request = $request->withAttribute(RequestAttr::authorization->name, $authz);
            if (!$authz->isScoped($this->requirements?->scope)) {
                return new Response(StatusCode::HTTP_403_FORBIDDEN,
                    ['X-Status-Reason' => "AUTHZ_MISSCOPED"]);
            }

            if ($this->owners->contains(OwnerType::client) && $authz->ownerType === OwnerType::client) {
                return $handler->handle($request);
            }

            $user = $authz->findUser();
        }

        if (!$this->owners->contains(OwnerType::user)) {
            return new Response(StatusCode::HTTP_403_FORBIDDEN,
                ['X-Status-Reason' => "AUTHZ_WRONG_OWNER_TYPE"]);
        }

        if ($user === null) {
            return new Response(StatusCode::HTTP_401_UNAUTHORIZED,
                ['X-Status-Reason' => "AUTHZ_USER_NOT_FOUND"]);
        }

        try {
            if (!$user->isRegistered()) {
                return new Response(StatusCode::HTTP_401_UNAUTHORIZED,
                    ['X-Status-Reason' => "AUTHZ_USER_NOT_REGISTERD"]);
            }
        } catch (DuplicateUserException $e) {
            return new Response(StatusCode::HTTP_400_BAD_REQUEST, [
                'Access-Control-Expose-Headers' => 'X-Status-Reason',
                'X-Status-Reason' => $e->getMessage(),
            ]);
        }

        $user->retrieveDecorated();

        if (!$user->meetRequirements($this->requirements?->user) || !$user->isActive()) {
            return new Response(StatusCode::HTTP_403_FORBIDDEN);
        }

        $user->updateLastAccess();

        return $handler->handle($request->withAttribute(RequestAttr::user->name, $user));
    }
}
