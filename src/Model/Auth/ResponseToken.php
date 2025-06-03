<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Auth;

class ResponseToken implements ResponseAuthz
{
    public string $access_token = "";
    public string $token_type = "";
    public int $expires_in = 0;
    public string $refresh_token = "";
    public string $state = "";

    #[\Override]
    public function cleanup(): ResponseAuthz
    {
        if ($this->expires_in === 0) {
            unset($this->expires_in);// @phpstan-ignore unset.possiblyHookedProperty
        }
        if ($this->refresh_token === "") {
            unset($this->refresh_token);// @phpstan-ignore unset.possiblyHookedProperty
        }
        if ($this->state === "") {
            unset($this->state);// @phpstan-ignore unset.possiblyHookedProperty
        }
        return $this;
    }
}
