<?php


use osd84\BrutalTestRunner\BrutalTestRunner;

require dirname(__DIR__) . '/vendor/autoload.php';


$btr = new BrutalTestRunner();

$btr->header(__FILE__);
$btr->assertEqual(true, is_file(__FILE__), 'script is file');
$btr->assertEqual(true, true, 'true == true');
$btr->assertEqual(1, '1', "1 === '1'", false);
$btr->assertEqual(1, '1', "1 === '1' must, Fail because 'strict' enabled", true);
$btr->assertEqual(true, 1, "true === 1 must, Fail because 'strict' enabled", true);
$btr->assertEqual(true, false, 'true == false must, Fail because \'strict\' enabled', true);
$btr->footer();
