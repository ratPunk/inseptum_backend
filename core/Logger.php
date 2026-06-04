<?php

declare(strict_types=1);

namespace App\Core;

class Logger
{
    private static ?Logger $instance = null;
    private string $logPath;
    private array $channels = ['auth', 'articles', 'api', 'errors'];

    private function __construct(string $logPath = null)
    {
        $this->logPath = $logPath ?? dirname(__DIR__) . '/storage/logs';
        $this->ensureDirectoryExists();
    }

    public static function getInstance(string $logPath = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($logPath);
        }
        return self::$instance;
    }

    private function ensureDirectoryExists(): void
    {
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    private function write(string $channel, string $level, string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        
        $logEntry = "[{$timestamp}] [{$level}] [{$channel}] [IP:{$ip}] [UA:{$userAgent}] {$message}{$contextStr}\n";
        
        $filename = $this->logPath . '/' . $channel . '.log';
        file_put_contents($filename, $logEntry, FILE_APPEND);
        
        // Also write to combined log
        $combinedFile = $this->logPath . '/combined.log';
        file_put_contents($combinedFile, $logEntry, FILE_APPEND);
    }

    public function auth(string $message, array $context = []): void
    {
        $this->write('auth', 'AUTH', $message, $context);
    }

    public function article(string $message, array $context = []): void
    {
        $this->write('articles', 'ARTICLE', $message, $context);
    }

    public function api(string $message, array $context = []): void
    {
        $this->write('api', 'API', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('errors', 'ERROR', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('api', 'INFO', $message, $context);
    }

    public function logRequest(string $method, string $uri, array $params = []): void
    {
        $context = [
            'method' => $method,
            'uri' => $uri,
            'params' => $params,
            'session' => session_id() ?: 'none'
        ];
        
        $this->api("HTTP {$method} {$uri}", $context);
    }
}