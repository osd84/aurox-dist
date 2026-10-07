<?php

namespace OsdAurox;

use function set_error_handler;
use function set_exception_handler;
use function register_shutdown_function;

class ErrorMonitoring
{
    /** @var array<string,bool> */
    private static array $reported = [];
    private static bool $sending = false;

    public static function initialize(): void
    {
        set_error_handler([self::class, 'handlePhpError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleFatalOnShutdown']);
    }

    public static function handlePhpError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        self::handleUnified([
            'type'    => $errno,
            'message' => $errstr,
            'file'    => $errfile,
            'line'    => $errline,
            'origin'  => 'php_error',
        ]);
        // true = on stoppe le handler par défaut de PHP (pas de double sortie)
        return true;
    }

    public static function handleException(\Throwable $e): void
    {
        self::handleUnified([
            'type'    => E_ERROR, // on normalise
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'origin'  => 'exception',
            'class'   => get_class($e),
            'trace'   => $e->getTraceAsString(),
        ]);
    }

    public static function handleFatalOnShutdown(): void
    {
        $last = error_get_last();
        if ($last && in_array($last['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
            self::handleUnified([
                'type'    => $last['type'],
                'message' => $last['message'],
                'file'    => $last['file'],
                'line'    => $last['line'],
                'origin'  => 'shutdown_fatal',
            ]);
        }
    }


    private static function handleUnified(array $error): void
    {
        // Déduplication basique par hash (type|file|line|message)
        $key = md5(sprintf('%s|%s|%s|%s',
            (string)($error['type'] ?? 'x'),
            (string)($error['file'] ?? 'x'),
            (string)($error['line'] ?? 'x'),
            (string)($error['message'] ?? 'x')
        ));
        if (isset(self::$reported[$key])) {
            return;
        }
        self::$reported[$key] = true;

        // Contexte HTTP
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'unknown-host';
        $uri    = $_SERVER['REQUEST_URI'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $url    = $host !== 'unknown-host' ? ($scheme . '://' . $host . $uri) : 'CLI';
        $ua     = $_SERVER['HTTP_USER_AGENT'] ?? 'n/a';
        $ref    = $_SERVER['HTTP_REFERER'] ?? 'n/a';

        // Mapping type lisible
        $typeName = self::errorTypeToString((int)($error['type'] ?? 0));

        // Construction du message
        $lines = [
            "🚨 **Erreur détectée**",
            "Type: {$typeName}" . (!empty($error['origin']) ? " ({$error['origin']})" : ''),
            "Fichier: " . ($error['file'] ?? 'n/a'),
            "Ligne: " . (string)($error['line'] ?? 'n/a'),
            "Message: " . (string)($error['message'] ?? 'n/a'),
            "IP: {$ip}",
            "Méthode: {$method}",
            "URL: {$url}",
            "User-Agent: {$ua}",
            "Referer: {$ref}",
            "APP:" . AppConfig::get('appName', 'unknown') . " (" . AppConfig::get('appUrl', 'unknown') . ")",
        ];

        if (!empty($error['class'])) {
            $lines[] = "Exception: " . $error['class'];
        }
        if (!empty($error['trace'])) {
            $lines[] = "Trace:\n```" . $error['trace'] . "```";
        }

        $message = implode("\n", $lines);

        // Protection anti-boucle si Discord::send() plante
        if (self::$sending) {
            error_log('[ErrorMonitoring] Prevented recursive send: ' . $message);
            return;
        }

        try {
            self::$sending = true;
            Discord::send($message);
        } catch (\Throwable $sendErr) {
            error_log('[ErrorMonitoring] Discord send failed: ' . $sendErr->getMessage());
        } finally {
            self::$sending = false;
        }
    }

    private static function errorTypeToString(int $type): string
    {
        return match ($type) {
            E_ERROR             => 'E_ERROR',
            E_WARNING           => 'E_WARNING',
            E_PARSE             => 'E_PARSE',
            E_NOTICE            => 'E_NOTICE',
            E_CORE_ERROR        => 'E_CORE_ERROR',
            E_CORE_WARNING      => 'E_CORE_WARNING',
            E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
            E_USER_ERROR        => 'E_USER_ERROR',
            E_USER_WARNING      => 'E_USER_WARNING',
            E_USER_NOTICE       => 'E_USER_NOTICE',
            E_STRICT            => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED        => 'E_DEPRECATED',
            E_USER_DEPRECATED   => 'E_USER_DEPRECATED',
            default             => 'E_UNKNOWN(' . $type . ')',
        };
    }
}
