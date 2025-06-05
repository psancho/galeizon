<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Auth;

class PostPasswordResetPayload
{
    public string $contact = '';
    public string $client_id = '';

    public static function fromObject(object $raw): self
    {
        $typed = new self;
        if (property_exists($raw, 'contact') && is_string($raw->contact)) {
            $typed->contact = $raw->contact;
        } else {
            $typed->contact = '';
        }
        if (property_exists($raw, 'client_id') && is_string($raw->client_id)) {
            $typed->client_id = $raw->client_id;
        } else {
            $typed->client_id = '';
        }

        return $typed;
    }
}
