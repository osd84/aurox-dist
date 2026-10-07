<?php

require_once '../aurox.php';

use osd84\BrutalTestRunner\BrutalTestRunner;
use OsdAurox\Cache;
use OsdAurox\IpInfo;

$tester = new BrutalTestRunner();
$tester->header(__FILE__);

// sans ipInfoApiUrl dans conf.php : désactivé, aucun appel réseau, fail-open
if (empty(\OsdAurox\AppConfig::get('ipInfoApiUrl'))) {
    $tester->assertEqual(IpInfo::info('212.27.40.240'), [], 'IpInfo désactivé sans ipInfoApiUrl');
    $tester->assertEqual(IpInfo::isFR('212.27.40.240'), true, 'IpInfo désactivé : isFR fail-open');
    $tester->footer(exit: false);
    return;
}

// suppresion du cache si existe
$cache = new Cache();
$cacheKey = "API_IP_CHECK:212.27.40.240";
$cache->delete($cacheKey);
$cacheKey = "API_IP_CHECK:2602:80d:1006::62";
$cache->delete($cacheKey);

$res = IpInfo::info('212.27.40.240');
$tester->assertEqual( $res['datas']['info']['country_code'], 'FR', 'IpInfo retourne les infos');
$tester->assertEqual( $res['datas']['fromAppCache'], false, 'IpInfo sans cache');

$res = IpInfo::info('212.27.40.240');
$tester->assertEqual( $res['datas']['fromAppCache'], true, 'IpInfo mets les infos en cache');

$res = IpInfo::info('2602:80d:1006::62');
$tester->assertEqual( $res['datas']['info']['country_code'], 'US', 'IpInfo retourne les infos Ipv6');

$res = IpInfo::countryCode('212.27.40.240');
$tester->assertEqual( $res, 'FR', 'IpInfo retourne le code pays');

$res = IpInfo::countryCode('2602:80d:1006::62');
$tester->assertEqual( $res, 'US', 'IpInfo retourne le code pays Ipv6');

$res = IpInfo::isFR('212.27.40.240');
$tester->assertEqual( $res, true, 'IpInfo retourne true si le pays est FR');

$res = IpInfo::isFR('2602:80d:1006::62');
$tester->assertEqual( $res, false, 'IpInfo retourne false si le pays est pas FR');

$res = IpInfo::continentCode('212.27.40.240');
$tester->assertEqual( $res, 'EU', 'IpInfo retourne le code continent');

$res = IpInfo::continentCode('2602:80d:1006::62');
$tester->assertEqual( $res, 'NA', 'IpInfo retourne le code continent Ipv6');

$res = IpInfo::isEU('212.27.40.240');
$tester->assertEqual( $res, true, 'IpInfo retourne true le continent est pas Européen');

$res = IpInfo::isEU('2602:80d:1006::62');
$tester->assertEqual( $res, false, 'IpInfo retourne false le continent est Européen');

$res = IpInfo::isEuOrNa('212.27.40.240');
$tester->assertEqual( $res, true, 'IpInfo retourne vrai est Européen');
$res = IpInfo::isEuOrNa('2602:80d:1006::62');
$tester->assertEqual( $res, true, 'IpInfo retourne vrai est NA');

$res = IpInfo::isEuOrNa('1.1.2.5');
$tester->assertEqual( $res, false, 'IpInfo retourne faux est Européen');

$tester->footer(exit: false);