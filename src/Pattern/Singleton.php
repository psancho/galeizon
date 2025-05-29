<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Pattern;

use LogicException;

abstract class Singleton
{
    public static function getInstance(): static
    {
        /** @var list<static> */
        static $instances = [];

        if (!array_key_exists(static::class, $instances)) {
            $instances[static::class] = new static();
        }

        return $instances[static::class];
    }

    final protected function __construct() {
        static::build();
    }

    /** @throws LogicException */
    public final function __clone()
    {
        throw new LogicException("Singleton: cloning is prohibited");
    }

    /** @throws LogicException */
    public final function __wakeup()
    {
        throw new LogicException("Singleton: unserializing is prohibited");
    }

    protected abstract function build(): void;
}
