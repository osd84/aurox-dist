<?php
/**
 * Honeypot xmlrpc.php (WordPress).
 * Ban immédiat de toute IP qui tente d'y accéder.
 */

require_once __DIR__ . '/../aurox.php';

use OsdAurox\Ban;
use OsdAurox\Sec;

Ban::ban(Sec::getRealIpAddr());

header('HTTP/1.0 403 Forbidden');
exit;
