<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Conf;

class SelfConf
{
    public protected(set) string $baseUrl = '';
    public protected(set) string $clientId = '';
    public protected(set) string $clientSecret = '';
    public protected(set) string $username = "";
    public protected(set) string $password = "";

    public static function fromObject(object $raw): self
    {
        $typed = new self;
        if (property_exists($raw, 'baseUrl') && is_string($raw->baseUrl)) {
            $typed->baseUrl = trim($raw->baseUrl);
        }
        if (property_exists($raw, 'clientId') && is_string($raw->clientId)) {
            $typed->clientId = trim($raw->clientId);
        }
        if (property_exists($raw, 'clientSecret') && is_string($raw->clientSecret)) {
            $typed->clientSecret = trim($raw->clientSecret);
        }
        if (property_exists($raw, 'username') && is_string($raw->username)) {
            $typed->username = trim($raw->username);
        }
        if (property_exists($raw, 'password') && is_string($raw->password)) {
            $typed->password = trim($raw->password);
        }
        return $typed;
    }
}
