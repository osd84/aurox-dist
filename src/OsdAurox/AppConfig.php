<?php

namespace OsdAurox;

use OsdAurox\Sec;

Class AppConfig {

    private static ?self $instance = null;

    private array $data = [];

    public static function init(array $config): self
    {
        if (self::$instance !== null) {
            throw new \RuntimeException("AppConfig déjà initialisé");
        }
        self::$instance = new self($config);
        return self::$instance;
    }

    private function __construct(array $conf)
    {
        // Defaults internes (optionnels)
        $defaults = [
            'adminFolder' => 'admin',
            'appName' => null,
            'appTitle' =>  null,
            'appAuthor' => null,
            'appDescription' => null,
            'appKeywords' => null,
            'appFavicon' => 'favicon.ico',
            'appLogo' => 'logo.png',
            'appLang' => 'fr',
            'appVersion' => '1.0.0',
            'devIp' => '127.0.0.1',
            'disableHttpsRedirect' => true,
            'devUrl' => 'http://localhost',
            'prodUrl' => 'http://localhost',
            'appUrl' => 'http://localhost',
            'debug' => false,
            'dbActive' => false,
            'driver' => 'mysql', // 'mysql' (defaut) ou 'pgsql'
            'host' => '127.0.0.1',
            'port' => '3306',
            'db' => 'default_db',
            'user' => 'userdb',
            'pass' => '',
            'charset' => 'utf8mb4',
            'lang' => ['fr'],
            'loginUrlForm' => '/',
            'mailContactDest' => null,
            'mailSupportDest' => null,
            'mailFrom' => null,
            'mailHost' => null,
            'mailPass' => null,
            'mailPort' => 465,
            'mailSsl' => true,
            'mailTls' => false,
            'mailUser' => null,
            'nonce' => false,
            'passwordComplexity' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]{8,}$/',
            'passwordMaxLength' => 255,
            'passwordMinLength' => 8,
            'salt' => '',
            'discordWebhook' => '',
            'abuseIpApiUrl' => '', // vide = AbuseIp désactivé
            'ipInfoApiUrl' => '', // vide = IpInfo désactivé
            'ban_file_path' => '',
            'featureRegister' => false,
            'featureUserAllowAdminCreate' => false,
        ];

        // Defaults dependants du driver, appliques uniquement si la cle est absente de conf.php.
        // Sans cle 'driver' on reste en mysql, les confs existantes ne bougent pas.
        if (($conf['driver'] ?? $defaults['driver']) === 'pgsql') {
            $defaults['port'] = '5432';
        }

        // on fusionne les valeurs fournies avec les defaults
        $this->data = array_replace($defaults, $conf);
    }



    public static function getInstance(): self
    {
        if(!self::$instance) {
            throw new \Exception('AppConfig not initialized');
        }
        return self::$instance;
    }

    public static function get(string $key, $default = '', bool $safe = false)
    {
        $instance = self::getInstance();
        $value = $instance->data[$key] ?? $default;

        if ($safe) {
            return $value;
        }

        // si c'est scalaire, on échappe
        if (is_scalar($value)) {
            return Sec::h((string)$value);
        }

        // tableaux/objets : on ne touche pas
        return $value;
    }

    /**
     * Magic méthode pour récupérer des propriétés depuis le tableau de configuration via $instance->
     *
     * @param string $key Le nom de la propriété à récupérer.
     * @return mixed|null retourne la valeur de la propriété ou null si elle n'existe pas.'
     */
    public function __get(string $key): mixed
    {
        // on récupère la valeur brute dans le tableau
        return $this->data[$key] ?? null;
    }

    public static function all(): array
    {
        return self::getInstance()->data;
    }

    public static function isDebug(): bool
    {
        return self::get('debug');
    }

    public static function dsn(): string
    {
        $c = self::getInstance()->data;

        // pgsql n'accepte pas le charset dans le DSN
        if (($c['driver'] ?? 'mysql') === 'pgsql') {
            return sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                $c['host'],
                $c['port'],
                $c['db']
            );
        }

        return sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $c['host'],
            $c['port'],
            $c['db'],
            $c['charset']
        );
    }

}