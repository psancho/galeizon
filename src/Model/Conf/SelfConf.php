<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Conf;

use Psancho\Galeizon\Model\Conf\Entry as ConfEntry;

class SelfConf extends ConfEntry
{
    public protected(set) string $baseUrl = '';
    public protected(set) string $clientId = '';
    /** sensible */
    public protected(set) string $clientSecret = '';
    /** sensible */
    public protected(set) string $password = "";
    public protected(set) string $redirectUri = "";
    public protected(set) string $username = "";

    #[\Override]
    public static function fromObject(object $raw, string $path): self
    {
        $subPath = "{$path}_self";
        $typed = new self;
        if (property_exists($raw, 'baseUrl') && is_string($raw->baseUrl)) {
            $typed->baseUrl = trim($raw->baseUrl);
        }
        if (property_exists($raw, 'clientId') && is_string($raw->clientId)) {
            $typed->clientId = trim($raw->clientId);
        }
        if (!is_null($clientSecret = ConfEntry::getEnv("{$subPath}_clientSecret"))) {
            $typed->clientSecret = $clientSecret;
        } else if (property_exists($raw, 'clientSecret') && is_string($raw->clientSecret)) {
            $typed->clientSecret = trim($raw->clientSecret);
        }
        if (!is_null($password = ConfEntry::getEnv("{$subPath}_password"))) {
            $typed->password = $password;
        } else if (property_exists($raw, 'password') && is_string($raw->password)) {
            $typed->password = trim($raw->password);
        }
        if (property_exists($raw, 'redirectUri') && is_string($raw->redirectUri)) {
            $typed->redirectUri = trim($raw->redirectUri);
        }
        if (property_exists($raw, 'username') && is_string($raw->username)) {
            $typed->username = trim($raw->username);
        }
        return $typed;
    }
}
