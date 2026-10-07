<?php

namespace OsdAurox;

class AbuseIp
{
    public static array $whiteList = [
        '127.0.0.1',
        '::1'
    ];

    public static function info(string $ip): array
    {
        $apiUrl = AppConfig::get('abuseIpApiUrl');
        $appName = Sec::hNoHtml(AppConfig::get('appName'));
        // pas d'URL configurée = vérification désactivée (fail-open), aucune IP ne sort du serveur
        if (empty($apiUrl)) {
            return [];
        }

        $ip = Sec::sanitize($ip, 'ip');

        $dbo = Dbo::getPDO();

        // Purge opportuniste 1%
        if (rand(1, 100) === 1) {
            $dbo->prepare("DELETE FROM ip_abuse_cache WHERE expires_at < NOW()")->execute();
        }

        $stmt = $dbo->prepare("SELECT * FROM ip_abuse_cache WHERE ip = ? AND expires_at > NOW()");
        $stmt->execute([$ip]);
        $row = $stmt->fetch();

        if ($row) {
            $row['fromAppCache'] = true;
            return $row;
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

        if ($httpCode !== 200 || !$response) {
            error_log("Aurox API error : AbuseIp error for IP: {$ip}");
            return [];
        }

        $resApi = json_decode($response, true);
        if ($resApi['status'] !== true) {
            error_log('Aurox API error [status is false]');
            return [];
        }

        $d = $resApi['datas']['info'] ?? [];

        if (!empty($d)) {
            // Upsert : seule requete non portable de la lib.
            // Les deux variantes s'appuient sur un index unique sur la colonne `ip`.
            if (Dbo::isPgsql()) {
                $sql = "
            INSERT INTO ip_abuse_cache (ip, score, country, total_reports, last_reported_at, expires_at, raw)
            VALUES (?, ?, ?, ?, ?, NOW() + INTERVAL '7 days', ?)
            ON CONFLICT (ip) DO UPDATE SET
                score            = EXCLUDED.score,
                country          = EXCLUDED.country,
                total_reports    = EXCLUDED.total_reports,
                last_reported_at = EXCLUDED.last_reported_at,
                expires_at       = EXCLUDED.expires_at,
                raw              = EXCLUDED.raw
        ";
            } else {
                $sql = "
            INSERT INTO ip_abuse_cache (ip, score, country, total_reports, last_reported_at, expires_at, raw)
            VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY), ?)
            ON DUPLICATE KEY UPDATE
                score            = VALUES(score),
                country          = VALUES(country),
                total_reports    = VALUES(total_reports),
                last_reported_at = VALUES(last_reported_at),
                expires_at       = VALUES(expires_at),
                raw              = VALUES(raw)
        ";
            }

            $stmt = $dbo->prepare($sql);

            $score = (int) ($d['abuseConfidenceScore'] ?? $d['score'] ?? 0);
            $country = $d['countryCode'] ?? $d['country'] ?? null;
            $totalReports = (int) ($d['totalReports'] ?? $d['total_reports'] ?? 0);
            $lastReportedAt = $d['lastReportedAt'] ?? $d['last_reported_at'] ?? null;

            $stmt->execute([
                $ip,
                $score,
                $country,
                $totalReports,
                $lastReportedAt,
                json_encode($d)
            ]);
        }

        $resApi['fromAppCache'] = false;
        return $resApi;
    }

    public static function score(string $ip): int
    {
        $res = self::info($ip);
        return (int) (
            $res['datas']['info']['abuseConfidenceScore']
            ?? $res['datas']['info']['score']
            ?? $res['score']
            ?? 0
        );
    }

    public static function isSafe(string $ip, int $threshold = 25): bool
    {
        if (in_array($ip, self::$whiteList)) {
            return true;
        }
        return self::score($ip) < $threshold;
    }

    public static function isAbusive(string $ip, int $threshold = 50): bool
    {
        if (in_array($ip, self::$whiteList)) {
            return false;
        }
        return self::score($ip) >= $threshold;
    }

    public static function blockIfAbusive(int $threshold = 50): void
    {
        $ip = Sec::getRealIpAddr();
        if(empty($ip)) {
            return;
        }
        if (self::isAbusive($ip, $threshold)) {
            error_log("[AbuseIp] BLOCKED {$ip} score=" . self::score($ip) . " threshold={$threshold}");
            http_response_code(403);
            exit;
        }
    }
}