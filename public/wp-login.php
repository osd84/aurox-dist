<?php
/**
 * Honeypot : aucun visiteur légitime ne tape /wp-login.php sur un site non-WordPress.
 * Ban immédiat de l'IP qui tente d'y accéder.
 *
 * Doit être listé dans /public/robots.txt (Disallow) pour ne pas piéger les bots honnêtes.
 */

require_once __DIR__ . '/../aurox.php';

use OsdAurox\Ban;
use OsdAurox\Sec;

Ban::ban(Sec::getRealIpAddr());

header('HTTP/1.0 403 Forbidden');
exit;
