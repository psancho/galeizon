<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Auth;

class PostTokenPayload
{
    public ?GrantFlowType $grant_type = null;
    public string $code = '';
    public string $username = '';
    public string $password = '';
    public string $client_id = '';
    public string $client_secret = '';
    public string $refresh_token = '';
    public string $scope = '';

    public static function fromObject(object $raw): self
    {
        $typed = new self;
        if (property_exists($raw, 'grant_type') && is_string($raw->grant_type)
            && !is_null($grantFlow = GrantFlowType::tryFromName($raw->grant_type))
        ) {
            $typed->grant_type = $grantFlow;
        }
        if (property_exists($raw, 'scope') && is_string($raw->scope)) {
            $typed->scope = $raw->scope;
        }
        switch ($typed->grant_type) {
        case GrantFlowType::authorization_code:
            if (property_exists($raw, 'code') && is_string($raw->code)) {
                $typed->code = $raw->code;
            }
            break;

        case GrantFlowType::client_credentials:
            if (property_exists($raw, 'client_id') && is_string($raw->client_id)) {
                $typed->client_id = $raw->client_id;
            }
            if (property_exists($raw, 'client_secret') && is_string($raw->client_secret)) {
                $typed->client_secret = $raw->client_secret;
            }
            break;

        case GrantFlowType::password:
            if (property_exists($raw, 'username') && is_string($raw->username)) {
                $typed->username = $raw->username;
            }
            if (property_exists($raw, 'password') && is_string($raw->password)) {
                $typed->password = $raw->password;
            }
            if (property_exists($raw, 'client_id') && is_string($raw->client_id)) {
                $typed->client_id = $raw->client_id;
            }
            break;

        case GrantFlowType::refresh_token:
            if (property_exists($raw, 'refresh_token') && is_string($raw->refresh_token)) {
                $typed->refresh_token = $raw->refresh_token;
            }
            break;
        }

        return $typed;
    }
}
