## Aurox.php

Remplacer : 

```php
    if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
```

Par 

```php
    if ((empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') && !AppConfig::get('disableHttpsRedirect')) {
```
