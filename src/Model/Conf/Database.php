<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Conf;

use Psancho\Galeizon\Model\Conf\Entry as ConfEntry;

class Database extends ConfEntry
{
    public protected(set) ?Credentials $credentials = null;
    public protected(set) string $dsn = '';
    public protected(set) ?Migrations $migrations = null;

    #[\Override]
    public static function fromObject(object $raw, string $path): self
    {
        $subPath = "{$path}_database";
        $typed = new self;
        if (property_exists($raw, 'credentials') && is_object($raw->credentials)) {
            $typed->credentials = Credentials::fromObject($raw->credentials, $subPath);
        }
        if (property_exists($raw, 'dsn') && is_string($raw->dsn)) {
            $typed->dsn = trim($raw->dsn);
        }
        if (property_exists($raw, 'migrations') && is_object($raw->migrations)) {
            $typed->migrations = Migrations::fromObject($raw->migrations, $path . $subPath);
        }
        return $typed;
    }
}
