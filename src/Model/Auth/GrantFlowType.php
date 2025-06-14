<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Auth;

use Error;
use JsonSerializable;

enum GrantFlowType implements JsonSerializable
{
    case implicit;
    case authorization_code;
    case password;
    case client_credentials;
    case refresh_token;

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

    #[\Override]
    public function jsonSerialize(): string
    {
        return $this->name;
    }
}
