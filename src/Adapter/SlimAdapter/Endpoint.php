<?php
declare(strict_types = 1);

namespace Psancho\Galeizon\Adapter\SlimAdapter;

use Attribute;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
class Endpoint
{
    /** @param list<string> $middlewares */
    public function __construct(
        protected string $verb,
        protected string $path,
        protected bool $secure = true,
        protected ?string $authz = null,
        protected array $middlewares = [],
    )
    {}
}
