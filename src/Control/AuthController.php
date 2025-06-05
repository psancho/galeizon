<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Control;

use Nyholm\Psr7\Uri;
use Psancho\Galeizon\Adapter\LogAdapter;
use Psancho\Galeizon\Adapter\MailerAdapter;
use Psancho\Galeizon\Adapter\SlimAdapter\Endpoint;
use Psancho\Galeizon\Adapter\SlimAdapter\RequestAttr;
use Psancho\Galeizon\Model\Auth\Authorization;
use Psancho\Galeizon\Model\Auth\AuthorizationRegistration;
use Psancho\Galeizon\Model\Auth\DuplicateUserException;
use Psancho\Galeizon\Model\Auth\Error as AuthError;
use Psancho\Galeizon\Model\Auth\PostPasswordResetPayload;
use Psancho\Galeizon\Model\Auth\PostTokenPayload;
use Psancho\Galeizon\Model\Auth\Registration;
use Psancho\Galeizon\Model\Auth\ResponseToken;
use Psancho\Galeizon\Model\Auth\TokenType;
use Psancho\Galeizon\Model\Auth\UserIdentity;
use Psancho\Galeizon\Model\Conf;
use Psancho\Galeizon\View\Json;
use Psancho\Galeizon\View\StatusCode;
use Psancho\Galeizon\View\Template;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\ServerRequest;
use Symfony\Component\Mime\Email;

class AuthController extends SlimController
{
    #[Endpoint(verb: "GET", path: "/authc/dialog", secure: false)]
    public function getAuthcDialog(ServerRequest $request, ResponseInterface $response): ResponseInterface
    {
        return $this->getAuthcDialogCore($request, $response);
    }

    /**
     * @param array{
     *     invalid_response_type?: string,
     *     missing_redirect_uri_or_clientid?: string,
     *     invalid_redirect_uri_template?: string,
     * } $errorMessages
     */
    protected function getAuthcDialogCore(ServerRequest $request, ResponseInterface $response,
        ?string $badRequestTemplate = null,
        array $errorMessages = []
    ): ResponseInterface
    {
        if (is_null($badRequestTemplate)) {
            $badRequestTemplate = Template::getInstance()->format('dialogBadRequest', Template::CORE);
        }
        if (!array_key_exists('invalid_response_type', $errorMessages)) {
            $errorMessages['invalid_response_type'] = 'Invalid response_type.';
        }
        if (!array_key_exists('missing_redirect_uri_or_clientid', $errorMessages)) {
            $errorMessages['missing_redirect_uri_or_clientid'] = 'Redirect_uri or client_id is missing.';
        }
        if (!array_key_exists('invalid_redirect_uri_template', $errorMessages)) {
            $errorMessages['invalid_redirect_uri_template'] = 'Invalid redirect_uri (%s) for the given client_id (%s).';
        }

        $responseType = self::getParamAsString($request, 'response_type');
        if (!in_array($responseType, ['code', 'token'], true)) {
            $body = sprintf($badRequestTemplate, $errorMessages['invalid_response_type']);

            $response->getBody()->write($body);
            return $response->withStatus(StatusCode::HTTP_400_BAD_REQUEST);
        }

        /** @var string $clientId */
        $clientId = self::getParamAsString($request, 'client_id', false, '');
        /** @var string $redirectUri */
        $redirectUri = self::getParamAsString($request, 'redirect_uri', false, '');

        if ($clientId === '' || $redirectUri === '') {
            $body = sprintf($badRequestTemplate, $errorMessages['missing_redirect_uri_or_clientid']);
            $response->getBody()->write($body);
            return $response->withStatus(StatusCode::HTTP_400_BAD_REQUEST);
        }
        // TODO
        // if (!SecurityMetada::registeredRedirectUri($clientId, $redirectUri)) {
        //     $body = sprintf($badRequestTemplate,
        //         sprintf($errorMessages['invalid_redirect_uri_template'], $redirectUri, $clientId)
        //     );
        //     $response->getBody()->write($body);
        //     return $response->withStatus(StatusCode::HTTP_400_BAD_REQUEST);
        // }

        /** @var array<string, string> $queryParams */
        $queryParams = $request->getParams() ?: [];
        $queryParams = self::_unmapped($queryParams, [
            'denied',
        ]);

        $ihmBaseUrl = static::getIhmBaseUrl($request);
        $query = self::paramToQuery($queryParams);

        return $response
            ->withStatus(StatusCode::HTTP_302_FOUND)
            ->withHeader('Location', "$ihmBaseUrl/sign-in?$query")
        ;
    }

    #[Endpoint(verb: "POST", path: "/authc")]
    public function postAuthc(ServerRequest $request, ResponseInterface $response): ResponseInterface
    {
        $username = '';
        $password = '';
        $responseType = '';
        /** @var ?string $scope */
        $scope = null;

        $parsedBody = $request->getParsedBody();
        if (is_object($parsedBody)) {
            $parsedBody = (array) $parsedBody;
        }
        if (is_array($parsedBody)) {
            extract($parsedBody);
        }

        // @phpstan-ignore function.impossibleType, booleanOr.alwaysTrue, identical.alwaysTrue
        if ($username === '' || !in_array($responseType, Authorization::RESPONSE_TYPE, true)) {
            return $response->withStatus(StatusCode::HTTP_400_BAD_REQUEST);
        }

        // TODO: client_id en param, pour connaître le client initial,
        //       avec vérif de l'inscription

        // @phpstan-ignore deadCode.unreachable
        $user = UserIdentity::authenticate($username, $password);
        if (is_null($user)) {
            return $response->withStatus(StatusCode::HTTP_404_NOT_FOUND);
        }
        if (!$user->isActive()) {
            return $response->withStatus(StatusCode::HTTP_403_FORBIDDEN);
        }
        try {
            if (!$user->isRegistered()) {
                return $response->withStatus(StatusCode::HTTP_401_UNAUTHORIZED);
            }
        } catch (DuplicateUserException) {
            return $response
                ->withStatus(StatusCode::HTTP_400_BAD_REQUEST)
                ->withHeader('Access-Control-Expose-Headers', 'X-Status-Reason')
                ->withHeader('X-Status-Reason', 'Email already associated with another account.')
            ;
        }

        $authz = Authorization::genAuthz($user->username, $responseType, $scope);

        $state = self::getParamAsString($request, 'state');
        if ($state) {
            assert(($authz instanceof ResponseToken));
            $authz->state = $state;
        }

        $json = Json::serialize(Json::cleanupProperties($authz));

        $response->getBody()->write($json);
        return $response->withHeader('Content-Type', 'application/json');
    }

    #[Endpoint(verb: "POST", path: "/token")]
    public function postToken(ServerRequest $request, ResponseInterface $response): ResponseInterface
    {
        $rawDemand = self::getBodyAsObject($request);
        if (is_null($rawDemand)) {
            return $response->withStatus(StatusCode::HTTP_400_BAD_REQUEST);
        }
        $demand = PostTokenPayload::fromObject($rawDemand);

        $tokenResponse = Authorization::genToken($demand);
        $json = Json::serialize(Json::cleanupProperties($tokenResponse));

        if ($tokenResponse instanceof AuthError) {
            $response->getBody()->write($json);
            return $response->withStatus($tokenResponse->error->statusCode());
        }

        $response->getBody()->write($json);
        return $response->withHeader('Content-Type', 'application/json');
    }

    #[Endpoint(verb: "POST", path: "/password/reset", secure: false)]
    public function postPwdReset(ServerRequest $request, ResponseInterface $response): ResponseInterface
    {
        return $this->postPwdResetCore($request, $response);
    }

    protected function postPwdResetCore(ServerRequest $request, ResponseInterface $response,
        ?string $emailTemplate = null,
        string $subject = 'Password reset'
    ): ResponseInterface
    {
        if (is_null($emailTemplate)) {
            $emailTemplate = Template::getInstance()->format('emailLinkRenew', Template::CORE);
        }

        $rawDemand = self::getBodyAsObject($request);
        if (is_null($rawDemand)) {
            return $response->withStatus(StatusCode::HTTP_400_BAD_REQUEST);
        }
        $demand = PostPasswordResetPayload::fromObject($rawDemand);

        if (!is_null($check = Authorization::isSameVendor($demand->client_id))) {
            $json = Json::serialize(Json::cleanupProperties($check));
            $response->getBody()->write($json);
            return $response->withStatus($check->error->statusCode());
        }

        $user = UserIdentity::retrieveEmailAddress($demand->contact);
        if (!is_null($user)) {
            $token = Authorization::genTokenPasswordReset($user->username);
            $ihmBaseUrl = static::getIhmBaseUrl($request, true);
            $url = "$ihmBaseUrl/renew?token=$token";
            $body = sprintf($emailTemplate, $url);
            $from = Conf::getInstance()->auth->noreply ?? 'noreply@galeizon.fr';

            $email = (new Email)
            ->subject($subject)
            ->from($from)
            ->to($user->email)
            ->html($body)
            ;
            MailerAdapter::getInstance()->send($email);
        }

        // Nota: c'est volontairement que je ne dis pas au client si tout se passe bien.
        return $response->withStatus(StatusCode::HTTP_204_NO_CONTENT);
    }

    #[Endpoint(verb: "PATCH", path: "/password", authz: "mandatoryUser")]
    public function patchPwd(ServerRequest $request, ResponseInterface $response): ResponseInterface
    {
        return $this->patchPwdCore($request, $response);
    }

    protected function patchPwdCore(ServerRequest $request, ResponseInterface $response,
        ?string $emailTemplate = null,
        string $subject = 'Authentication'
    ): ResponseInterface
    {
        if (is_null($emailTemplate)) {
            $emailTemplate = Template::getInstance()->format('emailLinkNew', Template::CORE);
        }

        /** @var ?string $password */
        $password = null;
        $parsedBody = $request->getParsedBody();
        if (is_object($parsedBody)) {
            $parsedBody = (array) $parsedBody;
        }
        if (is_array($parsedBody)) {
            extract($parsedBody);
        }

        if (empty($password)) { // @phpstan-ignore empty.notAllowed
            return $response->withStatus(StatusCode::HTTP_400_BAD_REQUEST);
        }

        /** @var Authorization $authz */
        $authz = $request->getAttribute(RequestAttr::authorization->name);
        if (!$authz->isUsedFor(TokenType::passwordResetToken)) {
            LogAdapter::notice('SECURITY attempt to change password with inappropriate token');
            return $response->withStatus(StatusCode::HTTP_400_BAD_REQUEST);
        }

        /** @var UserIdentity $user */
        $user = $request->getAttribute(RequestAttr::user->name);

        $user->changePassword($password);

        $body = sprintf($emailTemplate, $user->email);
        $from = Conf::getInstance()->auth->noreply ?? 'noreply@galeizon.fr';

        $email = (new Email)
        ->subject($subject)
        ->from($from)
        ->to($user->email)
        ->html($body)
        ;
        MailerAdapter::getInstance()->send($email);

        return $response->withStatus(StatusCode::HTTP_204_NO_CONTENT);
    }

    #[Endpoint(verb: "GET", path: "/userinfo", authz: "mandatoryUser")]
    public function getUserInfo(ServerRequest $request, ResponseInterface $response): ResponseInterface
    {
        // TODO si X-Provider est renseigné:
        // http 400 (actuellement: http 401)
        /** @var UserIdentity $user */
        $user = $request->getAttribute(RequestAttr::user->name);
        $json = Json::serialize($user);
        $response->getBody()->write($json);
        return $response
        ->withHeader('Cache-Control', 'private, max-age=0, no-cache, no-store')
        ->withHeader('Pragma', 'no-cache');
}

    #[Endpoint(verb: "GET", path: "/authc/users/create", secure: false)]
    public function createUsers(ServerRequest $request, ResponseInterface $response): ResponseInterface
    {
        return $this->createUsersCore($request, $response);
    }

    protected function createUsersCore(ServerRequest $request, ResponseInterface $response,
        ?string $emailTemplate = null,
        string $subject = 'New user regitered',
        string $userAdminRoute = '',
    ): ResponseInterface
    {
        if (is_null($emailTemplate)) {
            $emailTemplate = Template::getInstance()->format('emailNoticeNewUser', Template::CORE);
        }

        $token = self::getParamAsString($request, 'token', true);
        if (empty($token)) { // @phpstan-ignore empty.notAllowed
            return $response->withStatus(StatusCode::HTTP_400_BAD_REQUEST);
        }
        $authz = AuthorizationRegistration::decryptToken($token);
        if (is_null($authz)) {
            return $response->withStatus(StatusCode::HTTP_400_BAD_REQUEST);
        }
        if (!$authz->isValid()) {
            return $response
                ->withStatus(StatusCode::HTTP_401_UNAUTHORIZED)
                ->withHeader('Access-Control-Expose-Headers', 'X-Status-Reason')
                ->withHeader('X-Status-Reason', 'Registration demand has expired.')
            ;
        }
        if (!$authz->isUsedFor(TokenType::registrationToken)) {
            return $response->withStatus(StatusCode::HTTP_400_BAD_REQUEST);
        }
        try {
            $authz->registration->register()->changePassword($authz->registration->password)->register();
        } catch (DuplicateUserException $e) {
            return $response
                ->withStatus(StatusCode::HTTP_400_BAD_REQUEST)
                ->withHeader('Access-Control-Expose-Headers', 'X-Status-Reason')
                ->withHeader('X-Status-Reason', 'User already exists with different email ou username')
            ;
        }

        if (Conf::getInstance()->auth->notifyOnRegistration ?? false) {
            /** @var \Closure(): array<string> $getAdminEmails */
            $getAdminEmails = [UserIdentity::getDecorator(), 'getAdminEmails'](...);// @phpstan-ignore callable.nonCallable
            /** @var array<string> $admins */
            $admins = $getAdminEmails();
            if (count($admins) > 0) {
                $ihmBaseUrl = static::getIhmBaseUrl($request, true);
                $url = "$ihmBaseUrl{$userAdminRoute}";
                $body = sprintf($emailTemplate,
                    $authz->registration->firstname,
                    $authz->registration->lastname,
                    $url,
                );
                $from = Conf::getInstance()->auth->noreply ?? 'noreply@galeizon.fr';

                $email = (new Email)
                ->subject($subject)
                ->from($from)
                ->to(...$admins)
                ->html($body)
                ;
                MailerAdapter::getInstance()->send($email);
            }
        }

        return $response
            ->withStatus(StatusCode::HTTP_201_CREATED);
    }

    #[Endpoint(verb: "POST", path: "/authc/users/register")]
    public function postRegister(ServerRequest $request, ResponseInterface $response): ResponseInterface
    {
        return $this->postRegisterCore($request, $response);
    }

    protected function postRegisterCore(ServerRequest $request, ResponseInterface $response,
        ?string $emailTemplate = null,
        string $subject = 'Confirm registration'
    ): ResponseInterface
    {
        if (is_null($emailTemplate)) {
            $emailTemplate = Template::getInstance()->format('emailLinkRegistration', Template::CORE);
        }

        $rawDemand = self::getBodyAsObject($request);
        if (is_null($rawDemand)) {
            return $response->withStatus(StatusCode::HTTP_400_BAD_REQUEST);
        }
        $registration = Registration::fromObject($rawDemand);

        if ($registration->alreadyExists()) {
            return $response
                ->withStatus(StatusCode::HTTP_400_BAD_REQUEST)
                ->withHeader('Access-Control-Expose-Headers', 'X-Status-Reason')
                ->withHeader('X-Status-Reason', 'Username or email already used.');
        }
        if ($registration->email === '' || $registration->username === '') {
            return $response
                ->withStatus(StatusCode::HTTP_400_BAD_REQUEST)
                ->withHeader('Access-Control-Expose-Headers', 'X-Status-Reason')
                ->withHeader('X-Status-Reason', 'Username or email is missing.');
        }

        $token = AuthorizationRegistration::genTokenRegistration($registration);
        $ihmBaseUrl = static::getIhmBaseUrl($request, true);
        $url = "{$ihmBaseUrl}authc/users/create?token=$token";
        $body = sprintf($emailTemplate, $url);
        $from = Conf::getInstance()->auth->noreply ?? 'noreply@galeizon.fr';

        $email = (new Email)
        ->subject($subject)
        ->from($from)
        ->to($registration->email)
        ->html($body)
        ;
        MailerAdapter::getInstance()->send($email);

        return $response
            ->withStatus(StatusCode::HTTP_204_NO_CONTENT);
    }

    protected static function getIhmBaseUrl(ServerRequest $request, bool $absolute = false): string
    {
        $uri = $request->getUri();
        $path = $uri->getPath();
        $path = self::reducePath($path, Conf::getInstance()->slim->basepath ?? '');

        if (!isset($ihmBaseUrl)) {// @phpstan-ignore isset.variable
            static $ihmBaseUrl = Conf::getInstance()->auth->ihmBaseUrl ?? 'ihm';
        }

        assert(is_string($ihmBaseUrl));
        $ihmBaseUri = new Uri($ihmBaseUrl);
        if ($ihmBaseUri->getAuthority() === '') {
            if ($absolute) {
                $apiUrl = new Uri(substr((string) $uri, 0, - strlen($path)) ?: '');
                $ihmBaseUrl = $apiUrl . $ihmBaseUrl;
            } else {
                $ihmBaseUrl = str_repeat('../', max(count(explode('/', $path)) - 1, 0)) . $ihmBaseUrl;
            }
        }

        $ihmBaseUrl = new Uri($ihmBaseUrl);
        if (array_key_exists('HTTP_X_FORWARDED_HOST', $_SERVER)
            && $ihmBaseUrl->getScheme() !== ''
        ) {
            $ihmBaseUrl = $ihmBaseUrl->withScheme('https');
        }

        return (string) $ihmBaseUrl;
    }

    protected static function reducePath(string $path, string $reference): string
    {
        $t1 = explode('/', $path);
        $t2 = explode('/', $reference);

        while (reset($t1) === reset($t2)) {
            array_shift($t1);
            array_shift($t2);
        }

        return implode ('/', $t1);
    }

    /**
     * retourne le tableau clés, valeurs du tableau source dont la clé n'est pas une clé listée
     *
     * @param array<string, string> $source tableau source
     * @param array<string>         $keys   liste des clés à omettre
     *
     * @return array<string, string>
     */
    private static function _unmapped(array $source, array $keys): array
    {
        $ref = array_combine($keys, array_fill(0, count($keys), null));

        return array_udiff_uassoc($source, $ref,
            function ($vs, $vr) {
                return 0;
            },
            function ($ks, $kr) {
                if ($ks === $kr) {
                    return 0;
                } else if ($ks > $kr) {
                    return 1;
                } else {
                    return -1;
                }
            }
        );
    }
}
