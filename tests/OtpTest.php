<?php

require_once '../aurox.php';

use OsdAurox\Otp;
use osd84\BrutalTestRunner\BrutalTestRunner;

$tester = new BrutalTestRunner();
$tester->header(__FILE__);

// ---- Base32 ----
$tester->header("Test Base32 encode/decode");

$raw = 'Hello!';
$encoded = Otp::base32Encode($raw);
$tester->assertEqual($encoded, 'JBSWY3DPEE', 'base32Encode : vecteur connu');
$tester->assertEqual(Otp::base32Decode($encoded), $raw, 'base32Decode : round-trip');

// Tolérance espaces / minuscules / padding
$tester->assertEqual(Otp::base32Decode('jbsw y3dp ee=='), $raw, 'base32Decode : tolère espaces, minuscules, =');

try {
    Otp::base32Decode('ABC!DEF');
    $tester->assertEqual(0, 1, 'base32Decode : caractère invalide doit lever une exception');
} catch (\Exception $e) {
    $tester->assertEqual(1, 1, 'base32Decode : caractère invalide lève bien une exception');
}

// ---- Vecteurs RFC 6238 (secret ASCII "12345678901234567890", SHA1) ----
$tester->header("Test TOTP vecteurs RFC 6238");

$rfcSecret = Otp::base32Encode('12345678901234567890');
$tester->assertEqual($rfcSecret, 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 'Secret RFC en Base32');

// digits=8 pour coller aux vecteurs officiels de la RFC
$tester->assertEqual(Otp::totp($rfcSecret, 59, digits: 8), '94287082', 'RFC 6238 : T=59');
$tester->assertEqual(Otp::totp($rfcSecret, 1111111109, digits: 8), '07081804', 'RFC 6238 : T=1111111109');
$tester->assertEqual(Otp::totp($rfcSecret, 1234567890, digits: 8), '89005924', 'RFC 6238 : T=1234567890');
$tester->assertEqual(Otp::totp($rfcSecret, 2000000000, digits: 8), '69279037', 'RFC 6238 : T=2000000000');

// version 6 digits (standard authenticators)
$tester->assertEqual(Otp::totp($rfcSecret, 59), '287082', 'TOTP 6 digits : T=59');

// ---- generateSecret ----
$tester->header("Test generateSecret()");

$secret = Otp::generateSecret();
$tester->assertEqual(strlen($secret), 32, 'generateSecret : 20 octets -> 32 chars Base32');
$tester->assertEqual(preg_match('/^[A-Z2-7]+$/', $secret), 1, 'generateSecret : alphabet Base32 valide');
$tester->assertEqual($secret !== Otp::generateSecret(), true, 'generateSecret : deux appels différents');

try {
    Otp::generateSecret(8);
    $tester->assertEqual(0, 1, 'generateSecret : < 16 octets doit lever une exception');
} catch (\Exception $e) {
    $tester->assertEqual(1, 1, 'generateSecret : < 16 octets lève bien une exception');
}

// ---- verify ----
$tester->header("Test verify()");

$secret = Otp::generateSecret();
$code = Otp::totp($secret);
$slice = Otp::verify($secret, $code);
$tester->assertEqual($slice !== false, true, 'verify : code courant valide');
$tester->assertEqual($slice, intdiv(time(), 30), 'verify : retourne le bon timeslice');

// code avec espaces (saisie utilisateur "123 456")
$spaced = substr($code, 0, 3) . ' ' . substr($code, 3);
$tester->assertEqual(Otp::verify($secret, $spaced) !== false, true, 'verify : tolère les espaces');

// mauvais code
$bad = $code === '000000' ? '111111' : '000000';
$tester->assertEqual(Otp::verify($secret, $bad), false, 'verify : mauvais code refusé');

// format invalide
$tester->assertEqual(Otp::verify($secret, 'abcdef'), false, 'verify : code non numérique refusé');
$tester->assertEqual(Otp::verify($secret, '12345'), false, 'verify : mauvaise longueur refusée');

// dérive d'horloge : code du timeslice précédent accepté avec window=1
$prevCode = Otp::totp($secret, time() - 30);
$tester->assertEqual(Otp::verify($secret, $prevCode, window: 1) !== false, true, 'verify : timeslice -1 accepté (window=1)');
$tester->assertEqual(Otp::verify($secret, $prevCode, window: 0), false, 'verify : timeslice -1 refusé (window=0)');

// ---- Anti-replay ----
$tester->header("Test anti-replay");

$code = Otp::totp($secret);
$slice = Otp::verify($secret, $code);
$tester->assertEqual($slice !== false, true, 'anti-replay : premier usage OK');
// rejouer le même code avec le timeslice consommé
$replay = Otp::verify($secret, $code, lastTimeslice: $slice);
$tester->assertEqual($replay, false, 'anti-replay : même code refusé au second usage');

// ---- provisioningUri ----
$tester->header("Test provisioningUri()");

$uri = Otp::provisioningUri('ABCD2345', 'jean@example.com', 'MonApp');
$tester->assertEqual(str_starts_with($uri, 'otpauth://totp/MonApp:jean%40example.com'), true, 'provisioningUri : label correct');
$tester->assertEqual(str_contains($uri, 'secret=ABCD2345'), true, 'provisioningUri : secret présent');
$tester->assertEqual(str_contains($uri, 'issuer=MonApp'), true, 'provisioningUri : issuer présent');

$tester->footer(exit: false);
