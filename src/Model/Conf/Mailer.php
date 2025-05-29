<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Conf;

class Mailer
{
    public protected(set) string $dsn = '';

    public static function fromObject(object $raw): self
    {
        $typed = new self;
        if (property_exists($raw, 'dsn') && is_string($raw->dsn)) {
            $typed->dsn = trim($raw->dsn);
        }
        return $typed;
    }
}
