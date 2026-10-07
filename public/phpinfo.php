<?php
/**
 * Honeypot phpinfo.php.
 * Ban immédiat. Aucun visiteur humain ne tape cette URL en prod.
 */

require_once __DIR__ . '/../aurox.php';

use OsdAurox\Ban;
use OsdAurox\Sec;

Ban::ban(Sec::getRealIpAddr());

header('HTTP/1.0 403 Forbidden');
exit;
