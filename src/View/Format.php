<?php
declare(strict_types = 1);

namespace Psancho\Galeizon\View;

use DateInterval;
use DateTimeImmutable;
use Exception;
use LogicException;
use ReflectionClass;
use RuntimeException;

class Format
{
    /** réduit l'affichage de la taille en utilisant les unités B, KB, MB, GB, TB ou PB */
    public static final function bytes(int $size, int $precision = 2): string
    {
        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        $base = log($size, 1024);
        $floor = (int) min(floor($base), count($suffixes) - 1);

        assert(array_key_exists($floor, $suffixes));

        return round(pow(1024, $base - $floor), $precision) .' '. $suffixes[$floor];
    }

    /**
     * Transforme la chaîneCamelCase en chaîne_snake
     *
     * @see https://stackoverflow.com/questions/1993721/how-to-convert-camelcase-to-camel-case
     */
    public static function camel2snake(string $camel): string
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $camel, $matches);
        $snake = $matches[0];
        foreach ($snake as &$match) {
            $match = $match === strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $snake);
    }

    /** @throws RuntimeException */
    public static function iso8601ToSeconds(string $intervalSpec, bool $future = false): int
    {
        $diff = self::_interval2Diff($intervalSpec, $future);
        return (int) $diff->format("%a") * 24 * 60 * 60 + $diff->h * 60 * 60 + $diff->i * 60 + $diff->s;
    }

    /** @throws RuntimeException */
    public static function iso8601ToDays(string $intervalSpec, bool $future = false): int
    {
        return (int) self::_interval2Diff($intervalSpec, $future)->format("%a");
    }

    /** @throws RuntimeException */
    private static function _interval2Diff(string $intervalSpec, bool $future = false): DateInterval
    {
        $now = new DateTimeImmutable;
        try {
            $interval = new DateInterval($intervalSpec);
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        $diff = $future ? $now->add($interval)->diff($now) : $now->sub($interval)->diff($now);

        return $diff;
    }

    /**
     * @param scalar $value
     *
     * @throws LogicException
     */
    public static function getConstantName(string $prefix, $value): ?string
    {
        /** @phpstan-var list<string> $duplicateKeys */
        static $duplicateKeys = [
            'CURLE_OPERATION_TIMEOUTED', // duplicates CURLE_OPERATION_TIMEDOUT
            'CURLE_FTP_WEIRD_SERVER_REPLY', // duplicates CURLE_WEIRD_SERVER_REPLY
            'CURLE_FTP_PARTIAL_FILE', // duplicates CURLE_PARTIAL_FILE
            'CURLE_HTTP_RETURNED_ERROR', // duplicates CURLE_HTTP_NOT_FOUND
            'CURLE_FTP_BAD_DOWNLOAD_RESUME', // duplicates CURLE_BAD_DOWNLOAD_RESUME
            'CURLE_SSL_PEER_CERTIFICATE', // duplicates CURLE_SSL_CACERT
        ];
        /* @phpstan-var array<string, mixed> $constants */
        $constants = get_defined_constants();
        return self::_getAnyConstantName($constants, $prefix, $value, $duplicateKeys);
    }

    /**
     * @phpstan-param class-string $class
     * @param         mixed        $value
     * @phpstan-param list<string> $skippedKeys
     *
     * @throws LogicException
     */
    public static function getClassConstantName(string $class, string $prefix, $value, array $skippedKeys = []): ?string
    {
        /* @phpstan-var array<string, mixed> $classConstants */
        $classConstants = (new ReflectionClass($class))->getConstants();
        return self::_getAnyConstantName($classConstants, $prefix, $value, $skippedKeys);
    }

    /**
     * @phpstan-param array<string, mixed> $constants
     * @param         mixed                $value
     * @phpstan-param list<string>         $skippedKeys
     *
     * @throws LogicException
     */
    private static function _getAnyConstantName(array $constants, string $prefix, $value, array $skippedKeys = []): ?string
    {
        $const = array_filter($constants, function ($v, string $k) use ($prefix, $value, $skippedKeys) {
            return ($prefix === '' || strncmp($k, $prefix, strlen($prefix)) === 0)
                && $v === $value
                && !in_array($k, $skippedKeys, true);
        }, ARRAY_FILTER_USE_BOTH);
        $count = count($const);
        if ($count > 1) {
            throw new LogicException("Constant prefix too short");
        } else if ($count === 1) {
            reset($const);
            return (string) key($const);
        } else {
            return null;
        }
    }
}
