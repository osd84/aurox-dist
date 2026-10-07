<?php

namespace OsdAurox;


class Ban
{
    public static Ban|null $banInstance = null;

    /**
     * IPs whitelistées : jamais bannies, jamais bloquées.
     * À remplir via AppConfig dans aurox.php :
     *   Ban::$whiteList = AppConfig::get('monitoring_ips', []);
     */
    public static array $whiteList = [];


    /**
     * Liste de patterns exacts (legacy, conservée pour compat).
     * Match exact via in_array().
     */
    public static array $blackListWords = [
        '/.git/config',
        '.git/config',
        '/wp-admin/admin-ajax.php',
        '/xmlrpc.php',
        '/.env',
        '/.git',
        '/idea',
        '/.idea',
        '/.env.local',
        '/config/.env',
        '/.git/config',
        '/.env.production',
        '/.svn/entries',
        '/vendor/.env',
        '/docker-compose.yml',
        '/access.log',
        '/nginx.conf',
        '/cgit/config',
        '/apache2.conf',
        '/.kube/config',
        '/node_modules/package.json',
        '/.well-known/security.txt',
        '/.well-known/openid-configuration',
        '/.well-known/apple-app-site-association',
        '/api/v1/secrets',
        '/suitecrm/logfile.log',
        '/manager/html',
        '/zabbix/index.php',
        '/nagios/cgi-bin/status.cgi',
        '/.svn/text-base/index.php.svn-base',
        '/backend/.env',
        '/node_modules/.bin/',
        '/index.php.bak',
        '/.npmrc',
        '/config.old',
        '/phpinfo.php',
        '/.DS_Store',
        '/index.php.swp',
        '/config.php.swo',
        '/uploads',
        '/files',
        '/storage/logs/laravel.log',
        '/logs/access.log',
        '/logs/error.log',
        '/.editorconfig',
        '/.eslintrc',
        '/config.yml',
        '/database.yml',
        '/config.json',
        '/secrets.json',
        '/package.json',
        '/composer.json',
        '/composer.lock',
        '/.gitlab-ci.yml',
        '/.idea/workspace.xml',
        '/.bash_history',
        '/.vscode/settings.json',
        '/wp-content/debug.log',
        '/api/keys',
        '/debug/vars',
        '/env',
        '/.aws/credentials',
        '/.gcloud/credentials.json',
        '/wp-config.php',
        '/.svn/auth/',
        '/.git/objects',
        '/.bzr/branch/branch.conf',
        '/.hg/dirstate',
        '/.hg/hgrc',
        '/portal/.env',
        '/env/.env',
        '/api/.env',
        '/app/.env',
        '/dev/.env',
        '/new/.env',
        '/new/.env.local',
        '/new/.env.staging',
        '/_phpinfo.php',
        '/_profiler/phpinfo',
        '/_profiler/phpinfo/info.php',
        '/wp-config',
        '/aws-secret.yaml',
        '/awstats/.env',
        '/conf/.env',
        '/cron/.env',
        '/www/.env',
        '/env.backup',
        '/xampp/phpinfo.php',
        '/laravel/info.php',
        '/js/.env',
        '/laravel/.env',
        '/laravel/core/.env',
        '/mail/.env',
        '/mailer/.env',
        '/laravel/.env.local',
        '/laravel/core/.env.production',
        '/main/.env',
    ];


    /**
     * Patterns regex catch-all : tout match déclenche un ban immédiat.
     * Insensible à la casse, pas d'ancrage strict pour matcher /xxx/.env et /.env
     */
    public static array $blackListPatterns = [
        // Dotfiles & VCS
        '~(^|/)\.(env|git|svn|hg|bzr|aws|kube|gcloud|idea|vscode|DS_Store|bash_history|npmrc|editorconfig|eslintrc)(\.|/|$)~i',
        '~\.env([./]|$)~i',                                 // .env.local, .env.prod, .env.backup, .env/...
        // WordPress
        '~/wp-(login|admin|config|content/debug|signup|cron|trackback|comments-post)~i',
        '~/xmlrpc\.php~i',
        // phpinfo & équivalents
        '~/[a-z0-9_-]*phpinfo[a-z0-9_-]*\.php~i',
        '~/(info|php|pinfo|i|infos?|php-info|phpversion|old_phpinfo|linusadmin-phpinfo)\.php~i',
        '~/(test|temp|time|debug)\.php~i',
        // Framework debug
        '~/_profiler/~i',
        '~/_environment~i',
        '~/webroot/~i',
        '~/(actuator|telescope|horizon|symfony/profiler)/~i',
        // Backups & secrets
        '~/(config|database|settings|secrets|tokens|package|composer|sftp-config|deployment-config)\.(json|yml|yaml|old|bak|swp|swo|sql|zip|xml)$~i',
        '~/wp-config\.(php|txt)(\.(old|bak|save|swp))?$~i',
        '~\.(sql|sql\.gz|bak|backup|old|orig|save|swp|swo|zip|tar|tar\.gz|tgz|7z|rar|dump)$~i',
        '~/(www|site|backup|db|sql|public_html|web|admin|home|app|src)\.(zip|tar|tar\.gz|tgz|sql|sql\.gz|7z|rar)$~i',
        // Admin tools
        '~/(manager/html|zabbix|nagios|phpmyadmin|pma|adminer|mysql)/~i',
        // Trees sensibles
        '~/(node_modules|vendor)/~i',
        '~/(storage/logs|logs/(access|error)\.log|wp-content/debug\.log)~i',
        '~/\.well-known/(security\.txt|openid-configuration|apple-app-site-association)~i',
        '~/cgi-bin/~i',
        '~/(api/(keys|v1/secrets)|debug/vars)~i',
        '~/server-(status|info)~i',
        '~/(aws-secret|s3)\.ya?ml$~i',
        // Exploits récurrents
        '~\$\{jndi:~i',                                     // log4j
        '~%24%7Bjndi~i',
        '~/(boaform|setup\.cgi|hndUnblock\.cgi|cgi-bin/luci)~i',  // routeurs IoT
        // LFI brute
        '~/(etc/passwd|etc/shadow|proc/self/environ)~i',
    ];


    /**
     * User-Agents connus de bots/scanners malveillants ou indésirables.
     * Match par sous-chaîne, insensible à la casse (stripos).
     */
    public static array $blackListUserAgents = [
        'crusader-worker',
        'ProspectScope',
        'python-httpx',
        'python-requests',
        'AteveSearchSourceUrlDiscovery',
        'go-http-client',
        'libwww-perl',
        'Scrapy',
        // Scanners de vuln / recon
        'sqlmap',
        'Nikto',
        'Nmap Scripting Engine',
        'masscan',
        'zgrab',
        'Nuclei',
        'wpscan',
        'dirbuster',
        'gobuster',
        'ffuf',
        'Acunetix',
        'Nessus',
        'OpenVAS',
        'CensysInspect',
        'Expanse',
    ];


    public array $blackList = [];
    public string $realIp = '';
    public string $banMessage = '';


    private function __construct()
    {
        // singleton
    }
    private function __clone()
    {
        // singleton
    }

    public function init($banMessage='Oops !'): void
    {
        if(!file_exists(APP_ROOT . '/blacklist__.php')) {
            file_put_contents(APP_ROOT . '/blacklist__.php', "<?php \r\n");
        }
        if(!file_exists(APP_ROOT . '/banlog__.php')) {
            file_put_contents(APP_ROOT . '/banlog__.php', "<?php \r\n");
        }

        $this->banMessage = $banMessage;
    }

    public static function getInstance($banMessage='Oops !'): ?Ban
    {
        if(!self::$banInstance) {
            $o_ban = new self();
            $o_ban->init( $banMessage);
            self::$banInstance = $o_ban;
        }
        if($banMessage){
            self::$banInstance->banMessage = $banMessage;
        }
        return self::$banInstance;
    }

    public static function blockBlackListed($output=false): bool
    {
        $instance = self::getInstance();
        $instance->realIp = Sec::getRealIpAddr();
        if (in_array($instance->realIp, self::$whiteList)) {
            return false;
        }
        $instance->blackList = $instance->loadBanList();
        if (in_array($instance->realIp, $instance->blackList)) {
            if($output) {
                return true;
            }
            header('HTTP/1.0 403 Forbidden');
            die($instance->banMessage);
        }
        return false;
    }

    public static function checkRequest($output=false): bool
    {
        $instance = self::getInstance();
        $instance->realIp = Sec::getRealIpAddr();
        if (in_array($instance->realIp, self::$whiteList)) {
            return false;
        }
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $currentPath = htmlspecialchars($uri, ENT_QUOTES, 'UTF-8');
        if(isset($_GET['url'])) {
            $currentPath = htmlspecialchars($_GET['url'], ENT_QUOTES, 'UTF-8');
        }
        $sanitarizedBlackList = [];
        foreach (self::$blackListWords as $word) {
            $sanitarizedBlackList[] = htmlspecialchars($word, ENT_QUOTES, 'UTF-8');
        }
        if (in_array($currentPath, $sanitarizedBlackList)) {
            $date = date('Y-m-d H:i:s');
            $log = "$instance->realIp\n";
            file_put_contents(APP_ROOT . '/blacklist__.php', $log, FILE_APPEND);
            $logtimed = "$date - $instance->realIp\n";
            file_put_contents(APP_ROOT. '/banlog__.php', $logtimed, FILE_APPEND);

            self::rebuildHtaccessBansIfStale();

            if($output) {
                return true;
            }
            header('HTTP/1.0 403 Forbidden');
            $appName = AppConfig::get('appName') ?: 'UnknownAppName';
            $url = AppConfig::get('appUrl') ?: 'UnknownAppUrl';
            Discord::send('[BAN] Hack crawling detected on ' . Sec::hNoHtml($appName) . ' ' . Sec::hNoHtml($url) . ' by ' . Sec::hNoHtml($instance->realIp) . ' ' . $currentPath);
            die($instance->banMessage);
        }
        return false;
    }


    /**
     * Check via patterns regex sur l'URL courante (ou ?url= si on est dans 404.php).
     * Plus large que checkRequest() : matche /xxx/.env, /test/phpinfo.php, etc.
     *
     * @param bool $output  Si true, retourne true au lieu de die() (utilisé en test).
     * @return bool true si l'IP a été bannie, false sinon.
     */
    public static function checkRequestPatterns(bool $output = false): bool
    {
        $instance = self::getInstance();
        $instance->realIp = Sec::getRealIpAddr();
        if (in_array($instance->realIp, self::$whiteList)) {
            return false;
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        // Si arrivé via /404.php?url=…, on check l'URL d'origine
        if (isset($_GET['url'])) {
            $uri = $_GET['url'];
        }
        $uri = (string)$uri;

        if ($uri === '' || $uri === '/' || $uri === '/404.php') {
            return false;
        }

        foreach (self::$blackListPatterns as $pattern) {
            if (preg_match($pattern, $uri)) {
                $date = date('Y-m-d H:i:s');
                $log = "$instance->realIp\n";
                @file_put_contents(APP_ROOT . '/blacklist__.php', $log, FILE_APPEND | LOCK_EX);
                @file_put_contents(APP_ROOT . '/banlog__.php', "$date - $instance->realIp - $uri (pattern)\n", FILE_APPEND | LOCK_EX);

                if (AppConfig::get('ban_file_path')) {
                    @file_put_contents(AppConfig::get('ban_file_path'), $log, FILE_APPEND | LOCK_EX);
                }

                // Régénère le .htaccess (throttlé)
                self::rebuildHtaccessBansIfStale();

                if ($output) {
                    return true;
                }
                header('HTTP/1.0 403 Forbidden');
                $appName = AppConfig::get('appName') ?: 'UnknownAppName';
                $url     = AppConfig::get('appUrl') ?: 'UnknownAppUrl';
                Discord::send('[BAN-PATTERN] ' . Sec::hNoHtml($appName) . ' ' . Sec::hNoHtml($url)
                    . ' by ' . Sec::hNoHtml($instance->realIp) . ' ' . Sec::hNoHtml($uri));
                die($instance->banMessage);
            }
        }
        return false;
    }


    /**
     * Check statique du User-Agent courant contre $blackListUserAgents.
     * Match par sous-chaîne insensible à la casse : "crusader-worker/1.0" matche "crusader-worker".
     *
     * Contrairement aux autres méthodes de ce fichier, pas de passage par le
     * système de ban (pas d'IP écrite dans blacklist__.php, pas de rebuild
     * .htaccess, pas de notif Discord) : c'est un blocage stateless, à
     * appeler en tout premier dans le pipeline, avant Ban::checkRequest().
     * 404 plutôt que 403 : pas de signal exploitable pour l'opérateur du bot
     * (contrairement aux autres bans, celui-ci se déclenche sur des pages
     * ordinaires, pas des chemins honeypot).
     *
     * @param bool $output  Si true, retourne true au lieu de die() (utilisé en test).
     * @return bool true si bloqué, false sinon.
     */
    public static function blockUserAgent(bool $output = false): bool
    {
        $realIp = Sec::getRealIpAddr();
        if (in_array($realIp, self::$whiteList)) {
            return false;
        }

        $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
        if ($ua === '') {
            return false;
        }

        foreach (self::$blackListUserAgents as $needle) {
            if ($needle !== '' && stripos($ua, $needle) !== false) {
                if ($output) {
                    return true;
                }
                header('HTTP/1.0 404 Not Found');
                die('Not Found');
            }
        }
        return false;
    }


    /**
     * Rate-limit sur les 404 : si une IP fait > maxRequests 404 en $seconds,
     * elle est bannie. Aucun visiteur humain ne génère ça.
     *
     * À appeler dans /public/404.php
     *
     * @param int $seconds        Fenêtre temporelle.
     * @param int $maxRequests    Nombre max de 404 toléré dans la fenêtre.
     * @return bool true si l'IP vient d'être bannie.
     */
    public static function rateLimit404(int $seconds = 60, int $maxRequests = 5): bool
    {
        $ip = Sec::getRealIpAddr();
        if (empty($ip)) {
            return false;
        }

        // Évite la double-pénalité si IP déjà bannie
        $instance = self::getInstance();
        $instance->blackList = $instance->loadBanList();
        if (in_array($ip, $instance->blackList)) {
            return false;
        }

        $cache = new Cache();
        $key = 'ban_404_' . md5($ip);
        $data = $cache->get($key);
        $now = time();

        if ($data === null) {
            $cache->set($key, ['count' => 1, 'start' => $now], $seconds);
            return false;
        }

        // Fenêtre expirée → reset
        if (($now - $data['start']) >= $seconds) {
            $cache->set($key, ['count' => 1, 'start' => $now], $seconds);
            return false;
        }

        $data['count']++;
        $remaining = max(1, $seconds - ($now - $data['start']));
        $cache->set($key, $data, $remaining);

        if ($data['count'] >= $maxRequests) {
            $date = date('Y-m-d H:i:s');
            $log = "$ip\n";
            @file_put_contents(APP_ROOT . '/blacklist__.php', $log, FILE_APPEND | LOCK_EX);
            @file_put_contents(APP_ROOT . '/banlog__.php',
                "$date - $ip - rate-limit 404 ({$data['count']} hits in {$seconds}s)\n",
                FILE_APPEND | LOCK_EX
            );
            if (AppConfig::get('ban_file_path')) {
                @file_put_contents(AppConfig::get('ban_file_path'), $log, FILE_APPEND | LOCK_EX);
            }

            self::rebuildHtaccessBansIfStale();

            $appName = AppConfig::get('appName') ?: 'UnknownAppName';
            $url     = AppConfig::get('appUrl') ?: 'UnknownAppUrl';
            Discord::send('[BAN-404-FLOOD] ' . Sec::hNoHtml($appName) . ' ' . Sec::hNoHtml($url)
                . ' by ' . Sec::hNoHtml($ip) . " ({$data['count']} × 404 in {$seconds}s)");
            return true;
        }

        return false;
    }


    public static function ban($ip): bool
    {
        $instance = self::getInstance();
        $instance->blackList = $instance->loadBanList();
        if (in_array($ip, $instance->blackList)) {
            return false;
        }
        $date = date('Y-m-d H:i:s');
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $currentPath = htmlspecialchars($uri, ENT_QUOTES, 'UTF-8');
        if(isset($_GET['url'])) {
            $currentPath = htmlspecialchars($_GET['url'], ENT_QUOTES, 'UTF-8');
        }
        $log = "$ip\n";
        file_put_contents(APP_ROOT . '/blacklist__.php', $log, FILE_APPEND);
        $logtimed = "$date - $ip\n";
        file_put_contents(APP_ROOT . '/banlog__.php', $logtimed, FILE_APPEND);
        if(AppConfig::get('ban_file_path')) {
            file_put_contents(AppConfig::get('ban_file_path'), $log, FILE_APPEND);
        }

        // Régénère le .htaccess pour blocage Apache (throttlé)
        self::rebuildHtaccessBansIfStale();

        $appName = AppConfig::get('appName') ?: 'UnknownAppName';
        $url = AppConfig::get('appUrl') ?: 'UnknownAppUrl';
        Discord::send('[BAN] Hack attempt detected on ' . Sec::hNoHtml($appName) . ' ' . Sec::hNoHtml($url) . ' by ' . Sec::hNoHtml(Sec::getRealIpAddr()) . ' ' . $currentPath);
        Discord::send(json_encode($_POST));
        Discord::send(json_encode($_GET));
        return true;
    }

    public static function banIfHackAttempt(): bool
    {
        $instance = self::getInstance();
        foreach ($_GET as $key => $value) {
            if (is_string($value) && ($instance->detectXssAttempt($value) || $instance->detectSqlInjectionAttempt($value))) {
                return $instance->ban( $instance->realIp);
            }
        }

        foreach ($_POST as $key => $value) {
            if (is_string($value) && ($instance->detectXssAttempt($value) || $instance->detectSqlInjectionAttempt($value))) {
                return $instance->ban( $instance->realIp);
            }
        }

        return false;
    }

    private function loadBanList(): array
    {
        return file(APP_ROOT . '/blacklist__.php', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }


    /**
     * Régénère /public/.htaccess avec un bloc de bans IP, géré entre marqueurs.
     * Apache 2.4 syntax (<RequireAll> + Require not ip).
     *
     * Throttlé : ne régénère qu'une fois toutes les 60s max pour éviter le hammer I/O.
     *
     * @return bool true si régénéré, false si skip (throttle).
     */
    public static function rebuildHtaccessBansIfStale(int $minIntervalSec = 60): bool
    {
        $htaFile = APP_ROOT . '/public/.htaccess';
        $banFile = APP_ROOT . '/blacklist__.php';

        if (!file_exists($banFile) || !file_exists($htaFile)) {
            return false;
        }

        // Throttle : utilise mtime d'un fichier marqueur dédié.
        // Placé dans cache_system_h€re/ (déjà writable, gitignored).
        $cacheDir = APP_ROOT . '/cache_system_h€re';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        $marker = $cacheDir . '/.htaccess_bans_last';
        if (file_exists($marker)) {
            $lastRun = (int) filemtime($marker);
            if ((time() - $lastRun) < $minIntervalSec) {
                return false;
            }
        }
        @touch($marker);

        return self::rebuildHtaccessBans();
    }

    /**
     * Force la régénération du bloc IP-ban dans /public/.htaccess.
     * À appeler depuis un cron, ou en debug. Pas de throttle.
     *
     * @return bool
     */
    public static function rebuildHtaccessBans(): bool
    {
        $htaFile = APP_ROOT . '/public/.htaccess';
        $banFile = APP_ROOT . '/blacklist__.php';

        if (!file_exists($banFile) || !file_exists($htaFile)) {
            return false;
        }

        $ips = file($banFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $ips = array_unique(array_filter($ips, function(string $ip): bool {
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                return false;
            }
            // Exclut les IPv6 réseau (se terminent par ::)
            // Require not ip 2001:db8:: bloquerait tout le /64
            if (str_ends_with(trim($ip), '::')) {
                error_log("[Ban] Skipped network IPv6 in .htaccess: $ip");
                return false;
            }
            return true;
        }));

        // Limite à 1000 IPs dans le .htaccess pour éviter la dégradation Apache sur mutu.
        // Les IPs plus anciennes restent dans blacklist__.php et sont bloquées par PHP.
        if (count($ips) > 1000) {
            $ips = array_slice($ips, -1000);
        }

        $block  = "# === AUROX_BAN_START === " . count($ips) . " IPs - " . date('c') . "\n";
        if (!empty($ips)) {
            $block .= "<RequireAll>\n    Require all granted\n";
            foreach ($ips as $ip) {
                // TODO: certains Apache mutualisés font un matching trop large sur les IPv6
                // (ex: 2001:41d0:304:200::b683 bloque 2001:41d0:401:3000::702)
                // IPv6 gérées uniquement par PHP (blockBlackListed).
                // Si l'hébergeur corrige le bug, remplacer ce bloc par le code commenté ci-dessous.
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    continue;
                }
                $block .= "    Require not ip $ip\n";

                /*
                // VERSION IPv6 APACHE — à réactiver si l'hébergeur corrige le matching
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    // /128 = hôte exact, évite le prefix matching implicite d'Apache
                    $normalized = inet_ntop(inet_pton($ip));
                    $block .= "    Require not ip $normalized/128\n";
                } else {
                    $block .= "    Require not ip $ip\n";
                }
                */
            }
            $block .= "</RequireAll>\n";
        }
        $block .= "# === AUROX_BAN_END ===\n";

        $current = @file_get_contents($htaFile);
        if ($current === false) {
            return false;
        }
        $clean = preg_replace(
            '/# === AUROX_BAN_START ===.*?# === AUROX_BAN_END ===\n?/s',
            '',
            $current
        );
        $new = rtrim($clean) . "\n\n" . $block;

        return @file_put_contents($htaFile, $new, LOCK_EX) !== false;
    }


    public static function detectXssAttempt(string $input): bool
    {
        $xssPatterns = [
            '/<script\b[^>]*>(.*?)<\/script>/is',
            '/javascript:/i',
            '/on\w+=["\'].*?["\']/i',
            '/<iframe\b.*?>.*?<\/iframe>/is',
            '/document\.cookie/i',
            '/<embed\b.*?>.*?<\/embed>/is',
            '/<object\b.*?>.*?<\/object>/is',
            '/vbscript:/i',
            '/data:text\/html/i'
        ];

        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    public static function detectSqlInjectionAttempt(string $input): bool
    {
        $sqlInjectionPatterns = [
            '/(\b|\')OR(\b|\'|")/i',
            '/(\b|\')AND(\b|\'|")/i',
            '/\'\s*--/i',
            '/--(\s|$)/i',
            '/;(\s|$)/i',
            '/UNION\s+SELECT/i',
            '/SELECT\s.*\sFROM/i',
            '/INSERT\s+INTO/i',
            '/UPDATE\s+\w+\s+SET/i',
            '/DELETE\s+FROM/i',
            '/DROP\s+TABLE/i',
            '/CREATE\s+TABLE/i',
            '/INFORMATION_SCHEMA/i',
            '/\bor\b.*?=/i',
            '/\bexec\b/i',
            '/sleep\(\d+\)/i',
            '/benchmark\((.*?)\)/i',
            '/load_file\(.+\)/i',
            '/outfile\s+/i',
        ];

        foreach ($sqlInjectionPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    public static function unBan( $ip): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        $instance = self::getInstance();
        $instance->blackList = $instance->loadBanList();
        $newContent = '';
        $hint = false;
        foreach ($instance->blackList as $key => $value) {
            if($value != $ip) {
                $newContent .= $value . "\n";
            } else {
                $hint = true;
            }
        }
        file_put_contents(APP_ROOT . '/blacklist__.php', $newContent);
        $date = date('Y-m-d H:i:s');
        $logtimed = "$date - $ip - unban\n";
        file_put_contents(APP_ROOT . '/banlog__.php', $logtimed, FILE_APPEND);

        // Régénère le .htaccess pour retirer l'IP du bloc Apache
        self::rebuildHtaccessBans();

        return $hint;
    }

}