<?php

return [
    'appName' => 'OsdAurox',
    'appTitle' => 'OsdAurox',
    'appAuthor' => '-',
    'appDescription' => '-',
    'appKeywords' => '-',
    'appFavicon' => 'favicon.ico',
    'appLogo' => 'logo.png',
    'appLang' => 'fr',
    'appVersion' => '1.0.0',
    'devIp' => '127.0.0.1',
    'publicDevIp' => '<mettre ip publique de dev>',
    'disableHttpsRedirect' => False, // si True, désactive la redirection forcée en HTTPS en production

    'devUrl' => 'http://127.0.0.1:8000',
    'prodUrl' => 'https://127.0.0.1:8000',
    'appUrl' => 'https://127.0.0.1:8000',

    'debug' => True, // mettre False pour passer en production
    'dbActive' => False,
    'driver' => 'mysql', // 'mysql' (defaut si la cle est absente) ou 'pgsql'
    'host' => '127.0.0.1',
    'port' => '3306', // 5432 par defaut si driver = pgsql
    'db' => 'aurox_tests',
    'user' => 'test',
    'pass' => '<changemeTestOnly£>',
    'charset' => 'utf8mb4',
    'passwordMinLength' => 8,
    'passwordMaxLength' => 255,
    'passwordComplexity' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
    'adminFolder' => 'admin',
    'nonce' => False,
    'salt' => '<ChangeTeSalt>',

    'mailHost' => '<changMe>',
    'mailPort' => 465,
    'mailTls' => False,
    'mailSsl' => True,
    'mailUser' => '<changMe>',
    'mailPass' => '<changMe>',
    'mailFrom' => '<changMe>',
    'mailContactDest' => '<changMe>',
    'mailSupportDest' => '<changMe>',

    'ipInfoToken' => '<changeMe>',
    'ipInfoApiUrl' => '', // endpoint de géolocalisation IP, vide = désactivé (aucune IP envoyée)
    'abuseIpApiUrl' => '', // endpoint de score AbuseIPDB, vide = désactivé (aucune IP envoyée)
    'abuseIpDbKey' => '<changeMe>',
    'whiteListWafIp' => [], // ip de monitoring ignorée par le WAF

    'lang' => ['fr', 'en', 'it'],
    'discordWebhook' => 'https://discord.com/api/webhooks/<change_me>',
//    'ban_file_path' => '/chemin/vers/to_ban.txt', permet de spécifier un fichier supplémentaire pour stocker les adresses IP à bannir

];