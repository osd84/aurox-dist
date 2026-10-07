<?php

require_once '../aurox.php';

use OsdAurox\Fmt;
use osd84\BrutalTestRunner\BrutalTestRunner;

$tester = new BrutalTestRunner();
$tester->header(__FILE__);


$resultTrue = Fmt::bool(true);
$tester->assertEqual('Yes', $resultTrue, "bool retourne 'Yes' pour une valeur true");

$resultFalse = Fmt::bool(false);
$tester->assertEqual('No', $resultFalse, "bool retourne 'No' pour une valeur false");

$resultNumericTrue = Fmt::bool(1);
$tester->assertEqual('Yes', $resultNumericTrue, "bool retourne 'Yes' pour une valeur numérique équivalente à true");

$resultNumericFalse = Fmt::bool(0);
$tester->assertEqual('No', $resultNumericFalse, "bool retourne 'No' pour une valeur numérique équivalente à false");

$resultEmptyString = Fmt::bool('');
$tester->assertEqual('No', $resultEmptyString, "bool retourne 'No' pour une chaîne vide");

$resultNonEmptyString = Fmt::bool('hello');
$tester->assertEqual('Yes', $resultNonEmptyString, "bool retourne 'Yes' pour une chaîne non vide");

$tester->footer(exit: false);