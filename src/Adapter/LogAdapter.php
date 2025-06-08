<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Adapter;

use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\ProcessorInterface;
use Psancho\Galeizon\Adapter\LogAdapter\SourceFileLineFormatter;
use Psancho\Galeizon\Adapter\LogAdapter\SourceFileProcessor;
use Psancho\Galeizon\Model\Conf;
use Psancho\Galeizon\Model\Conf\Monolog as ConfMonolog;
use Psancho\Galeizon\Pattern\Singleton;
use Stringable;

/**
 * BUG: phpstan 1.12.6 ne voit pas l'implémentation Stringable pour les exceptions, donc => mixed
 *
 * @method static void emergency(mixed|string|Stringable $message, array<string, mixed> $context = array())
 * @method static void alert(mixed|string|Stringable $message, array<string, mixed> $context = array())
 * @method static void critical(mixed|string|Stringable $message, array<string, mixed> $context = array())
 * @method static void error(mixed|string|Stringable $message, array<string, mixed> $context = array())
 * @method static void warning(mixed|string|Stringable $message, array<string, mixed> $context = array())
 * @method static void notice(mixed|string|Stringable $message, array<string, mixed> $context = array())
 * @method static void info(mixed|string|Stringable $message, array<string, mixed> $context = array())
 * @method static void debug(mixed|string|Stringable $message, array<string, mixed> $context = array())
 * @method static void log(mixed $level, mixed|string|Stringable $message, array<string, mixed> $context = array())
 */
class LogAdapter extends Singleton
{

    /** @var string */
    protected $logDirPath;
    /** @var Logger */
    protected $logger;
    /** @var FormatterInterface */
    protected $formatter;
    /** @var list<ProcessorInterface> */
    protected $processors = [];
    /** @var list<string> */
    protected $systems = [];
    protected Level $logLevel;// @phpstan-ignore property.uninitialized

    protected const string SYSTEM_FILES = 'files';
    protected const string SYSTEM_STDOUT = 'stdout';
    protected const array SYSTEMS = [self::SYSTEM_FILES, self::SYSTEM_STDOUT];

    #[\Override]
    protected function build(): void
    {
        $conf = Conf::getInstance()->monolog;
        $this->logLevel = self::_toMonologLevel($conf->level, Level::Debug);
        $this->systems = self::_parseSystemsConf($conf->systems);

        $this->setProcessors()->setFormatter();

        $this->logger = new Logger('psancho');
        foreach ($this->processors as $processor) {
            $this->logger->pushProcessor($processor);
        }

        if (in_array(self::SYSTEM_FILES, $this->systems, true)) {
            $fileHandler = new RotatingFileHandler(
                dirname(__DIR__, 5) . '/logs/log',
                maxFiles: $conf->maxFiles,
                level: $this->logLevel,
                filePermission: 0777,
            );
            $fileHandler->setFormatter($this->formatter);
            $this->logger->pushHandler($fileHandler);
        }

        if (in_array(self::SYSTEM_STDOUT, $this->systems, true)) {
            $stdoutHandler = new StreamHandler("php://stdout", $this->logLevel);

            $stdoutHandler->setFormatter($this->formatter);
            $this->logger->pushHandler($stdoutHandler);
        }
    }

    private static function _toMonologLevel(mixed $level, Level $default): Level
    {
        if ($level instanceof Level) {
            return $level;
        } else if (is_string($level) && in_array(strtoupper($level), Level::NAMES, true)) {
            return Level::fromName($level);// @phpstan-ignore argument.type
        } else if (is_int($level) &&  in_array($level, Level::VALUES, true)) {
            /** @var value-of<Level::VALUES> $level */
            return Level::fromValue($level);
        }
        return $default;
    }

    /** @return list<string> */
    private static function _parseSystemsConf(string $confSystems): array
    {
        $systemList = strlen($confSystems) > 0 ? array_map('trim', explode(',', $confSystems)) : [];
        return array_values(array_intersect($systemList, self::SYSTEMS));
    }

    /** @return $this */
    protected function setFormatter(): self
    {
        $this->formatter = self::_setFormatter(
            "Y-m-d H:i:s",
            "[%datetime%] %level_name%: [%file_info%] %message% %context% %extra%\n"
        );

        return $this;
    }

    private static function _setFormatter(string $dateFormat, string $format): FormatterInterface
    {
        $formatter = new SourceFileLineFormatter($format, $dateFormat, true, true);
        $formatter->includeStacktraces();
        $formatter->setJsonPrettyPrint(true);

        return $formatter;
    }

    /** @return $this */
    protected function setProcessors(): self
    {
        $this->processors[] = new SourceFileProcessor;

        return $this;
    }

    /** @return $this */
    public function pushHandler(AbstractProcessingHandler $handler): self
    {
        $handler->setFormatter($this->formatter);
        $handler->setLevel($this->logLevel);

        $this->logger->pushHandler($handler);

        return $this;
    }

    /** @param array<int|string, mixed> $args */
    public static function __callstatic(string $method, $args): void
    {
        static::getInstance()->logger->$method(...$args);// @phpstan-ignore method.dynamicName
    }
}
