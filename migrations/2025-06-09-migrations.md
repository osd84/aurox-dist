## Aurox.php

Remplacer : 

```php
// DB
Dbo::getInstance(
AppConfig::get('host'),
AppConfig::get('port'),
AppConfig::get('db'),
AppConfig::get('user'),
AppConfig::get('pass', safe: true),
AppConfig::get('charset')
);
```

Par 
```php
// DB
// Si un DB est active dans conf.php
if(AppConfig::get('dbActive', false)) {
Dbo::getInstance(
AppConfig::get('host'),
AppConfig::get('port'),
AppConfig::get('db'),
AppConfig::get('user'),
AppConfig::get('pass', safe: true),
AppConfig::get('charset')
);
}
```

Dans conf.php, ajouter clef dbActive

```php
'debug' => true, // change me to false in production
'dbActive' => false, <---------------------------------------
'host' => '127.0.0.1',
```
