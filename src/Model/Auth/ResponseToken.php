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
}
