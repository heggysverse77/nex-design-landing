<?php
// public/api/config/env.php

class Env {
    private static bool $loaded = false;

    public static function load(): void {
        if (self::$loaded) return;
        self::$loaded = true;

        $paths = [
            __DIR__ . '/../.env',
            __DIR__ . '/../../.env',
            __DIR__ . '/.env',
        ];

        foreach ($paths as $path) {
            if (file_exists($path) && is_readable($path)) {
                self::parse($path);
                break;
            }
        }
    }

    private static function parse(string $filePath): void {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) continue;

            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $val = trim($parts[1]);

                // Strip surrounding quotes
                if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                    (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                    $val = substr($val, 1, -1);
                }

                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }

    public static function get(string $key, $default = null) {
        self::load();
        $val = getenv($key);
        if ($val !== false) return $val;
        if (isset($_ENV[$key])) return $_ENV[$key];
        if (isset($_SERVER[$key])) return $_SERVER[$key];
        return $default;
    }
}

// Auto-load on include
Env::load();
