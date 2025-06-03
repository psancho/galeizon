<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Auth;

use InvalidArgumentException;
use Psancho\Galeizon\Adapter\LogAdapter;
use Psancho\Galeizon\Model\Conf;
use Psancho\Galeizon\Model\ConfException;
use Tuupola\Base62Proxy as Base62;

class Token
{
    /** @var array{cipher: string, options: int} */
    protected static $sslConfig = [
        'cipher' => 'AES-256-CBC',
        'options' => \OPENSSL_RAW_DATA,
    ];

    /**
     * @throws ConfException
     * @throws InvalidArgumentException
     */
    public static function decrypt(string $token): ?string
    {
        $keyRaw = static::key2raw(self::getCipherKey());

        try {
            $tokenRaw = Base62::decode($token);
        } catch (InvalidArgumentException $e) {
            LogAdapter::notice($e, ['token' => $token]);
            return null;
        }
        $ivLen = (int) openssl_cipher_iv_length(self::$sslConfig['cipher']);
        $iv = substr($tokenRaw, 0, $ivLen) ?: '';
        $crypted = substr($tokenRaw, $ivLen);

        $decrypted = openssl_decrypt($crypted, self::$sslConfig['cipher'], $keyRaw, self::$sslConfig['options'], $iv);

        if ($decrypted !== false) {
            return $decrypted;
        } else {
            return null;
        }
    }

    /** @throws ConfException */
    protected static function getCipherKey(): string
    {
        if (is_null(Conf::getInstance()->auth?->cipherKey)) {
            throw new ConfException("auth.cipherKey not set", 1);
        }
        return Conf::getInstance()->auth->cipherKey;
    }

    /** @throws InvalidArgumentException */
    public static function encrypt(string $data): string
    {
        $keyRaw = static::key2raw(self::getCipherKey());

        $ivLen = (int) openssl_cipher_iv_length(self::$sslConfig['cipher']);
        $iv = openssl_random_pseudo_bytes($ivLen);

        $crypted = openssl_encrypt($data, self::$sslConfig['cipher'], $keyRaw, self::$sslConfig['options'], $iv);

        $tokenRaw = $iv . $crypted;
        $token = Base62::encode($tokenRaw);

        return $token;
    }

    /**
     * extrait la clé de la chaîne base62 et assure que sa longueur fait 256 bits (soit 32 octets)
     *
     * @throws InvalidArgumentException
     */
    protected static function key2raw(string $key): string
    {
        $keyRaw = Base62::decode($key);
        if (strlen($keyRaw) < 32) {
            throw new InvalidArgumentException("Cypher key too short.");
        }

        return (substr($keyRaw, 0, 32));
    }
}
