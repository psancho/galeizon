<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Conf;

class Debug
{
    public protected(set) bool $logJsonOnError = false;
    public protected(set) bool $traceRequests = false;

    public static function fromObject(?object $raw): self
    {
        $typed = new self;
        if (is_null($raw)) {
            return new self;
        }

        if (property_exists($raw, 'logJsonOnError') && is_bool($raw->logJsonOnError)) {
            $typed->logJsonOnError = $raw->logJsonOnError;
        }
        if (property_exists($raw, 'traceRequests') && is_bool($raw->traceRequests)) {
            $typed->traceRequests = $raw->traceRequests;
        }
        return $typed;
    }
}
