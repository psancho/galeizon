<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Auth;

use Psancho\Galeizon\View\Json;

class AuthorizationRegistration extends Authorization
{
    public function __construct(
        public Registration $registration,
        string $ownerId = '',
        OwnerType $ownerType = OwnerType::user,
        int $timestamp = 0,
        TokenType $usage = TokenType::accessToken,
        string $scope = '',
    )
    {
        parent::__construct($ownerId, $ownerType, $timestamp, $usage, $scope);
    }

    #[\Override]
    public static function fromJson(string $json): static
    {
        $authz = parent::fromJson($json);
        $args = Json::tryUnserialize($json);
        if (is_array($args)) {
            $args = (object) $args;
        }
        if (is_object($args)) {
            if (property_exists($args, 'registration') && is_object($args->registration)) {
                $authz->registration = Registration::fromObject($args->registration);
            }
        }
        return $authz;
    }

    public static function genTokenRegistration(Registration $registration): string
    {
        $authRenew = new self(
            registration: $registration,
            timestamp: time(),
            usage: TokenType::registrationToken,
        );

        return $authRenew->encryptToken();
    }
}
