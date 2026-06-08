<?php

declare(strict_types=1);

namespace App\Core;

/**
 * PSR-3 inspired logger with:
 *  - Log levels: DEBUG, INFO, WARNING, ERROR, CRITICAL
 *  - Per-channel files + combined.log
 *  - Stack traces for ERROR / CRITICAL
 *  - Global PHP error / exception / shutdown handlers
 *  - Context interpolation and pretty JSON context
 */
class Logger
{
    // ── Log levels ────────────────────────────────────────────────────────────
    public const DEBUG    = 'DEBUG';
    public const INFO     = 'INFO';
    public const WARNING  = 'WARNING';
    public const ERROR    = 'ERROR';
    public const CRITICAL = 'CRITICAL';

    private const LEVEL_WEIGHT = [
        self::DEBUG    => 0,
        self::INFO     => 1,
        self::WARNING  => 2,
        self::ERROR    => 3,
        self::CRITICAL => 4,
    ];

    // ── Singleton ─────────────────────────────────────────────────────────────
    private static ?Logger $instance = null;

    private string $logPath;
    private string $minLevel;
    private bool   $handlersRegistered = false;

    private function __construct(string $logPath, string $minLevel = self::DEBUG)
    {
        $this->logPath  = rtrim($logPath, '/\\');
        $this->minLevel = $minLevel;
        $this->ensureDirectory($this->logPath);
    }

    public static function getInstance(string $logPath = null, string $minLevel = self::DEBUG): self
    {
        if (self::$instance === null) {
            $path = $logPath ?? dirname(__DIR__) . '/storage/logs';
            self::$instance = new self($path, $minLevel);
        }
        return self::$instance;
    }

    // ── Public channel shortcuts ──────────────────────────────────────────────

    public function debug(string $message, array $context = []): void
    {
        $this->write('api', self::DEBUG, $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('api', self::INFO, $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->write('api', self::WARNING, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('errors', self::ERROR, $message, $context, true);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->write('errors', self::CRITICAL, $message, $context, true);
    }

    public function auth(string $message, array $context = []): void
    {
        $this->write('auth', self::INFO, $message, $context);
    }

    public function article(string $message, array $context = []): void
    {
        $this->write('articles', self::INFO, $message, $context);
    }

    public function api(string $message, array $context = []): void
    {
        $this->write('api', self::INFO, $message, $context);
    }

    public function logRequest(string $method, string $uri, array $params = []): void
    {
        $this->write('api', self::INFO, "HTTP {$method} {$uri}", [
            'method'  => $method,
            'uri'     => $uri,
            'params'  => $params,
        ]);
    }

    /**
     * Log an exception with full stack trace.
     */
    public function exception(\Throwable $e, array $context = [], string $channel = 'errors'): void
    {
        $context = array_merge($context, [
            'exception'  => get_class($e),
            'message'    => $e->getMessage(),
            'code'       => $e->getCode(),
            'file'       => $e->getFile(),
            'line'       => $e->getLine(),
            'trace'      => $this->formatTrace($e->getTrace()),
        ]);

        if ($e->getPrevious()) {
            $prev = $e->getPrevious();
            $context['previous'] = [
                'exception' => get_class($prev),
                'message'   => $prev->getMessage(),
                'file'      => $prev->getFile(),
                'line'      => $prev->getLine(),
            ];
        }

        $this->write($channel, self::CRITICAL, get_class($e) . ': ' . $e->getMessage(), $context, false);
    }

    // ── Global handler registration ───────────────────────────────────────────

    /**
     * Register PHP error / exception / shutdown handlers.
     * Call once after Logger::getInstance() in index.php.
     */
    public function registerHandlers(): void
    {
        if ($this->handlersRegistered) {
            return;
        }
        $this->handlersRegistered = true;

        // Uncaught exceptions
        set_exception_handler(function (\Throwable $e): void {
            $this->exception($e);
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(500);
            }
            echo json_encode(['error' => 'Internal server error'], JSON_UNESCAPED_UNICODE);
            exit(1);
        });

        // PHP errors → logger (still honours error_reporting)
        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
            if (!(error_reporting() & $errno)) {
                return false; // respect @ operator
            }

            $level = match (true) {
                in_array($errno, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)      => self::CRITICAL,
                in_array($errno, [E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING], true)       => self::WARNING,
                in_array($errno, [E_NOTICE, E_USER_NOTICE, E_DEPRECATED, E_USER_DEPRECATED], true)          => self::INFO,
                default                                                                                       => self::WARNING,
            };

            $this->write('errors', $level, "[PHP:{$errno}] {$errstr}", [
                'file'  => $errfile,
                'line'  => $errline,
                'trace' => $this->formatTrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10)),
            ]);

            return true; // don't execute PHP internal error handler
        });

        // Fatal errors (E_ERROR etc. are not caught by set_error_handler)
        register_shutdown_function(function (): void {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                $this->write('errors', self::CRITICAL, '[FATAL] ' . $error['message'], [
                    'file' => $error['file'],
                    'line' => $error['line'],
                ]);
                if (!headers_sent()) {
                    header('Content-Type: application/json; charset=utf-8');
                    http_response_code(500);
                }
                echo json_encode(['error' => 'Internal server error'], JSON_UNESCAPED_UNICODE);
            }
        });
    }

    // ── Core write ────────────────────────────────────────────────────────────

    private function write(
        string $channel,
        string $level,
        string $message,
        array  $context = [],
        bool   $appendTrace = false
    ): void {
        // Honour minimum level
        if ((self::LEVEL_WEIGHT[$level] ?? 0) < (self::LEVEL_WEIGHT[$this->minLevel] ?? 0)) {
            return;
        }

        if ($appendTrace && empty($context['trace'])) {
            $context['trace'] = $this->formatTrace(
                debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15)
            );
        }

        $timestamp = date('Y-m-d H:i:s');
        $ip        = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        $method    = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $uri       = $_SERVER['REQUEST_URI']    ?? '';
        $levelPad  = str_pad($level, 8);          // align columns

        $contextStr = '';
        if (!empty($context)) {
            $contextStr = "\n    " . str_replace("\n", "\n    ", json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        $entry = "[{$timestamp}] [{$levelPad}] [{$channel}] [{$method} {$uri}] [IP:{$ip}] {$message}{$contextStr}\n";

        $this->ensureDirectory($this->logPath);
        $this->append($this->logPath . '/' . $channel . '.log', $entry);
        $this->append($this->logPath . '/combined.log',          $entry);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function append(string $file, string $entry): void
    {
        file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Convert a debug_backtrace() / getTrace() array to a compact string list.
     */
    private function formatTrace(array $trace): array
    {
        $lines = [];
        foreach (array_slice($trace, 0, 20) as $i => $frame) {
            $file     = isset($frame['file']) ? basename($frame['file']) : '(internal)';
            $line     = $frame['line'] ?? '?';
            $class    = isset($frame['class']) ? $frame['class'] . ($frame['type'] ?? '::') : '';
            $function = $frame['function'] ?? '(closure)';
            $lines[]  = "#{$i} {$file}:{$line} {$class}{$function}()";
        }
        return $lines;
    }
}
