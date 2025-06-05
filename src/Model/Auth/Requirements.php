<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Auth;

class Requirements
{
    public mixed $user = null;
    public ?string $scope = null;

    public function forScope(string $scope): self
    {
        $this->scope = $scope;
        return $this;
    }

    public function forUser(mixed $user): self
    {
        $this->user = $user;
        return $this;
    }
}
