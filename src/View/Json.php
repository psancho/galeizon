<?php
declare(strict_types = 1);

namespace Psancho\Galeizon\View;

use JsonException;

class Json
{
    public const string MIMETYPE = 'application/json';

    private const string CONST_PREFIX = 'JSON_ERROR_';

    /** @throws JsonException */
    public static function unserialize(string $json): mixed
    {
        return json_decode($json, false, flags: JSON_THROW_ON_ERROR);
    }

    /** désérialise la chaîne si elle correspond à du json, sinon retourne la chaine telle quelle */
    public static function tryUnserialize(string $str): mixed
    {
        $obj = json_decode($str, false);
        $err = json_last_error();
        if (JSON_ERROR_NONE === $err) {
            return $obj;
        } else {
            return $str;
        }
    }

    /** @throws JsonException */
    public static function serialize(mixed $data): string
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR) ?: '';

        return $json;
    }

    /**
     * Récupère le nom de l'erreur
     *
     * @param int $error
     *
     * @return string
     */
    public static function errorMessage(int $error): string
    {
        static $prefix = 'JSON_ERROR_';
        $constantName = Format::getConstantName(self::CONST_PREFIX, $error);
        $msg = is_null($constantName) ? 'UNKNOWN' : substr($constantName, strlen(self::CONST_PREFIX));

        return $msg;
    }

    /** Supprime les propriétés ayant la valeur nulle */
    public static function cleanupProperties(object $object): object
    {
        foreach ((array) $object as $key => $value) {
            if (is_null($value)) {
                unset($object->$key);// @phpstan-ignore property.dynamicName
            }
        }
        return $object;
    }

    /**
     * Supprime les propriétés listées
     *
     * @param list<string> $properties
     */
    public static function removeProperties(object $object, array $properties): object
    {
        foreach ((array) $object as $key => $value) {
            if (in_array($key, $properties, true)) {
                unset($object->$key);// @phpstan-ignore property.dynamicName
            }
        }
        return $object;
    }

    /**
     * Supprime les propriétés non listées
     *
     * @param array<string> $properties
     */
    public static function keepProperties(object $object, array $properties): object
    {
        foreach ((array) $object as $key => $value) {
            if (!in_array($key, $properties, true)) {
                unset($object->$key);// @phpstan-ignore property.dynamicName
            }
        }
        return $object;
    }
}
