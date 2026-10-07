# Aurox

## 1 - Modification du bootstrap aurox.php

Remplacer le bloc après `//SESION` dans `aurox.php` par :  

```
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
    'cookie_samesite' => 'Strict',     // ou 'Lax' (souvent suffisant), ou 'None' si besoin cross-site
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
```

## Correction du bootstrap `aurox.php` :

Supprimer le `?>` et les saut de ligne à la fin de `aurox.php` si il y en a.

## Ajout d'une option de CONF

A modifier dans le fichier `/conf.php` :

```
mailSupportDest = """
```