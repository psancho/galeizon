<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Auth;

class ResponseCode implements ResponseAuthz
{
    public string $code = '';
    public string $state = '';

    #[\Override]
    public function cleanup(): ResponseAuthz
    {
        if ($this->state === '') {
            unset($this->state);// @phpstan-ignore unset.possiblyHookedProperty
        }
        return $this;
    }
}
