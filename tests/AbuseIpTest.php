<?php

require_once '../aurox.php';

use osd84\BrutalTestRunner\BrutalTestRunner;
use OsdAurox\AbuseIp;

$tester = new BrutalTestRunner();
$tester->header(__FILE__);

// sans abuseIpApiUrl dans conf.php : désactivé, aucun appel réseau, fail-open
if (empty(\OsdAurox\AppConfig::get('abuseIpApiUrl'))) {
    $tester->assertEqual(AbuseIp::info('212.27.40.240'), [], 'AbuseIp désactivé sans abuseIpApiUrl');
    $tester->assertEqual(AbuseIp::isAbusive('212.27.40.240'), false, 'AbuseIp désactivé : jamais abusive');
    $tester->footer(exit: false);
    return;
}

// IP FR connue propre (DNS public Free)
$ipFrClean = '212.27.40.240';
// IP abusive connue (botnet chinois récurrent)
$ipAbusive = '2602:80d:1008::52';
// IP US
$ipUs = '2602:80d:1006::62';

// Nettoyage BDD avant test
$dbo = \OsdAurox\Dbo::getPDO();
$dbo->prepare("DELETE FROM ip_abuse_cache WHERE ip IN (?, ?, ?)")
    ->execute([$ipFrClean, $ipAbusive, $ipUs]);

// --- info() sans cache ---
$res = AbuseIp::info($ipFrClean);
$tester->assertEqual($res['fromAppCache'], false, 'AbuseIp sans cache');
$tester->assertEqual(isset($res['datas']['info']['score']), true, 'AbuseIp retourne un score');

// --- cache BDD ---
$res = AbuseIp::info($ipFrClean);
$tester->assertEqual($res['fromAppCache'], true, 'AbuseIp met en cache BDD');

// --- score() ---
$score = AbuseIp::score($ipFrClean);
$tester->assertEqual($score >= 0 && $score <= 100, true, 'AbuseIp score dans la plage 0-100');

// --- isSafe() IP propre ---
$res = AbuseIp::isSafe($ipFrClean);
$tester->assertEqual($res, true, 'AbuseIp IP FR propre est safe');

// --- isAbusive() IP malveillante ---
$res = AbuseIp::isAbusive($ipAbusive);
$tester->assertEqual($res, true, 'AbuseIp détecte une IP abusive');

// --- isSafe() avec seuil custom ---
$res = AbuseIp::isSafe($ipFrClean, 50);
$tester->assertEqual($res, true, 'AbuseIp isSafe seuil custom 50');

// --- whitelist localhost ---
$res = AbuseIp::isSafe('127.0.0.1');
$tester->assertEqual($res, true, 'AbuseIp whitelist 127.0.0.1');

$res = AbuseIp::isAbusive('127.0.0.1');
$tester->assertEqual($res, false, 'AbuseIp whitelist 127.0.0.1 pas abusive');

// --- IPv6 ---
$res = AbuseIp::info($ipUs);
$tester->assertEqual(isset($res['datas']['info']), true, 'AbuseIp retourne infos IPv6');

$tester->footer(exit: false);