<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Conf;

class Database
{
    public protected(set) string $dsn = '';
    public protected(set) ?Credentials $crud = null;
    public protected(set) ?Credentials $admin = null;

    public static function fromObject(object $raw): self
    {
        $typed = new self;
        if (property_exists($raw, 'dsn') && is_string($raw->dsn)) {
            $typed->dsn = trim($raw->dsn);
        }
        if (property_exists($raw, 'crud') && is_object($raw->crud)) {
            $typed->crud = Credentials::fromObject($raw->crud);
        }
        if (property_exists($raw, 'admin') && is_object($raw->admin)) {
            $typed->admin = Credentials::fromObject($raw->admin);
        }
        return $typed;
    }
}
