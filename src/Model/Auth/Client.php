<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Auth;

use PDO;
use PDOException;
use Psancho\Galeizon\App;
use Psancho\Galeizon\Model\Auth\Error as AuthError;

class Client
{
    public static function isSameVendor(string $clientId): ?AuthError
    {

        $sql = <<<SQL
        select `uid`, same_vendor from `client` where `uid` = ?
        SQL;
        $stmt = App::getInstance()->authCnx->prepare($sql) ?: throw new PDOException("DB_ERROR");
        $stmt->execute([$clientId]);
        /** @var false|array{uid: string, same_vendor: int} */
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($client === false) {
            return new AuthError(ErrorType::invalid_client);
        } else if ($client['same_vendor'] === 0) {
            return new AuthError(ErrorType::unauthorized_client);
        } else {
            return null;
        }
    }

    public static function isRegisteredRedirectUri(string $clientId, string $redirectUri): bool
    {
        $sql = <<<SQL
        select R.uri
        from redirect R
        join client C on C.id = R.clientId
        where C.uid = ? and R.uri = ?
        SQL;
        $stmt = App::getInstance()->authCnx->prepare($sql) ?: throw new PDOException("DB_ERROR");
        $stmt->execute([$clientId, $redirectUri]);
        $registeredUri = $stmt->fetch(PDO::FETCH_COLUMN, 0);
        $stmt->closeCursor();

        return $registeredUri !== false;
    }
}
