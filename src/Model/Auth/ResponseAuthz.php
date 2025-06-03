<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Auth;

interface ResponseAuthz
{
    public function cleanup(): self;
}
