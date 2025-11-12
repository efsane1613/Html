<?php

namespace App\Support;

class Logger
{
    private string $logFile;

    public function __construct(string $logFile)
    {
        $this->logFile = $logFile;
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    private function write(string $level, string $message, array $context): void
    {
        $timestamp = (new \DateTimeImmutable())->format(DATE_ATOM);
        $payload = [
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];

        $line = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($line === false) {
            return;
        }

        file_put_contents($this->logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
