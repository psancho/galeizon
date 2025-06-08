<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Auth;

use Error;
use JsonSerializable;

enum OwnerType implements JsonSerializable
{
    case user;
    case client;
    case any;

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

    public function contains(OwnerType $owner): bool
    {
        return $this === self::any || $this === $owner;
    }

    #[\Override]
    public function jsonSerialize(): string
    {
        return $this->name;
    }
}
