<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Auth;

use JsonSerializable;
use Psancho\Galeizon\View\StatusCode;

enum ErrorType implements JsonSerializable
{
    /** param manquant ou inattendu */
    case invalid_request;
    /** client non identifié (client cred grant) */
    case invalid_client;
    /** pwd ou code non valide */
    case invalid_grant;
    /** scope invalide */
    case invalid_scope;
    /** le client n'est pas authorisé à utiliser le grant type demandé */
    case unauthorized_client;
    /** le user n'est pas activé */
    case inactive_user;
    /** grant type non reconnu */
    case unsupported_grant_type;

    public function statusCode(): int
    {
        return match ($this) {
            self::invalid_request => StatusCode::HTTP_400_BAD_REQUEST,
            self::invalid_client => StatusCode::HTTP_401_UNAUTHORIZED,
            self::invalid_grant => StatusCode::HTTP_401_UNAUTHORIZED,
            self::invalid_scope => StatusCode::HTTP_403_FORBIDDEN,
            self::unauthorized_client => StatusCode::HTTP_403_FORBIDDEN,
            self::inactive_user => StatusCode::HTTP_403_FORBIDDEN,
            default => StatusCode::HTTP_400_BAD_REQUEST,
        };
    }

    #[\Override]
    public function jsonSerialize(): string
    {
        return $this->name;
    }
}
