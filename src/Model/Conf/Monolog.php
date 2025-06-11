<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Conf;

use Psancho\Galeizon\Model\Conf\Entry as ConfEntry;

class Monolog extends ConfEntry
{
    public protected(set) string $level = 'debug';
    public protected(set) string $systems = 'stdout';
    public protected(set) int $maxFiles = 1;

    #[\Override]
    public static function fromObject(object $raw, string $path): self
    {
        $typed = new self;
        if (property_exists($raw, 'level') && is_string($raw->level)) {
            $typed->level = trim($raw->level);
        }
        if (property_exists($raw, 'systems') && is_string($raw->systems)) {
            $typed->systems = trim($raw->systems);
        }
        if (property_exists($raw, 'maxFiles') && is_numeric($raw->maxFiles)) {
            $typed->maxFiles = (int) $raw->maxFiles;
        }
        return $typed;
    }
}
