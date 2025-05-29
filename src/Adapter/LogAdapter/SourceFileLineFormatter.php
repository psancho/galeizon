<?php declare(strict_types=1);

namespace Psancho\Galeizon\Adapter\LogAdapter;

use Monolog\Formatter\LineFormatter;
use Monolog\LogRecord;

class SourceFileLineFormatter extends LineFormatter
{
    /** @inheritDoc */
    #[\Override]
    public function format(LogRecord $record): string
    {
        $hasFileInfo = array_key_exists('file_info', $record->extra) && is_string($record->extra['file_info']);

        $fileInfo = '';
        if ($hasFileInfo) {
            $fileInfo = $record->extra['file_info'];
            unset($record->extra['file_info']);
        }
        assert(is_string($fileInfo));

        $output = parent::format($record);

        if ($hasFileInfo) {
            $record->extra['file_info'] = $fileInfo;
        }

        $output = str_replace('%file_info%', $fileInfo, $output);

        return $output;
    }
}
