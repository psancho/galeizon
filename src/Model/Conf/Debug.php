<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Conf;

use Psancho\Galeizon\Model\Conf\Entry as ConfEntry;

class Debug extends ConfEntry
{
    public protected(set) bool $logJsonOnError = false;
    public protected(set) bool $traceRequests = false;

    #[\Override]
    public static function fromObject(object $raw, string $path): self
    {
        $typed = new self;
        if (property_exists($raw, 'logJsonOnError') && is_bool($raw->logJsonOnError)) {
            $typed->logJsonOnError = $raw->logJsonOnError;
        }
        if (property_exists($raw, 'traceRequests') && is_bool($raw->traceRequests)) {
            $typed->traceRequests = $raw->traceRequests;
        }
        return $typed;
    }
}
