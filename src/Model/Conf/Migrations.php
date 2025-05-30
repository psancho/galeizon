<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Conf;

class Migrations
{
    public protected(set) ?Credentials $credentials = null;
    public protected(set) string $directory = "./migrations";
    public protected(set) string $namespace = "Psancho\\Galeizon\\Migrations";

    public static function fromObject(object $raw): self
    {
        $typed = new self;
        if (property_exists($raw, 'directory') && is_string($raw->directory)) {
            $typed->directory = trim($raw->directory);
        }
        if (property_exists($raw, 'namespace') && is_string($raw->namespace)) {
            $typed->namespace = trim($raw->namespace);
        }
        if (property_exists($raw, 'credentials') && is_object($raw->credentials)) {
            $typed->credentials = Credentials::fromObject($raw->credentials);
        }
        return $typed;
    }
}
