<?php
declare(strict_types = 1);

namespace Psancho\Galeizon\Model\Database;

use PDO;
use Psancho\Galeizon\Model\Conf\Database as ConfDatabase;

class Connection
{
    /** @var array<string,PDO> */
    private static $_pdo = [];

    /** @param array<int, scalar> $options */
    public static function getInstance(?ConfDatabase $conf = null, array $options = []): PDO
    {
        if (is_null($conf)) {
            // utiliser le dns par défaut
            if (count(self::$_pdo) === 0) {
                throw new ConnectionException('No_DB_connexion');
            } else {
                return reset(self::$_pdo);
            }
        } else if (!array_key_exists($conf->dsn, self::$_pdo)) {
            self::push($conf, $options);
        }

        return self::$_pdo[$conf->dsn];
    }

    /** @param array<int, scalar> $options */
    public static function push(ConfDatabase $conf, array $options = []): void
    {
        $dsn = $conf->dsn;
        if (array_key_exists($dsn, self::$_pdo)) {
            return;
        } else if (is_null($conf->credentials)) {
            throw new ConnectionException("No_DB_credentials");

        }

        /** @var array<int> $driverOptionList */
        static $driverOptionList = [
            PDO::MYSQL_ATTR_LOCAL_INFILE,
            PDO::MYSQL_ATTR_INIT_COMMAND,
            PDO::MYSQL_ATTR_MULTI_STATEMENTS,
        ];

        /** @var array<int, scalar> $driverOptions */
        $driverOptions = [
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8',
        ];
        /** @var array<int, scalar> $otherOptions */
        $otherOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::MYSQL_ATTR_DIRECT_QUERY => false,
            // DEBUG le cas des int retournés string
            //       Cf. https://bugs.php.net/bug.php?id=44341 comment du 21/11/2013
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        foreach ($options as $option => $value) {
            if (in_array($option, $driverOptionList, true)) {
                $driverOptions[$option] = $value;
            } else {
                $otherOptions[$option] = $value;
            }
        }

        self::$_pdo[$dsn] = new PDO($dsn, $conf->credentials->login, $conf->credentials->password, $driverOptions);

        foreach ($otherOptions as $option => $value) {
            self::$_pdo[$dsn]->setAttribute($option, $value);
        }
    }

    public static function schemaNameFromDsn(string $dsn): string
    {
        $matches = [];
        preg_match('/dbname=[^;]*/', $dsn, $matches);
        if (count($matches) === 0) {
            return '';
        }
        $field = explode('=', $matches[0]);
        return $field[1];
    }

    /**
     * cast un mysql.BIT en int
     *
     * sous Linux, on obtient un octet sous forme de chaîne
     */
    public static function castBitToInt(string|int $value): int
    {
        return is_string($value) ? ord($value) : $value;
    }

    /** @access private */
    final private function __construct()
    {
    }
}
