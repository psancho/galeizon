<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Auth;

class Error
{
    public function __construct(public ErrorType $error)
    {}
}
