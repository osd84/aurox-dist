<?php

require_once '../aurox.php';

use OsdAurox\Log;
use osd84\BrutalTestRunner\BrutalTestRunner;

$tester = new BrutalTestRunner();
$tester->header(__FILE__);

// Chemin des fichiers de log utilisé pour les tests

// Initialisation de l'objet Log
$log = Log::getInstance();
$logFile = $log->path;
// Nettoyage du fichier de log avant les tests
if (file_exists($logFile)) {
    unlink($logFile);
}

$log->info('Test message INFO');
$logContent = file_get_contents($logFile);
$tester->assertEqual(
    str_contains($logContent, 'info : Test message INFO'),
    true,
    "info() écrit correctement un message avec le niveau 'INFO'"
);

$log->error('Test message ERROR');
$logContent = file_get_contents($logFile);
$tester->assertEqual(
    str_contains($logContent, 'error : Test message ERROR'),
    true,
    "error() écrit correctement un message avec le niveau 'ERROR'"
);

$log->debug('Test message DEBUG');
$logContent = file_get_contents($logFile);
$tester->assertEqual(
    str_contains($logContent, 'debug : Test message DEBUG'),
    true,
    "debug() écrit correctement un message avec un niveau custom 'DEBUG'"
);

// Nettoyage après les tests
//unlink($logFile);

$tester->footer(exit: false);