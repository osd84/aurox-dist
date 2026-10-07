<?php

require_once '../aurox.php';

use OsdAurox\Dict;
use osd84\BrutalTestRunner\BrutalTestRunner;

$tester = new BrutalTestRunner();
$tester->header(__FILE__);


// init
if(!defined('APP_ROOT')) {
    define('APP_ROOT', realpath(__DIR__));
}

$_SERVER['REQUEST_METHOD'] = 'POST'; // mock

$array = ['key1' => 'value1', 'key2' => 'value2'];
$out = Dict::get($array, 'key1');
$tester->assertEqual($out, 'value1', 'Dict::get() existe ok');

$out = Dict::get($array, 'key3');
$tester->assertEqual($out, null, 'Dict::get() existe pas ok');

$out = Dict::get($array, 'key3', 'default');
$tester->assertEqual($out, 'default', 'Dict::get() existe pas et default est ok');



$tester->footer(exit: false);