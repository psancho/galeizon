<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Conf;

use Psancho\Galeizon\View\Format;

class TokenLifetime
{
    public protected(set) int $authorizationCode = 60;
    public protected(set) int $accessToken = 7 * 24 * 3600;
    public protected(set) int $passwordResetToken = 10 * 60;
    public protected(set) int $refreshToken = 6 * 30 * 7 * 24 * 3600;
    public protected(set) int $registrationToken = 10 * 60;

    public static function fromObject(?object $raw): self
    {
        $typed = new self;
        if (is_null($raw)) {
            return $typed;
        }
        self::_getProperty($raw, $typed, 'authorizationCode');
        self::_getProperty($raw, $typed, 'accessToken');
        self::_getProperty($raw, $typed, 'passwordResetToken');
        self::_getProperty($raw, $typed, 'refreshToken');
        self::_getProperty($raw, $typed, 'registrationToken');
        return $typed;
    }

    private static function _getProperty(object $raw, self $typed, string $property): void
    {
        if (property_exists($raw, $property)) {
            if (is_string($raw->$property)) {// @phpstan-ignore property.dynamicName
                $typed->$property = Format::iso8601ToSeconds(trim($raw->$property));// @phpstan-ignore property.dynamicName, property.dynamicName
            } else if (is_int($raw->$property)) {// @phpstan-ignore property.dynamicName
                $typed->$property = $raw->$property;// @phpstan-ignore property.dynamicName, property.dynamicName
            }
        }
    }
}
