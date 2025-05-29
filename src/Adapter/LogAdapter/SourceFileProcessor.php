<?php
declare(strict_types=1);

namespace Psancho\Galeizon\Adapter\LogAdapter;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Psancho\Galeizon\Adapter\LogAdapter;

class SourceFileProcessor implements ProcessorInterface
{
    /** {@inheritDoc} */
    #[\Override]
    public function __invoke(LogRecord $record)
    {
        $file = $this->findFile();
        $record->extra['file_info'] = (
            is_array($file)
            && array_key_exists('file', $file)
            && array_key_exists('line', $file)
        )
        ? (basename($file['file']) . ':' . $file['line']) : '';

        return $record;
    }

    /** @phpstan-return ?array{function: string, line?: int, file?: string, class?: class-string, type?: '->'|'::', args?: array<mixed>, object?: object} */
    public function findFile(): ?array
    {
        /** @__phpstan-var array<int, array{file: string, line: int, function: string, class: class-string}> $debug */
        $debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        array_shift($debug); // ignore findFile
        array_shift($debug); // ignore __invoke

        $i = 0;
        while ($i < sizeof($debug)
            && array_key_exists('class', $debug[$i])
            && $debug[$i]['class'] !== LogAdapter::class
        ) {
            $i++;
        }
        if ($i < sizeof($debug)
            && array_key_exists('file', $debug[$i])
            && in_array(basename($debug[$i]['file']), ['Debug.php', 'Log.php'], true)
        ) {
            $i++;
        }

        return $i < sizeof($debug) ? $debug[$i] : null;
    }
}
