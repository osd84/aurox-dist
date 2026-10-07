<?php

namespace OsdAurox;

use PDO;

class Dbo
{
    public const DRIVER_MYSQL = 'mysql';
    public const DRIVER_PGSQL = 'pgsql';

    /**
     * Ports par défaut selon le driver, utilisés uniquement si $port est vide.
     */
    private const DEFAULT_PORTS = [
        self::DRIVER_MYSQL => '3306',
        self::DRIVER_PGSQL => '5432',
    ];

    public static ?Dbo $dbo_instance = null;

    public ?PDO $pdo;
    public string $driver;
    public string $host;
    public string $user;
    public string $dbname;
    public string $pass;
    public string $charset;
    public string $dsn;
    public string $port;
    public bool $ssl;
    public string $sslCert;

    private function __construct()
    {
        $this->pdo = null;
    }

    private function __clone()
    {
        // sigleton
    }


    private function init(
        string  $host = 'localhost',
        string  $port = '',
        string  $dbname = 'default_db',
        string  $user = 'root',
        string  $pass = '',
        string  $charset = 'utf8mb4',
        bool    $ssl = true,
        string  $sslCert = '/conf_mysql_ssl_cert/mysql-ca.pem',
        string  $driver = self::DRIVER_MYSQL,
    )
    {

        $this->driver = $driver;
        $this->host = $host;
        $this->port = $port !== '' ? $port : self::DEFAULT_PORTS[$driver];
        $this->user = $user;
        $this->pass = $pass;
        $this->charset = $charset;
        $this->dbname = $dbname;
        $this->ssl = $ssl;
        $this->sslCert = $sslCert;

        // verification si le cert existe. Seul mysql a besoin d'un fichier CA :
        // en pgsql on chiffre en sslmode=require, sans certificat.
        if($this->ssl && $this->driver === self::DRIVER_MYSQL) {
            if(!file_exists(APP_ROOT . $this->sslCert)) {
                throw new \Exception("SSL certificate directory does not exist");
            }
        }

        $this->dsn = $this->buildDsn();


        $this->pdoCon();
    }

    /**
     * Construit le DSN PDO selon le driver.
     *
     * MySQL : le charset passe par le DSN, le SSL par les options PDO.
     * PostgreSQL : pas de charset dans le DSN (on fait un SET NAMES après connexion),
     * et le SSL se configure ici, en sslmode=require, sans certificat.
     */
    private function buildDsn(): string
    {
        $host = $this->host;
        $port = $this->port;
        $dbname = $this->dbname;

        if ($this->driver === self::DRIVER_PGSQL) {
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
            if ($this->ssl) {
                // require : chiffrement obligatoire, sans verification du certificat serveur,
                // donc aucun fichier a fournir. Le defaut de libpq serait 'prefer', qui
                // retombe en clair si le serveur ne propose pas TLS.
                // Choix assume : beaucoup de PostgreSQL manages presentent un certificat auto-signe
                // (CN interne, CA:FALSE, pas de CA telechargeable) et regenere automatiquement
                // (rotation). verify-ca / verify-full casserait la connexion a chaque rotation.
                // Risque residuel : MITM sur le chemin reseau, a limiter via la liste d'IP
                // autorisees de l'hebergeur et/ou un reseau prive.
                $dsn .= ';sslmode=require';
            }
            return $dsn;
        }

        return "mysql:host=$host;port=$port;dbname=$dbname;charset=$this->charset";
    }

    public static function getInstance(
        string  $host = 'localhost',
        string  $port = '',
        string  $dbname = 'default_db',
        string  $user = 'root',
        string  $pass = '',
        string  $charset = 'utf8mb4',
        bool    $ssl = true,
        string  $driver = self::DRIVER_MYSQL,
    )
    {
        if (self::$dbo_instance == null) {
            self::$dbo_instance = new self();

            if (!array_key_exists($driver, self::DEFAULT_PORTS)) {
                die('Unsupported database driver: only mysql and pgsql are supported');
            }

            // on autorise pas les connexion distante sans SSL
            if(!$ssl && ! in_array($host, ['127.0.0.1', 'localhost'])) {
                die('Database connexion to remote hosts without SSL is not allowed');
            }

            self::$dbo_instance->init($host, $port, $dbname, $user, $pass, $charset, $ssl, driver: $driver);
        }

        return self::$dbo_instance;
    }

    /**
     * Retourne le driver de la connexion courante ('mysql' ou 'pgsql').
     */
    public static function driver(): string
    {
        return self::getInstance()->driver;
    }

    public static function isPgsql(): bool
    {
        return self::driver() === self::DRIVER_PGSQL;
    }

    private function pdoCon()
    {
        $dsn = $this->dsn;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        // Les constantes PDO::MYSQL_ATTR_* ne sont définies que si pdo_mysql est chargé,
        // on ne les touche donc que sur le driver mysql. En pgsql le SSL est déjà dans le DSN.
        if($this->ssl && $this->driver === self::DRIVER_MYSQL) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = APP_ROOT . $this->sslCert;
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
        }


        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);

            // pgsql n'accepte pas le charset dans le DSN
            if ($this->driver === self::DRIVER_PGSQL) {
                $this->pdo->exec("SET NAMES 'UTF8'");
            }
        } catch (\Exception $e) {
            echo('-- [STOPPED] Oops DB Connection failed -- ');
            Log::error('-- [STOPPED] Oops DB Connection failed -- ');
            Log::error($e);
            error_log($e);
            die();
        }
    }

    public static function getPdo()
    {
        $instance = self::getInstance();

        if ($instance->pdo == null) {
            $instance->pdoCon();
        }
        return $instance->pdo;
    }

    public static function sslVersion(): ?string
    {
        $instance = self::getInstance();
        $pdo = self::getPdo();

        if ($instance->driver === self::DRIVER_PGSQL) {
            $stmt = $pdo->query("SELECT version FROM pg_stat_ssl WHERE pid = pg_backend_pid()");
            $res  = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$res || empty($res['version'])) {
                return 'None';
            }

            return $res['version'];
        }

        $stmt = $pdo->query("SHOW STATUS LIKE 'Ssl_version'");
        $res  = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$res || empty($res['Value'])) {
            return 'None';
        }

        return $res['Value'];
    }


}
