<?php

require_once '../aurox.php';

use OsdAurox\Js;
use osd84\BrutalTestRunner\BrutalTestRunner;
use OsdAurox\Sec;

$tester = new BrutalTestRunner();
$tester->header(__FILE__);


// init
if(!defined('APP_ROOT')) {
    define('APP_ROOT', realpath(__DIR__));
}

$tester->header('Test  de JS::consoleLog()');
$tester->header("Test de la méthode Js::consoleLog");

$tester->header("Test de la méthode Js::consoleLog");

// Test 1 : Vérification qu'un message simple est affiché correctement dans la console
ob_start();
Js::consoleLog("Test message simple");
$output = ob_get_clean();
$tester->assertEqual(
    $output,
    '<script>console.log("Test message simple");</script>',
    "La méthode consoleLog affiche correctement un message simple dans la console"
);

// Test 2 : Gestion des balises HTML dans le message
ob_start();
Js::consoleLog("Message avec <b>balises</b>");
$output = ob_get_clean();
$tester->assertEqual(
    $output,
    '<script>console.log("Message avec balises");</script>',
    "La méthode consoleLog supprime correctement les balises HTML dans le message"
);

// Test 3 : Gestion d'un message vide
ob_start();
Js::consoleLog("");
$output = ob_get_clean();
$tester->assertEqual(
    $output,
    '<script>console.log("");</script>',
    "La méthode consoleLog gère correctement un message vide"
);

// Test 4 : Gestion d'un message non encodable
ob_start();
Js::consoleLog("\xB1\x31");
$output = ob_get_clean();
$tester->assertEqual(
    $output,
    '<script>console.log("");</script>',
    "La méthode consoleLog gère correctement un message non encodable"
);

// Test 2 : Gestion des balises HTML dans le message
ob_start();
Js::consoleLog("Message avec <b>balises</b>");
$output = ob_get_clean();
$tester->assertEqual(
    $output,
    '<script>console.log("Message avec balises");</script>',
    "La méthode consoleLog supprime correctement les balises HTML dans le message"
);

// Test 3 : Gestion d'un message vide
ob_start();
Js::consoleLog("");
$output = ob_get_clean();
$tester->assertEqual(
    $output,
    '<script>console.log("");</script>',
    "La méthode consoleLog gère correctement un message vide"
);

// Test 4 : Gestion d'un message non encodable
ob_start();
Js::consoleLog("\xB1\x31");
$output = ob_get_clean();
$tester->assertEqual(
    $output,
    '<script>console.log("");</script>',
    "La méthode consoleLog gère correctement un message non encodable"
);

// Test 5 : Tableau
ob_start();
Js::consoleLog(['foo' => 'bar']);
$output = ob_get_clean();
$tester->assertEqual(
    $output,
    '<script>console.log("{array}");</script>',
    "Array bloqué si safe=false"
);

ob_start();
Js::consoleLog(['foo' => 'bar'], safe: True);
$output = ob_get_clean();
$tester->assertEqual(
    $output,
    '<script>console.log({"foo":"bar"});</script>',
    "Array bloqué si safe=false"
);


$tester->footer(exit: false);