<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model;

use ErrorException;
use Psancho\Galeizon\Model\Conf\Database;
use Psancho\Galeizon\Model\Conf\Monolog;
use Psancho\Galeizon\Pattern\Singleton;

class Conf extends Singleton
{
    public protected(set) ?Database $database = null;
    public protected(set) ?Monolog $monolog = null;

    #[\Override]
    protected function build(): void
    {
        $confPath = dirname(__DIR__, 5) . '/config.json';
        if (!file_exists($confPath)) {
            throw new ConfException("CONF: json file not found", 1);
        }
        try {
            $json = file_get_contents($confPath);
        } catch (ErrorException $e) {
            throw new ConfException("CONF: unreadable json file", 1);
        }
        assert(is_string($json));
        $raw = json_decode($json, flags: JSON_THROW_ON_ERROR);
        if (!is_object($raw)) {
            throw new ConfException("CONF: bad format", 1);
        }
        $this->readConf($raw);
    }

    private function readConf(object $raw): void
    {
        if (property_exists($raw, 'auth') && is_object($raw->auth)) {}
        if (property_exists($raw, 'database') && is_object($raw->database)) {
            $this->database = Database::fromObject($raw->database);
        }
        if (property_exists($raw, 'mailer') && is_object($raw->mailer)) {}
        $this->monolog = Monolog::fromObject(
            property_exists($raw, 'monolog') && is_object($raw->monolog)
            ? $raw->monolog
            : null
        );
        if (property_exists($raw, 'self') && is_object($raw->self)) {}
        if (property_exists($raw, 'slim') && is_object($raw->slim)) {}
    }
}
