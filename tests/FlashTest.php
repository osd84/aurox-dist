<?php

require_once '../aurox.php';

use OsdAurox\Flash;
use osd84\BrutalTestRunner\BrutalTestRunner;


$tester = new BrutalTestRunner();
$tester->header(__FILE__);

// Initialisation de la session pour les tests
$_SESSION['messages'] = [];

// Test de la méthode add (ajout de différents types de messages)
Flash::add('success', 'Tout s\'est bien passé !');
$tester->assertEqual(['success' => ['Tout s\'est bien passé !']], $_SESSION['messages'], "add ajoute un message flash correctement au tableau");

Flash::add('error', 'Quelque chose s\'est mal passé.');
Flash::add('warning', 'Attention, vérifiez vos données.');
$expectedMessages = [
    'success' => ['Tout s\'est bien passé !'],
    'error' => ['Quelque chose s\'est mal passé.'],
    'warning' => ['Attention, vérifiez vos données.']
];
$tester->assertEqual($expectedMessages, $_SESSION['messages'], "add fonctionne correctement avec plusieurs types de messages");

// Test de la méthode get (récupération sans suppression)
$retrievedMessages = Flash::get();
$tester->assertEqual($expectedMessages, $retrievedMessages, "get retourne correctement tous les messages sans nettoyer");

// Test de la méthode get avec suppression
$retrievedMessagesAfterClear = Flash::get(true);
$tester->assertEqual($expectedMessages, $retrievedMessagesAfterClear, "get retourne correctement les messages et les supprime si clear=true");
$tester->assertEqual([], $_SESSION['messages'], "get vide correctement les messages dans la session");

// Test des méthodes spécifiques (error, success, info, warning)
Flash::error('Erreur critique !');
Flash::success('Succès !');
Flash::info('Information générale.');
Flash::warning('Attention !');

$expectedSpecificMessages = [
    'danger' => ['Erreur critique !'],
    'success' => ['Succès !'],
    'info' => ['Information générale.'],
    'warning' => ['Attention !']
];
$tester->assertEqual($expectedSpecificMessages, Flash::get(), "Les méthodes error, success, info et warning ajoutent correctement les messages au bon type dans la session");


// get and clear
$tester->assertEqual($expectedSpecificMessages, Flash::get(clear: true), "on a bien récupérer les messages");
$tester->assertEqual([], Flash::get(), "get et clear vide correctement la session");

$tester->footer(exit: false);