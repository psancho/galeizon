<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Conf;

use Psancho\Galeizon\Model\Conf\Entry as ConfEntry;

class App extends ConfEntry
{
    public protected(set) string $userDecorator = '';

    #[\Override]
    public static function fromObject(object $raw, string $path): self
    {
        $typed = new self;
        if (property_exists($raw, 'userDecorator') && is_string($raw->userDecorator)) {
            $typed->userDecorator = $raw->userDecorator;
        }
        return $typed;
    }
}
