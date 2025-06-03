<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Conf;

use Psancho\Galeizon\Model\Conf\Database as ConfDatabase;
use Psancho\Galeizon\Model\Conf\TokenLifetime as ConfTokenLifetime;

class Auth
{
    public protected(set) string $cipherKey = '';
    public protected(set) ?ConfDatabase $database = null;
    public protected(set) ?ConfTokenLifetime $lifetime = null;

    public static function fromObject(object $raw): self
    {
        $typed = new self;
        if (property_exists($raw, 'cipherKey') && is_string($raw->cipherKey)) {
            $typed->cipherKey = trim($raw->cipherKey);
        }
        if (property_exists($raw, 'database') && is_object($raw->database)) {
            $typed->database = ConfDatabase::fromObject($raw->database);
        }
        $typed->lifetime = ConfTokenLifetime::fromObject(
            property_exists($raw, 'lifetime') && is_object($raw->lifetime)
            ? $raw->lifetime
            : null
        );
        return $typed;
    }
}
