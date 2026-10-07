<?php

require_once '../aurox.php';

use OsdAurox\Csrf;
use osd84\BrutalTestRunner\BrutalTestRunner;

$tester = new BrutalTestRunner();
$tester->header(__FILE__);


// init
if(!defined('APP_ROOT')) {
    define('APP_ROOT', realpath(__DIR__));
}

$_SERVER['REQUEST_METHOD'] = 'POST'; // mock

$_SESSION['csrf'] = Csrf::generate();
$_POST['_csrf'] = $_SESSION['csrf'];
$csrf = Csrf::protect();
$tester->assertEqual(strlen($csrf) > 50, true, 'Csrf n1, CSRF token par session');
$tester->assertEqual($csrf, $_SESSION['csrf'] ?? null, 'Csrf dans Session');

$_SERVER['REQUEST_METHOD'] = 'POST'; // mock
$result = Csrf::verify($csrf);
$tester->assertEqual($result, true, 'Csrf true');

// seconde requête OK Csrf par session
$_SERVER['REQUEST_METHOD'] = 'POST'; // mock
$csrf = Csrf::protect();
$result = Csrf::verify($csrf);
$tester->assertEqual($result, true, 'Csrf true n2, ok nouvelle requete mais token par session');

// troisieme requête NOK Csrf par request
$_SERVER['REQUEST_METHOD'] = 'POST'; // mock
$csrf = Csrf::protect(forcePerRequest: true); // Le Token change
$_POST['_csrf'] = $csrf;
$result = Csrf::verify($csrf);
$tester->assertEqual($result, true, 'Csrf true n3, ok nouveau token par requete');

// Si on refait une requête avec l'ancien token on est refusé car le token change à chaque requête
$csrf = Csrf::protect(forcePerRequest: true); // Le Token change
$result = Csrf::verify($csrf); // Celui stocké dans $_POST['_csrf'] est toujours l'ancien, cela doit bloquer
$tester->assertEqual($result, false, 'Csrf echec n4, mauvais token par requête');


$_SERVER['REQUEST_METHOD'] = 'POST'; // mock
$_SESSION['csrf'] = Csrf::generate();
$_POST['_csrf'] = $_SESSION['csrf'];// mock
$success = Csrf::verify($_SESSION['csrf']);
$tester->assertEqual($success, true, 'Csrf ok');

$csrf_html = Csrf::inputHtml();
$tester->assertEqual(str_contains($csrf_html, $_SESSION['csrf']), true, 'Csrf input html ok');
$tester->assertEqual(str_contains($csrf_html, 'name="_csrf"'), true, 'Csrf input html ok');

$csrf_brut = Csrf::getToken();
$tester->assertEqual($csrf_brut,  $_SESSION['csrf'], 'Csrf brut ok');

$tester->footer(exit: false);