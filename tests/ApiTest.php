<?php

require_once '../aurox.php';

use OsdAurox\Api;
use osd84\BrutalTestRunner\BrutalTestRunner;

$tester = new BrutalTestRunner();
$tester->header(__FILE__);

$apiResponse = new Api();

$tester->assertEqual( false, $apiResponse->status, 'Api ko');

// preparation d'une réponse classique
$apiResponse->status = true;
$apiResponse->infos[] = 'ok';
$apiResponse->datas = ['dataKey' => 'dataVal'];
$expect = '{"status":true,"infos":["ok"],"errors":[],"success":[],"warnings":[],"datas":{"dataKey":"dataVal"},"validators":[],"redirect_url":""}';
$res = $apiResponse->returnJsonResponse(output: true);
$tester->assertEqual( $expect, $res, 'Api ok');


// test du HTML

$apiResponse->html = '<b>hello html</b>';
$res = json_decode($apiResponse->returnJsonResponse(output: true), true);
$tester->assertEqual( '<b>hello html</b>', $res['html'], 'Api retourne le HTML');

$apiResponse->html = '';
$res = json_decode($apiResponse->returnJsonResponse(output: true), true);
$tester->assertEqual( False, array_key_exists('html' , $res), 'Api enleve la clef html');


$tester->footer(exit: false);