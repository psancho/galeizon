<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Auth;

use Error;

enum OwnerType
{
    case user;
    case client;

    public static function tryFromName(string $name): ?self// @phpstan-ignore return.unusedType
    {
        try {
            $type = self::{$name};
            assert($type instanceof self);
            return $type;
        } catch (Error) {// @phpstan-ignore catch.neverThrown
            return null;
        }
    }
}
