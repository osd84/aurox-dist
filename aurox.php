<?php

// Const for all APP
if(!defined('APP_ROOT')) {
    define('APP_ROOT', realpath(__DIR__));
}
// SEC - unique request id used by scp
define('REQUEST_ID', bin2hex(random_bytes(16)));
define('NONCE', base64_encode(random_bytes(16)));

require_once __DIR__ . '/vendor/autoload.php';


use App\AppUrls;
use OsdAurox\AbuseIp;
use OsdAurox\AppConfig;
use OsdAurox\Ban;
use OsdAurox\Dbo;
use OsdAurox\ErrorMonitoring;
use OsdAurox\I18n;
use OsdAurox\Log;
use OsdAurox\Sec;

// AppConfig
AppConfig::init(require APP_ROOT . '/conf.php');


// ERRORS
if (AppConfig::get('debug', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    Log::getInstance(path : '/logs/dev.log');
    DEFINE('DEBUG', true);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);

    Log::getInstance(path : '/logs/prod.log');
    DEFINE('DEBUG', false);
}

// SESSION
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

// 1) Enforce HTTPS très tôt (avant toute sortie et avant session)
if (!AppConfig::get('debug')) {
    if ((empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') && !AppConfig::get('disableHttpsRedirect')) {
        $appUrl = AppConfig::get('appUrl');
        if (!str_contains($appUrl, 'https://')) {
            die('HTTPS is required in PROD');
        }
        header('Location: ' . $appUrl);
        exit;
    }
    // On initalize le monitoring des erreurs fatales
    ErrorMonitoring::initialize();
}

// 2) Options de cookie AVANT session_start()
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

$sessionOptions = [
    'cookie_lifetime' => 0,
    'cookie_path'     => '/',
    'cookie_secure'   => $secure,      // obligatoire si SameSite=None
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',     // ou 'Lax' (souvent suffisant), ou 'None' si besoin cross-site
    'use_strict_mode' => 1,
];

// 3) Ouvrir la session avec options
if (session_status() === PHP_SESSION_NONE) {
    session_start($sessionOptions);
}

// 4) En-têtes de sécurité (avant tout output)
if (AppConfig::get('nonce')) {
    header("Content-Security-Policy: script-src 'self' 'nonce-" . Sec::noneCsp() . "'; object-src 'none';");
}
if (!AppConfig::get('debug')) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains'); // ok uniquement en https
}


// DB
// Si un DB est active dans conf.php
if(AppConfig::get('dbActive', false)) {
    Dbo::getInstance(
        AppConfig::get('host'),
        AppConfig::get('port'),
        AppConfig::get('db'),
        AppConfig::get('user'),
        AppConfig::get('pass', safe: true),
        AppConfig::get('charset'),
        driver: AppConfig::get('driver')
    );
}

// Flash
$GLOBALS['messages'] = [];

// ban system
Ban::$whiteList = array_merge(
    Ban::$whiteList,
    AppConfig::get('whiteListWafIp', [])
);
Ban::blockUserAgent();           // 0. User-Agent connu malveillant ? (statique, sans ban IP, avant tout le reste)
AbuseIp::blockIfAbusive();
Ban::blockBlackListed();        // 1. IP déjà bannie ?
Ban::checkRequest();             // 2. URL match exact dans $blackListWords ?
Ban::checkRequestPatterns();     // 3. URL match regex dans $blackListPatterns ?

// i18n
$GLOBALS['i18n'] = new I18n();
$locale = $_SESSION['locale'] ?? 'fr';
$GLOBALS['i18n']->setLocale($locale);


// On stocke le referer dans la session
Sec::storeReferer();

AppUrls::existOr404();