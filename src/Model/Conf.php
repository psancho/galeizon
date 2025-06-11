<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Model;

use ErrorException;
use Psancho\Galeizon\Model\Conf\Auth as ConfAuth;
use Psancho\Galeizon\Model\Conf\Database as ConfDatabase;
use Psancho\Galeizon\Model\Conf\Debug as ConfDebug;
use Psancho\Galeizon\Model\Conf\Mailer as ConfMailer;
use Psancho\Galeizon\Model\Conf\Monolog as ConfMonolog;
use Psancho\Galeizon\Model\Conf\SelfConf as ConfSelf;
use Psancho\Galeizon\Model\Conf\Slim as ConfSlim;
use Psancho\Galeizon\Pattern\Singleton;
use Psancho\Galeizon\View\Json;

class Conf extends Singleton
{
    public protected(set) ?ConfAuth $auth = null;
    public protected(set) ?ConfDatabase $database = null;
    public protected(set) ConfDebug $debug;// @phpstan-ignore property.uninitialized
    public protected(set) ?ConfMailer $mailer = null;
    public protected(set) ConfMonolog $monolog;// @phpstan-ignore property.uninitialized
    public protected(set) ?ConfSelf $self = null;
    public protected(set) ?ConfSlim $slim = null;
    // TODO config dev spécifique, comprenant entre autres les assertions, self, etc..

    #[\Override]
    protected function build(): void
    {
        $confPath = dirname(__DIR__, 5) . '/config.jsonc';
        if (!file_exists($confPath)) {
            $confPath = dirname(__DIR__, 5) . '/config.json';
        }
        if (!file_exists($confPath)) {
            throw new ConfException("CONF: json file not found", 1);
        }
        try {
            $json = file_get_contents($confPath);
        } catch (ErrorException) {
            throw new ConfException("CONF: unreadable json file", 1);
        }
        assert(is_string($json));
        $raw = Json::unserialize($json);
        if (!is_object($raw)) {
            throw new ConfException("CONF: bad format", 1);
        }
        $this->readConf($raw);
    }

    private const ROOT_PASS = 'galeizon';

    private function readConf(object $raw): void
    {
        if (property_exists($raw, 'auth') && is_object($raw->auth)) {
            $this->auth = ConfAuth::fromObject($raw->auth, self::ROOT_PASS);
        }
        if (property_exists($raw, 'database') && is_object($raw->database)) {
            $this->database = ConfDatabase::fromObject($raw->database, self::ROOT_PASS);
        }
        $this->debug = property_exists($raw, 'debug') && is_object($raw->debug)
            ? ConfDebug::fromObject($raw->debug, self::ROOT_PASS)
            : new ConfDebug
        ;
        if (property_exists($raw, 'mailer') && is_object($raw->mailer)) {
            $this->mailer = ConfMailer::fromObject($raw->mailer, self::ROOT_PASS);
        }
        $this->monolog =
            property_exists($raw, 'monolog') && is_object($raw->monolog)
            ? ConfMonolog::fromObject($raw->monolog, self::ROOT_PASS)
            : new ConfMonolog
        ;
        if (property_exists($raw, 'self') && is_object($raw->self)) {
            $this->self = ConfSelf::fromObject($raw->self, self::ROOT_PASS);
        }
        if (property_exists($raw, 'slim') && is_object($raw->slim)) {
            $this->slim = ConfSlim::fromObject($raw->slim, self::ROOT_PASS);
        }
    }
}
