<?php

require_once '../aurox.php';


use OsdAurox\AppConfig;
use osd84\BrutalTestRunner\BrutalTestRunner;

$tester = new BrutalTestRunner();
$tester->header(__FILE__);


$tester->assertEqual( AppConfig::get('appName'), 'OSD_Aurox', 'AppConfig ok');

$instance = AppConfig::getInstance();
$tester->assertEqual( $instance->appName, 'OSD_Aurox', 'AppConfig singleton ok');

$tester->assertEqual( AppConfig::get('dbActive'), true, 'dbActive ok');

// test nouvelle conf
$val = AppConfig::get('mailSupportDest');
$tester->assertEqual( AppConfig::get('mailSupportDest'), '&lt;changMe&gt;', 'mailSupportDest ok');

// test si debug
$tester->assertEqual( AppConfig::isDebug(), true, 'AppConfig debug ok');

$tester->footer(exit: false);