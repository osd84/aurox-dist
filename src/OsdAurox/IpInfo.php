<?php

namespace OsdAurox;

class IpInfo
{
    public static array $whiteList = [
        '127.0.0.1',
        '::1'
    ];

    public static function info(string $ip): array
    {

        $apiUrl = AppConfig::get('ipInfoApiUrl');
        $appName = Sec::hNoHtml(AppConfig::get('appName'));

        // pas d'URL configurée = géolocalisation désactivée (fail-open), aucune IP ne sort du serveur
        if(empty($apiUrl)) {
            return [];
        }

        $ip = Sec::sanitize($ip, 'ip');

        // On utilise le cache

        $cache = new Cache();
        $cacheKey = "API_IP_CHECK:{$ip}";
        $cacheInfos = $cache->get($cacheKey);
        $cacheInfosArray = [];
        if($cacheInfos) {
            $cacheInfosArray = json_decode($cacheInfos, true);
            $cacheInfosArray['datas']['fromAppCache'] = true;
        }
        if (
            !empty($cacheInfosArray['datas']['info']['country_code'])
        ) {
            return $cacheInfosArray;
        } else {
            $cache->delete($cacheKey);
        }

        $url = $apiUrl . '?ip=' . $ip . '&src=' . urlencode($appName);


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        curl_setopt($ch, CURLOPT_USERAGENT,
            'Mozilla/5.0 (X11; Linux x86_64; rv:121.0) Gecko/20100101 Firefox/121.0'
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: fr-FR,fr;q=0.9,en;q=0.8',
            'Connection: keep-alive'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);


        // En cas d'erreur API, autoriser (fail-open)
        if ($httpCode !== 200 || !$response) {
            error_log('API IpInfo retourne un 500 (IP: ' . $ip . ', code: ' . $httpCode . ')');
            return [];
        }

        // on met en cache
        $cache->set($cacheKey, $response, 0); // infinie
        $res = json_decode($response, true);
        $res['datas']['fromAppCache'] = false;
        return $res;
    }

    public static function countryCode(string $ip): string
    {
        $res = self::info($ip);
        return $res['datas']['info']['country_code'] ?? '';
    }

    public static function continentCode(string $ip): string
    {
        $res = self::info($ip);
        return $res['datas']['info']['continent_code'] ?? '';
    }

    public static function isFR(string $ip): bool
    {
        if(in_array($ip, self::$whiteList)) {
            return true;
        }
        $code = self::countryCode($ip);
        if ($code === '') {
            return true; // fail-open, API muette
        }
        return $code === 'FR';
    }

    public static function isEU(string $ip): bool
    {
        if(in_array($ip, self::$whiteList)) {
            return true;
        }
        $code = self::continentCode($ip);
        if ($code === '') {
            return true; // fail-open
        }
        return $code === 'EU';
    }

    public static function isEuOrNa(string $ip): bool
    {
        if(in_array($ip, self::$whiteList)) {
            return true;
        }
        $code = self::continentCode($ip);
        if ($code === '') {
            return true; // fail-open
        }
        return $code === 'NA' || $code === 'EU';
    }

}