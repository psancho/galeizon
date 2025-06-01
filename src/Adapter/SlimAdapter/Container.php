<?php
declare(strict_types = 1);

namespace Psancho\Galeizon\Adapter\SlimAdapter;

use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    /** @var array<string, mixed> */
    protected array $entries = [];

    /** @return $this */
    public function set(string $id, mixed $entry): self
    {
        $this->entries[$id] = $entry;
        return $this;
    }

    /** @inheritDoc */
    #[\Override]
    public function get(string $id)
    {
        return $this->entries[$id];
    }

    /** @inheritDoc */
    #[\Override]
    public function has(string $id): bool {
        return array_key_exists($id, $this->entries);
    }
}
