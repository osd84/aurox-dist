# Aurox

**Une collection d'utilitaires PHP inspirée du Brutalisme et du Brutalism Dev Design.**

Pas de CMS, pas de plugin, pas de dépendance lourde. Du PHP, du SQL via PDO, du jQuery. Le code se lit en une après-midi.

Documentation : https://aurox.fr

**Licence : MIT**
**Prérequis : Apache2 + PHP ≥ 8.1**, extensions `pdo`, `curl`, `mbstring`, `gd`, `imagick`.
Base de données : MySQL / MariaDB ou PostgreSQL.

---

## Ce qu'il y a dedans

`src/OsdAurox/`, une classe par sujet, aucune magie :

| Sujet | Classes |
|---|---|
| Entrées, sortie, sécurité | `Sec` (sanitize, échappement, rate-limit, rôles), `Csrf`, `Captcha`, `Otp` (TOTP sans dépendance) |
| Base de données | `Dbo` (PDO, MySQL/PgSQL, SSL), `BaseModel` (Active Record minimal, **pas un ORM**), `BrutalCrud` (CRUD admin depuis un tableau de config) |
| Formulaires, validation | `Forms`, `FormsFilter`, `FormValidator`, `Validator`, `Field`, `Filter` |
| Vues | `Modal`, `Flash`, `Paginator` (+ mode DataTables), `ViewsShortcuts`, `Fmt`, `Js` |
| i18n | `I18n`, `Translations`, `Dict` |
| WAF maison | `Ban` (honeypots, motifs d'URL, User-Agent, flood 404, bans dans `.htaccess`), `AbuseIp`, `IpInfo` |
| Divers | `AppConfig`, `Cache` (fichiers), `Log`, `Mailer` (PHPMailer), `Discord`, `ErrorMonitoring`, `Image`, `MobileDetect`, `Api` |

Le dépôt est aussi une **application de démonstration fonctionnelle** : `aurox.php` (bootstrap), `app/`, `templates/`, `public/` (racine web), `translations/`.

---

## Installation

### Partir du dépôt (le plus simple)

```bash
git clone https://github.com/osd84/aurox-dist.git mon-projet
cd mon-projet
cp conf_example.php conf.php      # puis éditer
```

Pointez le `DocumentRoot` Apache sur `public/`. Tout le reste est hors racine web.

### Avec Composer, dans un projet existant

```bash
composer require osd84/aurox
```

Puis copiez à la racine de votre projet, depuis `vendor/osd84/aurox/` : `aurox.php`, `conf_example.php` (→ `conf.php`), et comme point de départ `app/`, `templates/`, `public/`. Le bootstrap `aurox.php` attend `conf.php` à côté de lui et une classe `App\AppUrls`.

### Configuration

Tout est dans `conf.php`, un simple tableau PHP. `conf_example.php` liste les clés avec leurs valeurs par défaut. En production : `debug => false`, `salt` renseigné, HTTPS obligatoire.

MySQL se connecte en SSL : placez le certificat CA de votre serveur dans `conf_mysql_ssl_cert/mysql-ca.pem`. PostgreSQL utilise `sslmode=require`, sans fichier.

---

## Sécurité & limitations

À lire avant de mettre en production.

* `BaseModel` **n'est pas un ORM**. Les noms de colonnes et de tables (`$field`, `$orderBy`, `$table`) passent par une liste blanche (`Sec::sqlIdent()`) ; une valeur invalide lève une `InvalidArgumentException`. `$select` reste injecté tel quel : **n'y passez jamais de saisie utilisateur.**
* `Sec::sanitize()` et `Sec::getParam()` sont des filtres **d'entrée**. En sortie, échappez avec `Sec::h()` / `Sec::hNoHtml()`.
* `Sec::getRealIpAddr()` ne lit que `REMOTE_ADDR`. Derrière un reverse proxy ou un CDN, configurez Apache (`mod_remoteip`) pour que `REMOTE_ADDR` soit la vraie IP, sinon le WAF bannira votre proxy.
* `AbuseIp` et `IpInfo` sont **désactivés tant que `abuseIpApiUrl` / `ipInfoApiUrl` ne sont pas renseignés** dans `conf.php`. Aucun endpoint n'est fourni : branchez le vôtre (format JSON attendu : voir le code des deux classes). Sans URL, rien ne sort de votre serveur.
* `Captcha` ne demande qu'un caractère à une position donnée. C'est confortable pour l'humain et faible contre un bot : couplez-le à `Sec::setRateLimit()` sur le formulaire.
* En production, `aurox.php` envoie `Strict-Transport-Security` avec `includeSubDomains`. Vérifiez que tous vos sous-domaines sont en HTTPS avant de déployer.
* `Ban` écrit les IP bannies dans `blacklist__.php` à la racine du projet et dans le `.htaccess` de `public/`. Ces fichiers doivent rester accessibles en écriture par PHP.

---

## Tests

Runner maison, sans dépendance ([BrutalTestRunner](https://github.com/osd84/BrutalTestRunner)) :

```bash
# base de test : aurox_tests.sql (MySQL) ou aurox_tests_pgsql.sql, et conf.php avec dbActive => true
cd tests
php run.php
```

Chaque `*Test.php` se lance aussi seul : `php SecTest.php`.

---

## Développement & contributions

Le développement se fait dans un dépôt privé. Ce dépôt public (`aurox-dist`) reçoit **un commit par version**, sans l'historique de travail. Les versions suivent [SemVer](https://semver.org/lang/fr/) et sont publiées sur [Packagist](https://packagist.org/packages/osd84/aurox).

Les pull requests sont lues et, si elles sont retenues, intégrées à la main dans la version suivante, pas fusionnées ici. Un correctif de sécurité se signale de préférence par mail : info@osd84.fr.

---

## Note

Ce projet est partagé **tel quel**, dans un esprit de liberté et de curiosité.
Pas de promesse, pas de magie.
