<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Auth;

use InvalidArgumentException;
use PDO;
use PDOException;
use Psancho\Galeizon\App;
use Psancho\Galeizon\Model\Auth\Error as AuthError;
use Psancho\Galeizon\View\Json;

/**
 * @property-read UserIdentity $user
 */
class Authorization
{
    /** @var string */
    public const TOKEN_TYPE = 'bearer';
    /** @var array<string> */
    public const RESPONSE_TYPE = ['code', 'token'];

    public function __construct(
        public string $ownerId = '',
        public OwnerType $ownerType = OwnerType::user,
        public int $timestamp = 0,
        public TokenType $usage = TokenType::accessToken,
        public string $scope = '',

    )
    {}

    public static function fromJson(string $json): static
    {
        $authz = new static;// @phpstan-ignore new.static
        $args = Json::tryUnserialize($json);
        if (is_array($args)) {
            $args = (object) $args;
        }
        if (is_object($args)) {
            if (property_exists($args, 'ownerId') && is_string($args->ownerId)) {
                $authz->ownerId = trim($args->ownerId);
            }
            if (property_exists($args, 'ownerType') && is_string($args->ownerType)) {
                $authz->ownerType = OwnerType::tryFromName($args->ownerType) ?? OwnerType::user;
            }
            if (property_exists($args, 'timestamp') && is_numeric($args->timestamp)) {
                $authz->timestamp = (int) $args->timestamp;
            }
            if (property_exists($args, 'usage') && is_int($args->usage)) {
                $authz->usage = TokenType::tryFrom($args->usage) ?? TokenType::accessToken;
            }
            if (property_exists($args, 'scope') && is_string($args->scope)) {
                $authz->scope = trim($args->scope);
            }
        }
        return $authz;
    }

    public function isValid(): bool
    {
        // TODO verifier si le owner est tjs autorisé
        return time() < $this->timestamp + $this->usage->lifetime();
    }

    public function isUsedFor(TokenType $usage): bool
    {
        return $this->usage === $usage;
    }

    public function isScoped(?string $scope): bool
    {
        $scopeList = explode(' ', $this->scope);
        return is_null($scope) || in_array($scope, $scopeList, true);
    }

    public function findUser(): ?UserIdentity
    {
        $user = null;
        // @phpstan-ignore empty.notAllowed
        if ($this->ownerType === OwnerType::user && !empty($this->ownerId)) {
            $user = UserIdentity::retrieveByUsername($this->ownerId);
        }
        return $user;
    }

    /** @throws InvalidArgumentException */
    public static function decryptToken(string $token): ?static
    {
        $decrypted = Token::decrypt($token);

        return is_null($decrypted) ? null : static::fromJson($decrypted);
    }

    /** @throws InvalidArgumentException */
    public function encryptToken(): string
    {
        return Token::encrypt(Json::serialize($this));
    }

    /**
     * fournit un code ou un token pour le endpoint GET /authC (flux implicit ou authz_code)
     *
     * @param value-of<self::RESPONSE_TYPE> $responseType
     */
    public static function genAuthz(string $username, string $responseType, ?string $scope): ResponseAuthz
    {
        if ($responseType === self::RESPONSE_TYPE[0]) {
            return static::genAuthzCode($username, $scope);
        } else {
            return static::genAuthzToken($username, GrantFlowType::implicit, $scope);
        }
    }

    protected static function genAuthzCode(string $username, ?string $scope): ResponseCode
    {
        $response = new ResponseCode;

        $auth = new self(
            ownerId: $username,
            timestamp: time(),
            usage: TokenType::authorizationCode,
        );
        if (!is_null($scope)) {
            $auth->scope = $scope;
        }

        $response->code = $auth->encryptToken();

        return $response;
    }

    protected static function genAuthzToken(string $username, GrantFlowType $grantFlow, ?string $scope): ResponseToken
    {
        $response = new ResponseToken;
        $response->expires_in = TokenType::accessToken->lifetime();

        $authAccess = new self(
            ownerId: $username,
            timestamp: time(),
        );
        if (!is_null($scope)) {
            $authAccess->scope = $scope;
        }

        $response->access_token = $authAccess->encryptToken();
        $response->token_type = self::TOKEN_TYPE;

        if ($grantFlow === GrantFlowType::authorization_code) {
            $authRefresh = new self(
                ownerId: $username,
                timestamp: time(),
                usage: TokenType::refreshToken,
            );
            $response->refresh_token = $authRefresh->encryptToken();
        }

        return $response;
    }

    /** fournit un token pour le endpoint POST /token, (flux authz_code, pwd, client_cred ou refresh) */
    public static function genToken(PostTokenPayload $demand): ResponseAuthz|AuthError
    {
        switch ($demand->grant_type) {
        case GrantFlowType::authorization_code:
            return static::genTokenUsingAuthzCode($demand);
        case GrantFlowType::client_credentials:
            return static::genTokenUsingClientCred($demand);
        case GrantFlowType::password:
            return static::genTokenUsingPwd($demand);
        case GrantFlowType::refresh_token:
            return static::genTokenUsingRefresh($demand);
        default:
            return new AuthError(ErrorType::invalid_grant);
        }
    }

    protected static function genTokenUsingAuthzCode(PostTokenPayload $demand): ResponseAuthz|AuthError
    {
        if ($demand->code === '') {
            return new AuthError(ErrorType::invalid_request);
        }

        $codeAuth = static::decryptToken($demand->code);
        if (is_null($codeAuth)) {
            return new AuthError(ErrorType::invalid_request);
        } else if (!$codeAuth->isUsedFor(TokenType::authorizationCode)
            || !$codeAuth->isValid()
        ) {
            return new AuthError(ErrorType::invalid_grant);
        }

        $response = new ResponseToken;
        $response->expires_in = TokenType::accessToken->lifetime();

        $authAccess = new self(
            ownerId: $codeAuth->ownerId,
            timestamp: time(),
            scope: $demand->scope,
        );
        $response->access_token = $authAccess->encryptToken();
        $response->token_type = self::TOKEN_TYPE;

        $authRefresh = new self(
            ownerId: $codeAuth->ownerId,
            timestamp: time(),
            usage: TokenType::refreshToken,
            scope: $demand->scope,
        );
        $response->refresh_token = $authRefresh->encryptToken();

        return $response;
    }

    protected static function genTokenUsingClientCred(PostTokenPayload $demand): ResponseAuthz|AuthError
    {
        if ($demand->client_id === '' || $demand->client_secret === '') {
            return new AuthError(ErrorType::invalid_request);
        }

        $sql = <<<SQL
        select `uid` from `client` where `uid` = ? and secret = ?
        SQL;
        $stmt = App::getInstance()->authCnx->prepare($sql) ?: throw new PDOException("DB_ERROR");
        $stmt->execute([$demand->client_id, $demand->client_secret]);
        $clientIsOk = false !== $stmt->fetch(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();
        if (!$clientIsOk) {
            return new AuthError(ErrorType::invalid_client);
        }

        $response = new ResponseToken;
        $response->expires_in = TokenType::accessToken->lifetime();

        $authAccess = new self(
            ownerId: $demand->client_id,
            ownerType: OwnerType::client,
            timestamp: time(),
            scope: $demand->scope,
        );
        $response->access_token = $authAccess->encryptToken();
        $response->token_type = static::TOKEN_TYPE;

        return $response;
    }

    protected static function genTokenUsingPwd(PostTokenPayload $demand): ResponseAuthz|AuthError
    {
        if ($demand->client_id === "" || $demand->username === "" || $demand->password === "") {
            return new AuthError(ErrorType::invalid_request);
        }

        $check = Client::isSameVendor($demand->client_id);
        if (!is_null($check)) {
            return $check;
        }

        $user = UserIdentity::authenticate($demand->username, $demand->password);
        if (is_null($user)) {
            return new AuthError(ErrorType::invalid_grant);
        } else if (!$user->isActive()) {
            return new AuthError(ErrorType::inactive_user);
        }

        $response = new ResponseToken;
        $response->expires_in = TokenType::accessToken->lifetime();

        $authAccess = new self(
            ownerId: $user->username,
            timestamp: time(),
            scope: $demand->scope,
        );
        $response->access_token = $authAccess->encryptToken();
        $response->token_type = static::TOKEN_TYPE;

        $authRefresh = new self(
            ownerId: $user->username,
            timestamp: time(),
            usage: TokenType::refreshToken,
        );
        $response->refresh_token = $authRefresh->encryptToken();

        return $response;
    }

    protected static function genTokenUsingRefresh(PostTokenPayload $demand): ResponseAuthz|AuthError
    {
        if ($demand->refresh_token === '') {
            return new AuthError(ErrorType::invalid_request);
        }

        $authz = Self::decryptToken($demand->refresh_token);
        if (is_null($authz) || !$authz->isUsedFor(TokenType::refreshToken)
            || !$authz->isValid()
        ) {
            return new AuthError(ErrorType::invalid_grant);
        }

        $response = new ResponseToken;
        $response->expires_in = TokenType::accessToken->lifetime();

        $authAccess = new self(
            ownerId: $authz->ownerId,
            ownerType: $authz->ownerType,
            timestamp: time(),
            scope: $authz->scope,
        );
        $response->access_token = $authAccess->encryptToken();
        $response->token_type = static::TOKEN_TYPE;

        return $response;
    }

    public static function genTokenPasswordReset(string $username): string
    {
        $authRenew = new self(
            ownerId: $username,
            timestamp: time(),
            usage: TokenType::passwordResetToken,
        );

        return $authRenew->encryptToken();
    }
}
