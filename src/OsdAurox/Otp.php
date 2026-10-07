<?php

namespace OsdAurox;

use Exception;

/**
 * TOTP (RFC 6238) minimaliste, zéro dépendance.
 * Compatible Google Authenticator, FreeOTP, Aegis, etc.
 *
 * Flux type :
 *   1. Enrôlement : $secret = Otp::generateSecret();
 *      -> stocker en DB (users.otp_secret), afficher Otp::provisioningUri()
 *   2. Login : Otp::verify($secret, $code) après le check mot de passe
 *   3. Anti-replay : stocker le timeslice retourné par verify() et
 *      refuser tout code <= dernier timeslice utilisé
 */
class Otp
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Génère un secret aléatoire encodé en Base32 (standard authenticators).
     *
     * @param int $bytes Nombre d'octets d'entropie (défaut 20 = 160 bits, reco RFC 4226)
     */
    public static function generateSecret(int $bytes = 20): string
    {
        if ($bytes < 16) {
            throw new Exception('Otp secret : 16 octets minimum');
        }
        return self::base32Encode(random_bytes($bytes));
    }

    /**
     * Calcule le code TOTP pour un instant donné.
     *
     * @param string $secret Secret en Base32
     * @param int|null $time Timestamp Unix (défaut : maintenant)
     * @param int $digits Longueur du code (6 par défaut, standard)
     * @param int $period Durée d'un timeslice en secondes (30 standard)
     */
    public static function totp(string $secret, ?int $time = null, int $digits = 6, int $period = 30): string
    {
        $time = $time ?? time();
        $counter = intdiv($time, $period);
        return self::hotp(self::base32Decode($secret), $counter, $digits);
    }

    /**
     * Vérifie un code TOTP avec tolérance de dérive d'horloge.
     *
     * @param string $secret Secret en Base32
     * @param string $code Code saisi par l'utilisateur
     * @param int $window Nb de timeslices de tolérance avant/après (1 = ±30s)
     * @param int|null $lastTimeslice Dernier timeslice déjà utilisé (anti-replay), null pour ignorer
     *
     * @return int|false Le timeslice qui a matché (à stocker pour l'anti-replay), false si invalide
     */
    public static function verify(string $secret, string $code, int $window = 1, ?int $lastTimeslice = null, int $digits = 6, int $period = 30): int|false
    {
        $code = trim(str_replace(' ', '', $code));
        if (!preg_match('/^[0-9]{' . $digits . '}$/', $code)) {
            return false;
        }

        $key = self::base32Decode($secret);
        $currentSlice = intdiv(time(), $period);

        for ($i = -$window; $i <= $window; $i++) {
            $slice = $currentSlice + $i;
            // anti-replay : on refuse les timeslices déjà consommés
            if ($lastTimeslice !== null && $slice <= $lastTimeslice) {
                continue;
            }
            $expected = self::hotp($key, $slice, $digits);
            if (hash_equals($expected, $code)) {
                return $slice;
            }
        }
        return false;
    }

    /**
     * URI otpauth:// à afficher (texte ou QR code) pour l'enrôlement.
     * Exemple : otpauth://totp/MonApp:jean@example.com?secret=XXX&issuer=MonApp
     */
    public static function provisioningUri(string $secret, string $account, ?string $issuer = null): string
    {
        $issuer = $issuer ?? AppConfig::get('appName') ?? 'Aurox';
        $label = rawurlencode($issuer) . ':' . rawurlencode($account);
        return 'otpauth://totp/' . $label
            . '?secret=' . rawurlencode($secret)
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1&digits=6&period=30';
    }

    /**
     * HOTP (RFC 4226) : HMAC-SHA1 + troncature dynamique.
     */
    private static function hotp(string $key, int $counter, int $digits = 6): string
    {
        // Compteur sur 8 octets big-endian
        $bin = pack('N*', 0, $counter); // ok jusqu'en 2038+ sur PHP 64 bits
        $hash = hash_hmac('sha1', $bin, $key, true);

        $offset = ord($hash[19]) & 0x0F;
        $value = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % (10 ** $digits);

        return str_pad((string)$value, $digits, '0', STR_PAD_LEFT);
    }

    public static function base32Encode(string $data): string
    {
        if ($data === '') {
            return '';
        }
        $binary = '';
        foreach (str_split($data) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }
        $result = '';
        foreach (str_split($binary, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0');
            $result .= self::BASE32_ALPHABET[bindec($chunk)];
        }
        return $result;
    }

    public static function base32Decode(string $b32): string
    {
        $b32 = strtoupper(str_replace([' ', '='], '', $b32));
        if ($b32 === '') {
            throw new Exception('Otp secret vide');
        }
        $binary = '';
        foreach (str_split($b32) as $char) {
            $pos = strpos(self::BASE32_ALPHABET, $char);
            if ($pos === false) {
                throw new Exception('Otp secret : caractère Base32 invalide');
            }
            $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $result = '';
        foreach (str_split($binary, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $result .= chr(bindec($chunk));
            }
        }
        return $result;
    }
}
