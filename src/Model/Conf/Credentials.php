<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Conf;

use Psancho\Galeizon\Model\Conf\Entry as ConfEntry;

class Credentials extends ConfEntry
{
    public protected(set) string $login = '';
    public protected(set) string $password = '';

    #[\Override]
    public static function fromObject(object $raw, string $path): self
    {
        $subPath = "{$path}_credentials";
        $typed = new self;
        if (property_exists($raw, 'login') && is_string($raw->login)) {
            $typed->login = trim($raw->login);
        }
        if (!is_null($password = ConfEntry::getEnv("{$subPath}_password"))) {
            $typed->password = $password;
        } else if (property_exists($raw, 'password') && is_string($raw->password)) {
            $typed->password = trim($raw->password);
        }
        return $typed;
    }
}
