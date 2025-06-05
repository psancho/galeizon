<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Auth;

interface IdentityProvider
{
    public function isValid(string $tokenAccess): bool;

    public function getUserinfo(string $tokenAccess): ?UserIdentity;
}
