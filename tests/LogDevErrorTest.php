<?php

require_once __DIR__ . '/../vendor/autoload.php';

use osd84\BrutalTestRunner\BrutalTestRunner;

$tester = new BrutalTestRunner();
$tester->header(__FILE__);

// Log::enableDevError() en debug : une erreur PHP arrete la page (exit), meme
// dans un try / catch. On ne peut pas tester un exit dans ce processus : chaque
// cas tourne dans un petit script enfant, avec sa propre conf (debug, pas de
// base, pas de webhook Discord : ErrorMonitoring ecrit alors dans dev.log).

$dossier = sys_get_temp_dir() . '/aurox_dev_error_' . bin2hex(random_bytes(4));
mkdir($dossier . '/logs', 0777, true);

$conf = require __DIR__ . '/../conf_example.php';
$conf['debug'] = True;
$conf['dbActive'] = False;
$conf['discordWebhook'] = '';
file_put_contents($dossier . '/conf.php', '<?php return ' . var_export($conf, true) . ';');

// lance $code apres le bootstrap aurox, rend [sortie, code de retour]
$lancer = function (string $code) use ($dossier): array {
    $script = $dossier . '/page.php';
    file_put_contents($script, "<?php\n"
        . "define('APP_ROOT', " . var_export($dossier, true) . ");\n"
        . "require " . var_export(realpath(__DIR__ . '/../aurox.php'), true) . ";\n"
        . $code);
    $sortie = [];
    $retour = 0;
    exec('php ' . escapeshellarg($script) . ' 2>/dev/null', $sortie, $retour);
    return [implode("\n", $sortie), $retour];
};

// 1. un warning dans un try / catch n'est plus avale
[$sortie, $retour] = $lancer('
try {
    $t = [];
    $x = $t["absent"];
} catch (\Throwable $e) {
    echo "AVALE";
}
echo "SUITE";
');
$tester->assertEqual(str_contains($sortie, 'FATAL ERROR - EXECUTION STOPPED'), true, "warning dans un catch : ecran d'erreur affiche");
$tester->assertEqual(str_contains($sortie, 'Undefined array key'), true, "warning dans un catch : message affiche");
$tester->assertEqual(str_contains($sortie, 'AVALE'), false, "warning dans un catch : le catch ne l'attrape pas");
$tester->assertEqual(str_contains($sortie, 'SUITE'), false, "warning dans un catch : la page s'arrete");
$tester->assertEqual($retour, 1, "warning dans un catch : exit(1)");

// 2. un deprecated arrete aussi la page
[$sortie, $retour] = $lancer('
$n = strlen(null);
echo "SUITE";
');
$tester->assertEqual(str_contains($sortie, 'deprecated'), true, "deprecated : ecran d'erreur affiche");
$tester->assertEqual(str_contains($sortie, 'SUITE'), false, "deprecated : la page s'arrete");

// 3. un @ est respecte : rien ne s'affiche, la page continue
[$sortie, $retour] = $lancer('
$t = [];
$x = @$t["absent"];
echo "SUITE";
');
$tester->assertEqual(str_contains($sortie, 'FATAL ERROR'), false, "@ : pas d'ecran d'erreur");
$tester->assertEqual(str_contains($sortie, 'SUITE'), true, "@ : la page continue");
$tester->assertEqual($retour, 0, "@ : exit(0)");

// 4. une exception metier reste attrapable
[$sortie, $retour] = $lancer('
try {
    throw new \RuntimeException("metier");
} catch (\Throwable $e) {
    echo "ATTRAPEE " . $e->getMessage();
}
echo " SUITE";
');
$tester->assertEqual(str_contains($sortie, 'ATTRAPEE metier SUITE'), true, "exception metier : attrapee, la page continue");
$tester->assertEqual(str_contains($sortie, 'FATAL ERROR'), false, "exception metier : pas d'ecran d'erreur");

// 5. une exception non attrapee : ecran d'erreur
[$sortie, $retour] = $lancer('
throw new \RuntimeException("pas attrapee");
');
$tester->assertEqual(str_contains($sortie, 'RuntimeException: pas attrapee'), true, "exception non attrapee : ecran d'erreur affiche");
$tester->assertEqual($retour, 1, "exception non attrapee : exit(1)");

// 6. l'erreur passe par ErrorMonitoring (Discord). Sans webhook, Discord::send()
// l'ecrit dans le log : la preuve que l'envoi a ete tente.
$logDev = (string) @file_get_contents($dossier . '/logs/dev.log');
$tester->assertEqual(str_contains($logDev, 'Discord webhook not set'), true, "ErrorMonitoring appele en dev (envoi Discord tente)");

// menage
foreach (glob($dossier . '/logs/*') as $fichier) {
    unlink($fichier);
}
rmdir($dossier . '/logs');
foreach (glob($dossier . '/*') as $fichier) {
    unlink($fichier);
}
rmdir($dossier);

$tester->footer(exit: false);
