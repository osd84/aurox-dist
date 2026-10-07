<?php

require_once '../aurox.php';

use OsdAurox\Base;
use osd84\BrutalTestRunner\BrutalTestRunner;

$tester = new BrutalTestRunner();
$tester->header(__FILE__);


// init
if(!defined('APP_ROOT')) {
    define('APP_ROOT', realpath(__DIR__));
}

$arrayForm = [
    ['id' => 1, 'name' => 'test1', 'other' => 'other'],
    ['id' => 2, 'name' => 'test2', 'fake' => 'fake'],
    ['id' => 3, 'name' => 'test3', 'span' => 'span'],
];
$out = Base::asSelectList($arrayForm);
$expect = ['id' => 1, 'name' => 'test1'];
$tester->assertEqual($out[0], $expect, 'asSelectList() ok');

// Test scriptUrl()
$testScriptPath = 'js/api-request.js';
$scriptOutput = Base::scriptTag($testScriptPath);
$expectedScript = "<script src='$testScriptPath?v=" . filemtime(APP_ROOT . '/public/' . $testScriptPath) . "'></script>";
$tester->assertEqual($scriptOutput, $expectedScript, 'scriptUrl() generates correct script tag');
$testScriptPath = '/js/api-request.js';
$scriptOutput = Base::scriptTag($testScriptPath);
$expectedScript = "<script src='$testScriptPath?v=" . filemtime(APP_ROOT . '/public/' . $testScriptPath) . "'></script>";
$tester->assertEqual($scriptOutput, $expectedScript, 'scriptUrl() generates correct script tag');


// Test cssUrl()
$testCssPath = 'css/test.css';
$cssOutput = Base::cssTag($testCssPath);
$expectedCss = "<link rel='stylesheet' href='$testCssPath?v=" . filemtime(APP_ROOT . '/public/' . $testCssPath) . "'>";
$tester->assertEqual($cssOutput, $expectedCss, 'cssUrl() generates correct link tag');
$testCssPath = '/css/test.css';
$cssOutput = Base::cssTag($testCssPath);
$expectedCss = "<link rel='stylesheet' href='$testCssPath?v=" . filemtime(APP_ROOT . '/public/' . $testCssPath) . "'>";
$tester->assertEqual($cssOutput, $expectedCss, 'cssUrl() generates correct link tag');

$tester->footer(exit: false);

