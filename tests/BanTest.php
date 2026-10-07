<?php

require_once '../aurox.php';


use OsdAurox\Ban;
use osd84\BrutalTestRunner\BrutalTestRunner;

$tester = new BrutalTestRunner();
$tester->header(__FILE__);

$_SERVER['REMOTE_ADDR'] = '192.168.54.4'; // mock ip address

// init
if (!defined('APP_ROOT')) {
    define('APP_ROOT', realpath(__DIR__));
}

// l'ip n'est pas bannie
$r = Ban::blockBlackListed(output: true);
$tester->assertEqual(false, $r, 'ip est bannie par black list');

// on check l'url
$_SERVER['REQUEST_URI'] = '/safe_url';
$r = Ban::checkRequest(output: true);
$tester->assertEqual(false, $r, 'ip non bannie par url');

// ban by request
$_SERVER['REQUEST_URI'] = '/.idea';
$r = Ban::checkRequest(output: true);
$tester->assertEqual(true, $r, 'ip bannie par url match');

// is block because in black list
$r = Ban::blockBlackListed(output: true);
$tester->assertEqual(true, $r, 'ip bannie car dans black list');

// unban ip
$r = Ban::unBan($_SERVER['REMOTE_ADDR']);
$tester->assertEqual(true, $r, 'unban ok');

// l'ip n'est plus bannie
$r = Ban::blockBlackListed(output: true);
$tester->assertEqual(false, $r, 'ip non bannie');

// ban by $_GET ?url
$_SERVER['REQUEST_URI'] = '/404.php';
$_GET['url'] = '/.idea';
$r = Ban::checkRequest(output: true);
$tester->assertEqual(true, $r, 'ip bannie via ?url=');

// unban ip
$r = Ban::unBan($_SERVER['REMOTE_ADDR']);
$tester->assertEqual(true, $r, 'unban ok');

// ban direct
$r = Ban::ban($_SERVER['REMOTE_ADDR']);
$tester->assertEqual(true, $r, 'ban ok');

// is block because in black list
$r = Ban::blockBlackListed(output: true);
$tester->assertEqual(true, $r, 'bannie var blacklist');

// unban ip
$r = Ban::unBan($_SERVER['REMOTE_ADDR']);
$tester->assertEqual(true, $r, 'unban ok');


// ban par detection d'injection dans $_POST ou $_GET
$_POST['test'] = 'test';
$_GET['name'] = 'username';
$r = Ban::banIfHackAttempt();
$tester->assertEqual(false, $r, 'pas de tentative');

// ban par XSS sur POST
$_POST['test'] = "<script>alert('123')</script>";
unset($_GET['name']);
$r = Ban::banIfHackAttempt();
$tester->assertEqual(true, $r, 'block XSS');

// check if ban & unban
$r = Ban::blockBlackListed(output: true);
$tester->assertEqual(true, $r, 'ip dans black list');
$r = Ban::unBan($_SERVER['REMOTE_ADDR']);
$tester->assertEqual(true, $r, 'unban ok');

// ban par SQLI en GET
unset($_POST['test']);
$_GET['name'] = "'; DROP TABLE users; --";
$r = Ban::banIfHackAttempt();
$tester->assertEqual(true, $r, 'block sqli');

// check if ban & unban
$r = Ban::blockBlackListed(output: true);
$tester->assertEqual(true, $r, 'bannie par black list');
$r = Ban::unBan($_SERVER['REMOTE_ADDR']);
$tester->assertEqual(true, $r, 'unban ok');



$tester->footer(exit: false);