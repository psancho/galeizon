<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Conf;

class Credentials
{
    public protected(set) string $login = '';
    public protected(set) string $password = '';

    public static function fromObject(object $raw): self
    {
        $typed = new self;
        if (property_exists($raw, 'login') && is_string($raw->login)) {
            $typed->login = trim($raw->login);
        }
        if (property_exists($raw, 'password') && is_string($raw->password)) {
            $typed->password = trim($raw->password);
        }
        return $typed;
    }
}
