<?php
declare(strict_types = 1);

namespace Psancho\Galeizon\Model\FireAndForget;

use Closure;

class Job
{
    public Closure $closure;// @phpstan-ignore missingType.callable
    /** @var list<mixed> $args */
    public array $args;

    /** @param list<mixed> $args */
    public function __construct(Closure $closure, array $args = [])// @phpstan-ignore missingType.callable
    {
        $this->closure = $closure;
        $this->args = $args;
    }
}
