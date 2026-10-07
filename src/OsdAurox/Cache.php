<?php

namespace OsdAurox;

use OsdAurox\AppConfig;

class Cache {
    private string $cachePath;

    public function __construct($folderName='cache_system_h€re') {
        $this->cachePath = APP_ROOT . '/' . $folderName . '/';

        // Crée le dossier s'il n'existe pas
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    /**
     * Récupère une valeur du cache
     * @param string $key La clé du cache
     * @return mixed La valeur ou null si elle n'existe pas ou est expirée
     */
    public function get(string $key): mixed
    {
        $filename = $this->getFilename($key);

        if (!file_exists($filename)) {
            return null;
        }

        $content = file_get_contents($filename);
        // pas d'objet en cache : un fichier cache modifié ne peut pas instancier de classe
        $data = unserialize(substr($content, strlen('<?php die(); ?>')), ['allowed_classes' => false]);
        if(empty($data)) {
            return null;
        }

        // Vérifie si le cache a expiré
        if ($data['expiry'] !== 0 && $data['expiry'] < time()) {
            $this->delete($key);
            return null;
        }

        return $data['value'];
    }

    /**
     * Met une valeur en cache
     * @param string $key La clé du cache
     * @param mixed $value La valeur à mettre en cache
     * @param int $timeout Durée de vie en secondes (0 = infini)
     * @return bool Succès ou échec
     */
    public function set(string $key, mixed $value, int $timeout = 0): bool
    {
        $filename = $this->getFilename($key);

        $now = new \DateTime();


        $data = [
            'value' => $value,
            'expiry' => $timeout > 0 ? time() + $timeout : 0,
            'ip' => Sec::hNoHtml(Sec::getRealIpAddr()),
            'write_at' => $now->format('Y-m-d H:i:s'),
        ];

        $content = '<?php die(); ?>' . serialize($data);
        return file_put_contents($filename, $content, LOCK_EX) !== false;
    }

    /**
     * Supprime une valeur du cache
     * @param string $key La clé du cache
     * @return bool Succès ou échec
     */
    public function delete(string $key): bool
    {
        $filename = $this->getFilename($key);

        if (file_exists($filename)) {
            return unlink($filename);
        }

        return true;
    }

    /**
     * Vide tout le cache
     */
    public function clear(): bool
    {
        $files = glob($this->cachePath . '*');

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }

    /**
     * Génère un nom de fichier à partir d'une clé
     */
    private function getFilename(string $key): string
    {
        $salt = AppConfig::get('salt');
        if (!$salt) {
            throw new \Exception('Salt is not set in conf.php');
        }
        $hash = md5($key . '_' . $salt);
        $strstr = strstr($key, ':', true);
        if ($strstr !== false) {
            $rawPrefix = $strstr;
        } else {
            $rawPrefix = $key;
        }
        $prefix = strtolower(Sec::sanitize($rawPrefix, 'slug'));
        $file = $this->cachePath . $prefix . '_' . $hash . '.php';
        return $file;
    }
}
