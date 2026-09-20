<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Lightweight Environment File Loader
 * Reads .env files and populates getenv(), $_ENV, and $_SERVER.
 */
class Env
{
    private static array $variables = [];

    public static function load(string $filePath): void
    {
        if (!file_exists($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Parse key=value
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $value = trim($parts[1]);

            // Strip surrounding quotes if present
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            // Convert boolean strings
            $lower = strtolower($value);
            if ($lower === 'true') {
                $value = true;
            } elseif ($lower === 'false') {
                $value = false;
            } elseif ($lower === 'null') {
                $value = null;
            }

            self::$variables[$key] = $value;
            putenv("{$key}=" . (is_bool($value) ? ($value ? 'true' : 'false') : (string)$value));
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$variables)) {
            return self::$variables[$key];
        }

        $val = getenv($key);
        if ($val !== false) {
            return $val;
        }

        return $default;
    }
}
