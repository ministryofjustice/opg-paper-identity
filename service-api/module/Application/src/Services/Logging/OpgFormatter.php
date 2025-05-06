<?php

declare(strict_types=1);

namespace Application\Services\Logging;

use Laminas\Http\Request as HttpRequest;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\LogRecord;

class OpgFormatter extends NormalizerFormatter
{
    private ?HttpRequest $request = null;

    public function setRequest(?HttpRequest $request): void
    {
        $this->request = $request;
    }

    /**
     * {@inheritDoc}
     */
    public function format(LogRecord $record): string
    {
        $original = parent::format($record);

        $record = [
            'time' => $original['datetime'],
            'level' => $original['level_name'],
            'msg' => $original['message'],
            'service_name' => $original['channel'],
        ];

        if (isset($original['context']['trace_id'])) {
            $record['trace_id'] = $original['context']['trace_id'];
            unset($original['context']['trace_id']);
        }

        if ($this->request !== null) {
            $record['request'] = [
                'method' => $this->request->getMethod(),
                'path' => $this->request->getUri()->getPath(),
            ];
        }

        unset($original['datetime']);
        unset($original['level_name']);
        unset($original['message']);
        unset($original['channel']);

        return $this->toJson(array_filter($record + $original)) . "\n";
    }
}
