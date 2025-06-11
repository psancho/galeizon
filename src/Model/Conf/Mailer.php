<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Conf;

use Psancho\Galeizon\Model\Conf\Entry as ConfEntry;

class Mailer extends ConfEntry
{
    public protected(set) string $dsn = '';

    #[\Override]
    public static function fromObject(object $raw, string $path): self
    {
        $typed = new self;
        if (property_exists($raw, 'dsn') && is_string($raw->dsn)) {
            $typed->dsn = trim($raw->dsn);
        }
        return $typed;
    }
}
