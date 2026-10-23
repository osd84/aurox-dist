<?php

namespace OsdAurox;

class Log
{
    public static ?Log $logger_instance = null;
    public string $path = '';
    public string $level = '';
    private function __construct()
    {
        // sigleton
    }

    private function __clone()
    {
        // sigleton
    }

    private function init($path = '/logs/app.log', $level='warning')
    {
        $logger = new Log();
        $logger->path = APP_ROOT . $path;
        $logger->level = $level;
        $logger->enableDevError();
        return $logger;
    }
    public static function getInstance($path = '/logs/app.log', $level='warning')
    {
        if (!self::$logger_instance) {
            $o_logger = new self();
            self::$logger_instance = $o_logger->init(path : $path, level : $level);
        }

        return self::$logger_instance;
    }


    /**
     * DEV uniquement (debug = true) : toute erreur PHP arrete la page sur l'ecran
     * d'erreur d'aurox, et part sur Discord comme en prod (ErrorMonitoring).
     *
     * En prod (debug = false) rien n'est pose ici : c'est ErrorMonitoring::initialize()
     * dans aurox.php qui s'en charge, comme avant.
     */
    public function enableDevError(): void
    {
        if(AppConfig::isDebug()) {
            set_error_handler([self::class, 'devErrorHandler']);
            set_exception_handler([self::class, 'devExceptionHandler']);
            register_shutdown_function([self::class, 'devShutdownHandler']);
        }
    }

    /**
     * DEV : warning, notice, deprecated...
     *
     * On n'en fait plus une ErrorException : une exception s'attrape, et un
     * try / catch (\Throwable) l'avalait (au mieux un Flash, la page continuait).
     * On s'arrete directement sur l'ecran d'erreur, aucun catch ne l'intercepte.
     *
     * Un @ devant l'expression est respecte : error_reporting() ne contient alors
     * plus l'erreur, on rend la main a PHP qui ne dit rien.
     */
    public static function devErrorHandler(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        // Discord, comme en prod : utile pour les appels ajax, dont on ne voit pas l'ecran
        ErrorMonitoring::handlePhpError($severity, $message, $file, $line);

        self::devStop(new \ErrorException($message, 0, $severity, $file, $line));
    }

    /**
     * DEV : exception non attrapee.
     */
    public static function devExceptionHandler(\Throwable $e): never
    {
        ErrorMonitoring::handleException($e);

        self::devStop($e);
    }

    /**
     * DEV : erreur fatale (E_ERROR, E_PARSE...), vue a l'arret du script.
     */
    public static function devShutdownHandler(): void
    {
        $e = error_get_last();
        if ($e === null || !($e['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR))) {
            return;
        }

        ErrorMonitoring::handleFatalOnShutdown();

        while (ob_get_level()) {
            ob_end_clean();
        }

        if (!headers_sent()) {
            http_response_code(500);
        }

        // RESET HTML BRUTAL - ferme tout ce qui traîne et redémarre propre
        echo '</select></textarea></table></div></span></body></html></script>';

        // Nouvelle page HTML propre
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        echo '<style>*{margin:0;padding:0}body{background:#000;color:#0f0;font:14px monospace}</style>';
        echo '</head><body>';
        echo "<pre style='padding:20px;white-space:pre-wrap;word-wrap:break-word;background:#000;color:#f00;overflow:auto;max-height:90vh;max-width:90vw;'>";
        echo "💥 SHUTDOWN FATAL ERROR\n\n";
        echo htmlspecialchars(print_r($e, true));
        echo "\n\n";
        echo "OSD Aurox™ Framework -- DEBUGGER\n\n";
        echo "</pre></body></html>";

        // Log CLI
        $red = "\033[31m";
        $reset = "\033[0m";
        error_log($red . '================= ERROR =================' . $reset);
        error_log($red . 'SHUTDOWN FATAL ERROR' . $reset);
        error_log($red . '=>>> ' . print_r($e, true) . $reset);
        error_log($red . '=========================================' . $reset);

        exit(1);
    }

    /**
     * DEV : ecran d'erreur puis arret de l'execution.
     */
    private static function devStop(\Throwable $e): never
    {
        // Vide tous les buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Force le reset des headers
        if (!headers_sent()) {
            header_remove();
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
            header('Connection: close');
        }

        // RESET HTML BRUTAL - ferme tout ce qui traîne et redémarre propre
        echo '</select></textarea></table></div></span></body></html></script>';

        // Nouvelle page HTML propre
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        echo '<style>*{margin:0;padding:0}body{background:#000;color:#0f0;font:14px monospace}</style>';
        echo '</head><body>';
        echo "<pre style='padding:20px;white-space:pre-wrap;word-wrap:break-word;background:#000;color:#f00;overflow:auto;max-height:90vh;max-width:90vw;'>";
        echo "💥 FATAL ERROR - EXECUTION STOPPED\n\n";
        echo htmlspecialchars(get_class($e)) . ": " . htmlspecialchars($e->getMessage()) . "\n";
        echo htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "\n\n";
        echo htmlspecialchars($e->getTraceAsString());
        echo "\n\n";
        echo "OSD Aurox™ Framework -- DEBUGGER\n\n";
        echo "</pre></body></html>";

        // Log CLI
        $red = "\033[31m";
        $reset = "\033[0m";
        error_log($red . '================= ERROR =================' . $reset);
        error_log($red . 'FATAL ERROR - EXECUTION STOPPED' . $reset);
        error_log($red . '=>>> ' . get_class($e) . ': ' . $e->getMessage() . $reset);
        error_log($red . '=>>> ' . $e->getFile() . ':' . $e->getLine() . $reset);
        error_log($red . '=========================================' . $reset);

        exit(1);
    }

    private function writeLog($message, $type)
    {
        // open file
        $date = date('Y-m-d H:i:s');
        $message = "$date : $type : $message" . PHP_EOL;
        if(!is_dir(dirname($this->path))){
            mkdir(dirname($this->path), 0777, true);
        }
        file_put_contents($this->path, $message, FILE_APPEND);
    }

    public static function debug(string $message): void
    {
        $instance = self::getInstance();
        $instance->writeLog($message, 'debug');
    }

    public static function info(string $message): void
    {
        $instance = self::getInstance();
        $instance->writeLog($message, 'info');
    }

    public static function warning(string $message): void
    {
        $instance = self::getInstance();
        $instance->writeLog($message, 'warning');
    }

    public static function error(string $message): void
    {
        $instance = self::getInstance();
        $instance->writeLog($message, 'error');
    }


}