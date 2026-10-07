<?php

require_once '../aurox.php';

use OsdAurox\Filter;
use osd84\BrutalTestRunner\BrutalTestRunner;

$tester = new BrutalTestRunner();
$tester->header(__FILE__);

// Test de la méthode truncate
$text = "Ceci est un long texte à tronquer.";
$truncated = Filter::truncate($text, 20);
$tester->assertEqual("Ceci est un long...", $truncated, "Truncate fonctionne correctement avec un texte court");

$truncatedExact = Filter::truncate($text, 35); // Inclut exactement tout le texte
$tester->assertEqual($text, $truncatedExact, "Truncate ne tronque pas si la longueur suffit");

$truncatedCustomEnding = Filter::truncate($text, 20, "---");
$tester->assertEqual("Ceci est un long---", $truncatedCustomEnding, "Truncate fonctionne avec un suffixe personnalisé");

// Test de la méthode dateFr
$date = "2023-12-15";
$dateFr = Filter::dateFr($date);
$tester->assertEqual("15/12/2023", $dateFr, "dateFr retourne correctement la date au format français");

// Test de la méthode dateMonthFr
$dateMonthFr = Filter::dateMonthFr($date);
$tester->assertEqual("Décembre 2023", $dateMonthFr, "dateMonthFr retourne correctement le mois en français");

// Test de la méthode dateUs
$dateUs = Filter::dateUs($date);
$tester->assertEqual("2023-12-15", $dateUs, "dateUs retourne correctement la date au format US");

// Test de la méthode toDayDateUs
$today = date('Y-m-d');
$todayUs = Filter::toDayDateUs();
$tester->assertEqual($today, $todayUs, "toDayDateUs retourne correctement la date actuelle au format US");

$tester->footer(exit: false);