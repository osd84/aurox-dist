<?php

require_once '../aurox.php';

use OsdAurox\Ban;
use osd84\BrutalTestRunner\BrutalTestRunner;

$tester = new BrutalTestRunner();
$tester->header(__FILE__);

$_SERVER['HTTP_CLIENT_IP'] = '192.168.55.99';
$_SERVER['REMOTE_ADDR']   = '192.168.55.99';

if (!defined('APP_ROOT')) {
    define('APP_ROOT', realpath(__DIR__));
}

// --- checkRequestPatterns ---

$tester->header("checkRequestPatterns - dotfiles");

// .env basique
$_GET = []; $_POST = [];
$_SERVER['REQUEST_URI'] = '/.env';
$r = Ban::checkRequestPatterns(output: true);
$tester->assertEqual(true, $r, 'ban /.env via pattern');
Ban::unBan('192.168.55.99');

// .env.production
$_SERVER['REQUEST_URI'] = '/.env.production';
$r = Ban::checkRequestPatterns(output: true);
$tester->assertEqual(true, $r, 'ban /.env.production via pattern');
Ban::unBan('192.168.55.99');

// Nested .env (cas qu'on rate avec match exact)
$_SERVER['REQUEST_URI'] = '/app/portal/.env';
$r = Ban::checkRequestPatterns(output: true);
$tester->assertEqual(true, $r, 'ban /app/portal/.env (nested) via pattern');
Ban::unBan('192.168.55.99');

// Cas qu'on a vu dans les logs : /.remote /.local /.production
// Ces patterns ne sont pas dans nos regex (pas vraiment sensibles)
// MAIS on doit catcher /phpinfo, /info.php, /test.php

$tester->header("checkRequestPatterns - phpinfo & dérivés");

$_SERVER['REQUEST_URI'] = '/phpinfo.php';
$r = Ban::checkRequestPatterns(output: true);
$tester->assertEqual(true, $r, 'ban /phpinfo.php');
Ban::unBan('192.168.55.99');

$_SERVER['REQUEST_URI'] = '/info.php';
$r = Ban::checkRequestPatterns(output: true);
$tester->assertEqual(true, $r, 'ban /info.php');
Ban::unBan('192.168.55.99');

$_SERVER['REQUEST_URI'] = '/test/phpinfo.php';
$r = Ban::checkRequestPatterns(output: true);
$tester->assertEqual(true, $r, 'ban /test/phpinfo.php');
Ban::unBan('192.168.55.99');

$_SERVER['REQUEST_URI'] = '/old_phpinfo.php';
$r = Ban::checkRequestPatterns(output: true);
$tester->assertEqual(true, $r, 'ban /old_phpinfo.php');
Ban::unBan('192.168.55.99');

$tester->header("checkRequestPatterns - WordPress");

$_SERVER['REQUEST_URI'] = '/wp-login.php';
$r = Ban::checkRequestPatterns(output: true);
$tester->assertEqual(true, $r, 'ban /wp-login.php');
Ban::unBan('192.168.55.99');

$_SERVER['REQUEST_URI'] = '/wp-admin/admin-ajax.php';
$r = Ban::checkRequestPatterns(output: true);
$tester->assertEqual(true, $r, 'ban /wp-admin/...');
Ban::unBan('192.168.55.99');

$_SERVER['REQUEST_URI'] = '/wp-config.php.bak';
$r = Ban::checkRequestPatterns(output: true);
$tester->assertEqual(true, $r, 'ban /wp-config.php.bak');
Ban::unBan('192.168.55.99');

$tester->header("checkRequestPatterns - backups & archives");

$_SERVER['REQUEST_URI'] = '/www.zip';
$r = Ban::checkRequestPatterns(output: true);
$tester->assertEqual(true, $r, 'ban /www.zip');
Ban::unBan('192.168.55.99');

$_SERVER['REQUEST_URI'] = '/backup.sql';
$r = Ban::checkRequestPatterns(output: true);
$tester->assertEqual(true, $r, 'ban /backup.sql');
Ban::unBan('192.168.55.99');

$_SERVER['REQUEST_URI'] = '/db.sql.gz';
$r = Ban::checkRequestPatterns(output: true);
$tester->assertEqual(true, $r, 'ban /db.sql.gz');
Ban::unBan('192.168.55.99');

$tester->header("checkRequestPatterns - via ?url= (cible 404.php)");

$_SERVER['REQUEST_URI'] = '/404.php';
$_GET['url'] = '/phpinfo.php';
$r = Ban::checkRequestPatterns(output: true);
$tester->assertEqual(true, $r, 'ban via ?url=/phpinfo.php');
Ban::unBan('192.168.55.99');

$tester->header("checkRequestPatterns - faux positifs");

// URLs légitimes qui ne doivent PAS ban
unset($_GET['url']);
$_GET = [];

$_SERVER['REQUEST_URI'] = '/';
$r = Ban::checkRequestPatterns(output: true);
$tester->assertEqual(false, $r, 'pas de ban sur /');

$_SERVER['REQUEST_URI'] = '/contact';
$r = Ban::checkRequestPatterns(output: true);
$tester->assertEqual(false, $r, 'pas de ban sur /contact');

$_SERVER['REQUEST_URI'] = '/article/mon-article-php-2024';
$r = Ban::checkRequestPatterns(output: true);
$tester->assertEqual(false, $r, 'pas de ban sur URL contenant "php"');

$_SERVER['REQUEST_URI'] = '/produits/test-de-vin';
$r = Ban::checkRequestPatterns(output: true);
$tester->assertEqual(false, $r, 'pas de ban sur URL contenant "test"');


// --- blockUserAgent ---

$tester->header("blockUserAgent");

$_GET = []; $_POST = [];

$_SERVER['HTTP_USER_AGENT'] = 'crusader-worker/1.0';
$r = Ban::blockUserAgent(output: true);
$tester->assertEqual(true, $r, 'block UA crusader-worker/1.0');

$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; ProspectScope/1.0)';
$r = Ban::blockUserAgent(output: true);
$tester->assertEqual(true, $r, 'block UA ProspectScope');

$_SERVER['HTTP_USER_AGENT'] = 'python-httpx/0.28.1';
$r = Ban::blockUserAgent(output: true);
$tester->assertEqual(true, $r, 'block UA python-httpx');

$_SERVER['HTTP_USER_AGENT'] = 'AteveSearchSourceUrlDiscovery/0.1 (+mailto:crawler@example.com)';
$r = Ban::blockUserAgent(output: true);
$tester->assertEqual(true, $r, 'block UA AteveSearchSourceUrlDiscovery');

$_SERVER['HTTP_USER_AGENT'] = 'sqlmap/1.7.11#stable (http://sqlmap.org)';
$r = Ban::blockUserAgent(output: true);
$tester->assertEqual(true, $r, 'block UA sqlmap');

$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; Nmap Scripting Engine; https://nmap.org/book/nse.html)';
$r = Ban::blockUserAgent(output: true);
$tester->assertEqual(true, $r, 'block UA Nmap Scripting Engine');

$_SERVER['HTTP_USER_AGENT'] = 'CensysInspect/1.1 (+https://about.censys.io/)';
$r = Ban::blockUserAgent(output: true);
$tester->assertEqual(true, $r, 'block UA CensysInspect');

// pas de ban IP persistant : l'IP ne doit jamais finir dans la blacklist
$r = Ban::blockBlackListed(output: true);
$tester->assertEqual(false, $r, 'blockUserAgent ne persiste pas de ban IP');

// UA légitime : pas de blocage
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
$r = Ban::blockUserAgent(output: true);
$tester->assertEqual(false, $r, 'pas de blocage sur UA navigateur legitime');

// pas d'UA du tout : pas de blocage
unset($_SERVER['HTTP_USER_AGENT']);
$r = Ban::blockUserAgent(output: true);
$tester->assertEqual(false, $r, 'pas de blocage sur UA absent');


// --- rateLimit404 ---

$tester->header("rateLimit404 - flood détecté");

// On nettoie le cache pour partir propre
$cache = new \OsdAurox\Cache();
$cache->delete('ban_404_' . md5('192.168.55.99'));

// 4 premiers passages : OK, pas de ban
for ($i = 1; $i <= 4; $i++) {
    $r = Ban::rateLimit404(seconds: 10, maxRequests: 5);
    $tester->assertEqual(false, $r, "404 #$i : pas encore ban");
}

// 5e passage : ban
$r = Ban::rateLimit404(seconds: 10, maxRequests: 5);
$tester->assertEqual(true, $r, "404 #5 : ban déclenché");

// Vérif que l'IP est bien dans la blacklist
$r = Ban::blockBlackListed(output: true);
$tester->assertEqual(true, $r, "IP dans blacklist après flood 404");

Ban::unBan('192.168.55.99');
$cache->delete('ban_404_' . md5('192.168.55.99'));


// --- rebuildHtaccessBans ---

$tester->header("rebuildHtaccessBans");

// On ban 2 IP de test
Ban::ban('1.2.3.4');
Ban::ban('5.6.7.8');

$htaPath = APP_ROOT . '/public/.htaccess';
if (file_exists($htaPath)) {
    Ban::rebuildHtaccessBans();
    $content = file_get_contents($htaPath);
    $tester->assertEqual(str_contains($content, 'AUROX_BAN_START'), true, '.htaccess contient le marker');
    $tester->assertEqual(str_contains($content, 'Require not ip 1.2.3.4'), true, '.htaccess contient l\'IP 1.2.3.4');
    $tester->assertEqual(str_contains($content, 'Require not ip 5.6.7.8'), true, '.htaccess contient l\'IP 5.6.7.8');

    // Unban doit retirer du .htaccess
    Ban::unBan('1.2.3.4');
    $content = file_get_contents($htaPath);
    $tester->assertEqual(str_contains($content, 'Require not ip 1.2.3.4'), false, '.htaccess ne contient plus 1.2.3.4 après unban');
    $tester->assertEqual(str_contains($content, 'Require not ip 5.6.7.8'), true, '.htaccess contient encore 5.6.7.8');

    Ban::unBan('5.6.7.8');
} else {
    echo "  [SKIP] /public/.htaccess introuvable, test rebuildHtaccessBans skippé\n";
}

$tester->footer(exit: false);
