<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Conf;

abstract class Entry
{
    public static abstract function fromObject(object $raw, string $path): self;

    public static function getEnv(string $variableName): ?string
    {
        $value = array_key_exists($variableName, $_SERVER)
        ? $_SERVER[$variableName]
        : null;
        return is_scalar($value) ? (string) $value : null;
    }
}
