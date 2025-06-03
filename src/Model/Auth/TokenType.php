<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Auth;

use Psancho\Galeizon\Model\Conf;
use RuntimeException;

// TokenType::from(1) => TokenType::authorizationCode
// TokenType::tryFrom(12) => null
// TokenType::cases() => [TokenType::authorizationCode, ...]
enum TokenType: int
{
    case authorizationCode = 1;
    case accessToken = 2;
    case passwordResetToken = 3;
    case refreshToken = 4;
    case registrationToken = 5;

    public function lifetime(): int
    {
        $conf = Conf::getInstance()->auth?->lifetime;
        if (is_null($conf)) {
            throw new RuntimeException("Conf: auth not set", 1);
        }
        return match ($this) {
            self::authorizationCode => $conf->authorizationCode,
            self::accessToken => $conf->accessToken,
            self::passwordResetToken => $conf->passwordResetToken,
            self::refreshToken => $conf->refreshToken,
            default => $conf->registrationToken,
        };
    }
}
