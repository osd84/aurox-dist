<?php
namespace OsdAurox;

class Captcha
{
    /**
     * Génère un code et choisit une position aléatoire
     */
    public static function generateCode(int $length = 5): void
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }

        $_SESSION['captcha_code'] = $code;
        $_SESSION['captcha_pos']  = random_int(1, $length); // position 1-based
    }

    /**
     * Retourne l'image captcha en base64 (data URI).
     */
    public static function renderBase64(int $width = 120, int $height = 40): string
    {
        $code = $_SESSION['captcha_code'] ?? null;
        if (!$code) {
            self::generateCode();
            $code = $_SESSION['captcha_code'];
        }

        // Canvas source
        $src = imagecreatetruecolor($width, $height);
        imageantialias($src, true);
        $bg = imagecolorallocate($src, 240, 240, 240);
        imagefilledrectangle($src, 0, 0, $width, $height, $bg);

        // Bruit lignes
        for ($i = 0; $i < 18; $i++) {
            imagesetthickness($src, mt_rand(1, 2));
            $noiseColor = imagecolorallocate($src, mt_rand(150, 210), mt_rand(150, 210), mt_rand(150, 210));
            imageline(
                $src,
                mt_rand(0, $width), mt_rand(0, $height),
                mt_rand(0, $width), mt_rand(0, $height),
                $noiseColor
            );
        }

        // Bruit points
        for ($i = 0; $i < 300; $i++) {
            $dotColor = imagecolorallocate($src, mt_rand(180, 230), mt_rand(180, 230), mt_rand(180, 230));
            imagesetpixel($src, mt_rand(0, $width - 1), mt_rand(0, $height - 1), $dotColor);
        }

        // Texte complet (même si on ne demandera qu’une lettre)
        $textColor = imagecolorallocate($src, 40, 40, 40);
        $hasTtf = function_exists('imagettftext') && file_exists(__DIR__ . '/DejaVuSans.ttf');
        $font = $hasTtf ? (__DIR__ . '/DejaVuSans.ttf') : null;

        $len = strlen($code);
        $marginX = 8;
        $space = max(12, (int) floor(($width - 2 * $marginX) / $len));
        $baseY = (int) ($height * 0.7);
        $fontSize = 20;

        for ($i = 0; $i < $len; $i++) {
            $ch = $code[$i];
            $angle = mt_rand(-18, 18);
            $jx = mt_rand(-1, 2);
            $jy = mt_rand(-2, 2);
            $x = $marginX + $i * $space + $jx;
            $y = $baseY + $jy;

            if ($hasTtf) {
                imagettftext($src, $fontSize, $angle, $x, $y, $textColor, $font, $ch);
            } else {
                imagestring($src, 5, $x, (int)($height/3) + $jy, $ch, $textColor);
            }
        }

        // Lignes strikethrough
        for ($i = 0; $i < 2; $i++) {
            imagesetthickness($src, mt_rand(2, 3));
            $strikeColor = imagecolorallocate($src, mt_rand(80, 140), mt_rand(80, 140), mt_rand(80, 140));
            $y1 = mt_rand((int)($height*0.3), (int)($height*0.7));
            $y2 = mt_rand((int)($height*0.3), (int)($height*0.7));
            imageline($src, 0, $y1, $width, $y2, $strikeColor);
        }

        // Distorsion wave
        $dst = imagecreatetruecolor($width, $height);
        imageantialias($dst, true);
        $bg2 = imagecolorallocate($dst, 240, 240, 240);
        imagefilledrectangle($dst, 0, 0, $width, $height, $bg2);

        $ampX = 3.0;
        $ampY = 2.0;
        $periodX = mt_rand(30, 50);
        $periodY = mt_rand(20, 40);
        $phaseX = mt_rand(0, 1000) / 100.0;
        $phaseY = mt_rand(0, 1000) / 100.0;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $sx = (int) round($x + sin(($y / $periodX) + $phaseX) * $ampX);
                $sy = (int) round($y + cos(($x / $periodY) + $phaseY) * $ampY);
                if ($sx >= 0 && $sx < $width && $sy >= 0 && $sy < $height) {
                    $col = imagecolorat($src, $sx, $sy);
                    imagesetpixel($dst, $x, $y, $col);
                } else {
                    imagesetpixel($dst, $x, $y, $bg2);
                }
            }
        }


        ob_start();
        imagepng($dst);
        $raw = ob_get_clean();

        return 'data:image/png;base64,' . base64_encode($raw);
    }

    /**
     * Vérifie uniquement la lettre demandée
     */
    public static function verify(string $input, bool $unset = true): bool
    {
        $code = $_SESSION['captcha_code'] ?? null;
        $pos  = $_SESSION['captcha_pos'] ?? null;
        if (!$code || !$pos) {
            return false;
        }

        $expected = $code[$pos - 1]; // position humaine → index 0-based
        $ok = strtolower(trim($input)) === strtolower($expected);

        if ($ok && $unset) {
            unset($_SESSION['captcha_code'], $_SESSION['captcha_pos']);
        }

        return $ok;
    }

    public static function clear(): void
    {
        unset($_SESSION['captcha_code'], $_SESSION['captcha_pos']);
    }

    /**
     * HTML : image + question "xème lettre"
     */
    public static function captchaHtml(): string
    {
        if (!isset($_SESSION['captcha_code'])) {
            self::generateCode();
        }

        $id = 'captcha_' . bin2hex(random_bytes(4));
        $base64 = self::renderBase64();
        $pos = $_SESSION['captcha_pos'] ?? 1;

        // suffixe (1ère, 2ème, 3ème…)
        $suffix = match($pos) {
            1 => 'ère',
            default => 'ème'
        };

        $label = I18n::t('Enter the') . ' ' . Sec::hNoHtml($pos . $suffix) . ' <strong>' . I18n::t('character') . '</strong>';


        $html = <<<HTML
            <div class="captcha-group" style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
              <img id="{$id}_img" src="{$base64}" alt="Captcha" style="height:40px;vertical-align:middle;border:1px solid #ddd;">
              <label for="{$id}_input">{$label}</label>
             <input type="text" id="{$id}_input" name="captcha" maxlength="1" placeholder="?" autocomplete="off" required>
            </div>
        HTML;

        return $html;
    }
}
