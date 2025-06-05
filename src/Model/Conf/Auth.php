<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Conf;

use Psancho\Galeizon\Model\Conf\Database as ConfDatabase;
use Psancho\Galeizon\Model\Conf\TokenLifetime as ConfTokenLifetime;

class Auth
{
    public protected(set) string $cipherKey = '';
    public protected(set) ?ConfDatabase $database = null;
    public protected(set) string $ihmBaseUrl = 'ihm';
    public protected(set) ?ConfTokenLifetime $lifetime = null;
    public protected(set) string $noreply = '';
    public protected(set) bool $notifyOnRegistration = false;

    public static function fromObject(object $raw): self
    {
        $typed = new self;
        if (property_exists($raw, 'cipherKey') && is_string($raw->cipherKey)) {
            $typed->cipherKey = trim($raw->cipherKey);
        }
        if (property_exists($raw, 'database') && is_object($raw->database)) {
            $typed->database = ConfDatabase::fromObject($raw->database);
        }
        if (property_exists($raw, 'ihmBaseUrl') && is_string($raw->ihmBaseUrl)) {
            $typed->ihmBaseUrl = trim($raw->ihmBaseUrl);
        }
        $typed->lifetime = ConfTokenLifetime::fromObject(
            property_exists($raw, 'lifetime') && is_object($raw->lifetime)
            ? $raw->lifetime
            : null
        );
        if (property_exists($raw, 'noreply') && is_string($raw->noreply)) {
            $typed->noreply = trim($raw->noreply);
        }
        if (property_exists($raw, 'notifyOnRegistration') && is_bool($raw->notifyOnRegistration)) {
            $typed->notifyOnRegistration = $raw->notifyOnRegistration;
        }
        return $typed;
    }
}
