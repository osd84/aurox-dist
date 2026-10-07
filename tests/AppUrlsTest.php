<?php

require_once '../aurox.php';

use App\AppUrls;
use osd84\BrutalTestRunner\BrutalTestRunner;

$tester = new BrutalTestRunner();
$tester->header(__FILE__);

$tester->assertEqual( AppUrls::HOME, '/', 'home est ok');
$tester->assertEqual( AppUrls::LOGIN, '/auth/login.php', 'login est ok');


$tester->footer(exit: false);