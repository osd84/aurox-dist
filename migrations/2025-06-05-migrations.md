Editer conf.php 

## 1 Modifier conf.php
Ajouter : 

```php
    'port' => '3307',
```

Après `'host' => '127.0.0.1'`

## 2 Modifier aurox.php

Editer au [aurox.php](../aurox.php)

```php
Dbo::getInstance(
    AppConfig::get('host'),
    // ajouter AppConfig::get('port')
    AppConfig::get('db'),
    AppConfig::get('user'),
    AppConfig::get('pass', safe: true),
    AppConfig::get('charset')
);
```

Ajouter ligne :

`AppConfig::get('port')`

```php
Dbo::getInstance(
    AppConfig::get('host'),
    AppConfig::get('port'),  
    AppConfig::get('db'),
    AppConfig::get('user'),
    AppConfig::get('pass', safe: true),
    AppConfig::get('charset')
);
```

