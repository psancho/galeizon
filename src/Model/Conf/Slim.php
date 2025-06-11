<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Conf;

use Psancho\Galeizon\Model\Conf\Entry as ConfEntry;

class Slim extends ConfEntry
{
    public protected(set) string $basepath = '';
    /** @var list<string> */
    public protected(set) array $blackList = [];
    public protected(set) string $timeout = '';
    /** @var list<string> */
    public protected(set) array $whiteList = [];

    #[\Override]
    public static function fromObject(object $raw, string $path): self
    {
        $typed = new self;
        if (property_exists($raw, 'basepath') && is_string($raw->basepath)) {
            $typed->basepath = trim($raw->basepath);
        }
        if (property_exists($raw, 'blackList') && is_array($raw->blackList)) {
            foreach ($raw->blackList as $item) {
                if (is_string($item)) {
                    $item = trim($item);
                    if (strlen($item) > 0) {
                        $typed->blackList[] = $item;
                    }
                }
            }
        }
        if (property_exists($raw, 'timeout') && is_string($raw->timeout)) {
            $typed->timeout = trim($raw->timeout);
        }
        if (property_exists($raw, 'whiteList') && is_array($raw->whiteList)) {
            foreach ($raw->whiteList as $item) {
                if (is_string($item)) {
                    $item = trim($item);
                    if (strlen($item) > 0) {
                        $typed->whiteList[] = $item;
                    }
                }
            }
        }
        return $typed;
    }
}
