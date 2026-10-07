<?php
/**
 * /public/404.php
 *
 * Cible des RewriteRules .htaccess pour les 404.
 *
 * Pipeline :
 *   1. aurox.php → AbuseIp + Ban::blockBlackListed + Ban::checkRequest + Ban::checkRequestPatterns
 *      (si l'URL match un pattern WAF, on est déjà mort ici)
 *   2. Sinon : rate-limit 404. Au-delà du seuil, ban.
 *   3. Sinon : page 404 propre, noindex.
 */

require_once __DIR__ . '/../aurox.php';

use OsdAurox\Ban;
use OsdAurox\Base;

Base::noIndex();

// 5 × 404 en 60s = ban. Aucun humain ne fait ça.
Ban::rateLimit404(seconds: 60, maxRequests: 5);

http_response_code(404);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <title>404 - Page non trouvée</title>
</head>
<body>
<h1>404 - Page non trouvée</h1>
<p>La page demandée n'existe pas.</p>
</body>
</html>