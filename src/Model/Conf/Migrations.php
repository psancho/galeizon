<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model\Conf;

use Psancho\Galeizon\Model\Conf\Entry as ConfEntry;

class Migrations extends ConfEntry
{
    public protected(set) ?Credentials $credentials = null;
    public protected(set) string $directory = "./migrations";
    public protected(set) string $namespace = "Psancho\\Galeizon\\Migrations";
    public protected(set) string $reportEnv = "dev";
    public protected(set) string $reportFrom = "";
    /** @var string[] */
    public protected(set) array $reportTo = [];

    #[\Override]
    public static function fromObject(object $raw, string $path): self
    {
        $subPath = "{$path}_migrations";
        $typed = new self;
        if (property_exists($raw, 'credentials') && is_object($raw->credentials)) {
            $typed->credentials = Credentials::fromObject($raw->credentials, $subPath);
        }
        if (property_exists($raw, 'directory') && is_string($raw->directory)) {
            $typed->directory = trim($raw->directory);
        }
        if (property_exists($raw, 'namespace') && is_string($raw->namespace)) {
            $typed->namespace = trim($raw->namespace);
        }
        if (property_exists($raw, 'reportEnv') && is_string($raw->reportEnv)) {
            $typed->reportEnv = trim($raw->reportEnv);
        }
        if (property_exists($raw, 'reportFrom') && is_string($raw->reportFrom)) {
            $typed->reportFrom = trim($raw->reportFrom);
        }
        if (property_exists($raw, 'reportTo') && is_array($raw->reportTo)) {
            foreach ($raw->reportTo as $reportTo) {
                if (is_string($reportTo) && ($reportTo = trim($reportTo)) !== '') {
                    $typed->reportTo[] = $reportTo;
                }
            }
        }
        return $typed;
    }
}
