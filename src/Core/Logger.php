<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Simple file logger writing to storage/logs/app.log.
 */
class Logger
{
    private string $file;

    public function __construct(string $file)
    {
        $this->file = $file;
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $line = sprintf(
            "[%s] %s: %s%s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE)
        );
        @file_put_contents($this->file, $line, FILE_APPEND | LOCK_EX);
    }

    public function info(string $message, array $context = []): void    { $this->log('info', $message, $context); }
    public function warning(string $message, array $context = []): void { $this->log('warning', $message, $context); }
    public function error(string $message, array $context = []): void   { $this->log('error', $message, $context); }
}
