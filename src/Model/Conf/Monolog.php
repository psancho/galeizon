<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Conf;

class Monolog
{
    public protected(set) string $level = 'debug';
    public protected(set) string $systems = 'stdout';
    public protected(set) int $maxFiles = 1;

    public static function fromObject(?object $raw): self
    {
        $typed = new self;
        if (is_null($raw)) {
            return new self;
        }

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
