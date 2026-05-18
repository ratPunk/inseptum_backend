<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Минимальный парсер .env (без зависимостей).
 * Загружается один раз при первом обращении к Env::load().
 *
 * Формат:
 *   KEY=value
 *   KEY="value with spaces"
 *   # коммент
 *
 * Значения попадают в $_ENV и getenv(). НЕ перетирает уже выставленные переменные окружения.
 */
final class Env
{
    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        if (!is_file($path) || !is_readable($path)) {
            return; // .env может отсутствовать — не падаем
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $name  = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));

            if ($value !== '' && (
                ($value[0] === '"' && substr($value, -1) === '"') ||
                ($value[0] === "'" && substr($value, -1) === "'")
            )) {
                $value = substr($value, 1, -1);
            }

            if ($name === '' || array_key_exists($name, $_ENV)) {
                continue;
            }
            $_ENV[$name] = $value;
            putenv($name . '=' . $value);
        }
    }

    /**
     * Получить значение переменной окружения.
     * Приоритет: getenv() → $_ENV → default.
     */
    public static function get(string $key, $default = null)
    {
        $val = getenv($key);
        if ($val !== false && $val !== '') {
            return $val;
        }
        return $_ENV[$key] ?? $default;
    }

    public static function getInt(string $key, int $default = 0): int
    {
        $v = self::get($key, null);
        return $v === null ? $default : (int)$v;
    }

    public static function getFloat(string $key, float $default = 0.0): float
    {
        $v = self::get($key, null);
        return $v === null ? $default : (float)$v;
    }
}
