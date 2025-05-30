<?php
declare(strict_types = 1);

namespace Psancho\Galeizon\Model;

class File
{

    /** @see Symfony\Component\Filesystem::isAbsolutePath() */
    public static function isAbsolute(string $path): bool
    {
        return '' !== $path && (strspn($path, '/\\', 0, 1) !== 0
            || (\strlen($path) > 3 && ctype_alpha($path[0])
                && ':' === $path[1]
                && strspn($path, '/\\', 2, 1) !== 0
            )
            || null !== parse_url($path, \PHP_URL_SCHEME)
        );
    }
}
