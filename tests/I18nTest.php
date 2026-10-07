<?php

require_once '../aurox.php';

use OsdAurox\I18n;
use osd84\BrutalTestRunner\BrutalTestRunner;

$tester = new BrutalTestRunner();
$tester->header(__FILE__);

// Initialisation de l'instance globale pour I18n
$GLOBALS['i18n'] = new I18n('en');

// Test de la méthode getLocale()
$tester->header("Test de getLocale()");
$locale = $GLOBALS['i18n']->getLocale();
$tester->assertEqual($locale, 'en', "getLocale() retourne bien 'en' pour la locale par défaut");

// Test de la méthode setLocale() et de son effet
$tester->header("Test de setLocale()");
$GLOBALS['i18n']->setLocale('fr');
$newLocale = $GLOBALS['i18n']->getLocale();
$tester->assertEqual($newLocale, 'fr', "setLocale() modifie correctement la locale en 'fr'");

// Test de la méthode translate() avec une traduction existante
$tester->header("Test de translate()");
$translationKey = 'Welcome';
$translatedValue = $GLOBALS['i18n']->translate($translationKey);
$tester->assertEqual($translatedValue, 'Bienvenue', "translate() retourne bien 'Bienvenue' pour la clé 'welcome' dans la locale 'fr'");

// Test de la méthode translate() avec une traduction non existante
$unknownKey = 'nonexistent_key';
$unknownTranslation = $GLOBALS['i18n']->translate($unknownKey);
$tester->assertEqual($unknownTranslation, $unknownKey, "translate() retourne la clé elle-même si elle est introuvable");

// Test de la méthode translate() avec des placeholders
$placeholdersKey = 'hello_user';
$translatedWithPlaceholders = $GLOBALS['i18n']->translate($placeholdersKey, ['name' => 'Jean']);
$tester->assertEqual($translatedWithPlaceholders, 'Bonjour Jean', "translate() applique correctement les placeholders dans la traduction");

// Test de la méthode translate() avec l'option safe (désactivée par défaut)
$htmlKey = 'hello_user';
$htmlTranslation = $GLOBALS['i18n']->translate($htmlKey, ['name' => '<b>Jean</b>'], safe: true);
$tester->assertEqual($htmlTranslation, 'Bonjour <b>Jean</b>', "translate() retourne bien du HTML quand l'option safe est définie sur true");

// Test de la méthode translate() avec l'option safe désactivé (default)
$htmlKey = 'hello_user';
$htmlTranslationSafe = $GLOBALS['i18n']->translate($htmlKey,  ['name' => '<b>Jean</b>']);
$expectedSafeOutput = 'Bonjour &lt;b&gt;Jean&lt;/b&gt;';
$tester->assertEqual($htmlTranslationSafe, $expectedSafeOutput, "translate() échappe correctement le HTML quand l'option safe est activée");

// Test du liens de traduction Core et fichier Translations.php et /translations/<locale>.php
$trad = $GLOBALS['i18n']->translate('__testOverwrite');
$tester->assertEqual($trad, 'get From Core', "translate() vient de Translations.php, car existe pas dans /translations/fr.php");
$trad = $GLOBALS['i18n']->translate('__testOverwrite2');
$tester->assertEqual($trad, 'provient de fr.php', "translate() vient de /translations/fr.php, Translations.php est écrasé");


// Test de la méthode statique t()
$tester->header("Test de t()");
$staticTranslation = I18n::t('Welcome');
$tester->assertEqual($staticTranslation, 'Bienvenue', "I18n::t() retourne la traduction correcte de la clé 'welcome'");

// Test de la méthode statique t() avec I18n non initialisé
$GLOBALS['i18n'] = null;
try {
    I18n::t('welcome');
    $tester->assertEqual(true, false, "I18n::t() devrait lancer une exception si I18n n'est pas initialisé");
} catch (\LogicException $e) {
    $tester->assertEqual(str_contains($e->getMessage(), 'I18n not initialized'), true, "I18n::t() lance correctement une exception si I18n est non initialisé");
}


// Test de la méthode entity
$tester->header("Test de entity()");
$GLOBALS['i18n'] = new I18n('en');
$entity = [
    'name' => 'default',
    'name_en' => 'en',
    'name_fr' => 'fr',
    'name_it' => 'it',
];
// en
$r = I18n::entity($entity);
$tester->assertEqual($r, 'en', "I18n::entity() retourne la traduction correcte 'en'");
$GLOBALS['i18n'] = new I18n('fr');
// fr
$r = I18n::entity($entity);
$tester->assertEqual($r, 'fr', "I18n::entity() retourne la traduction correcte 'fr'");
// default cascade sur la clef 'name'
unset($entity['name_fr']);
$r = I18n::entity($entity);
$tester->assertEqual($r, 'default', "I18n::entity() retourne la traduction correcte 'default' car pas de name_fr");
// default cascade sur 'default' var
$r = I18n::entity($entity, 'custom');
$tester->assertEqual($r, 'custom', "I18n::entity() retourne la traduction correcte 'custom' car pas de name_fr et default");
// default cascade sur '' car pas de name, et pas de default val
unset($entity['name']);
$r = I18n::entity($entity);
$tester->assertEqual($r, '', "I18n::entity() retourne la traduction correcte '' car pas de name_fr et pas de default");
// sec test
$entity = [
    'name' => 'default',
    'name_fr' => "<script>alert('123')</script>",
];
$r = I18n::entity($entity);
$tester->assertEqual($r, '&lt;script&gt;alert(&#039;123&#039;)&lt;/script&gt;', "échappement ok");
$r = I18n::entity($entity, safe: true);
$tester->assertEqual($r, "<script>alert('123')</script>", "safe mode ok");
// test fieldName
$entity = [
    'custom' => 'default',
    'custom_fr' => "fr",
];
$r = I18n::entity($entity, fieldName: 'custom');
$tester->assertEqual($r, 'fr', "fieldName field ok");
// test cascade sur name si traduction null
$entity = [
    'name' => 'default',
    'name_fr' => null,
];
$r = I18n::entity($entity);
$tester->assertEqual($r, 'default', "fieldName field cascade sur custom ok si traduction null");


// test currentLocale
$tester->header("Test currentLocale()");
$GLOBALS['i18n'] = new I18n('en');
$currentLocale = I18n::currentLocale();
$tester->assertEqual($currentLocale, 'en', "currentLocale() retourne bien 'en'");
$GLOBALS['i18n'] = new I18n('fr');
$currentLocale = I18n::currentLocale();
$tester->assertEqual($currentLocale, 'fr', "currentLocale() retourne bien 'fr'");



// Test avec valeur par défaut
$tester->header("Test de getLocalizedFieldName()");
$result = I18n::getLocalizedFieldName();
$tester->assertEqual($result, 'name_fr', "Le champ par défaut devrait être 'name_fr'");
// Test avec un nom de champ personnalisé
$result = I18n::getLocalizedFieldName('description');
$tester->assertEqual($result, 'description_fr', "Le champ devrait être 'description_fr'");
// Test avec changement de locale
$GLOBALS['i18n'] = new I18n('en');
$result = I18n::getLocalizedFieldName('title');
$tester->assertEqual($result, 'title_en', "Le champ devrait être 'title_en'");
// Test avec caractères spéciaux
$result = I18n::getLocalizedFieldName("contenu<script>alert('123')</script>special_en");
$tester->assertEqual($result, "contenualert(&#039;123&#039;)special_en_en", "protégé contre XSS");


$tester->header("Test I18n::date");
// Test avec locale française
$GLOBALS['i18n']->setLocale('fr');
$tester->assertEqual(I18n::date('2025-01-15'), '15/01/2025', "La méthode date() doit retourner au format français (d/m/Y) quand la locale est 'fr'");
// Test avec locale anglaise
$GLOBALS['i18n']->setLocale('en');
$tester->assertEqual(I18n::date('2025-01-15'), '2025-01-15', "La méthode date() respecte le format personnalisé quand la locale n'est pas 'fr'");
// Test de sécurisation XSS
$date = '2025-01-15"><script>alert("XSS")</script>';
$tester->assertEqual(I18n::date($date), '', "La méthode date() doit sécuriser les entrées contre les XSS");

$tester->header("Test I18n::dateTime");
// Test avec locale française
$GLOBALS['i18n']->setLocale('fr');
$tester->assertEqual(I18n::dateTime('2025-01-15 12:01:23'), '15/01/2025 12:01', "La méthode dateTime() doit retourner au format français (d/m/Y) quand la locale est 'fr'");
$tester->assertEqual(I18n::dateTime('2025-01-15 12:01:23', showSec: True), '15/01/2025 12:01:23', "La méthode dateTime() doit retourner au format français (d/m/Y) quand la locale est 'fr'");
// Test avec locale anglaise
$GLOBALS['i18n']->setLocale('en');
$tester->assertEqual(I18n::dateTime('2025-01-15 12:01:23'), '2025-01-15 12:01', "La méthode dateTime() respecte le format personnalisé quand la locale n'est pas 'fr'");
$tester->assertEqual(I18n::dateTime('2025-01-15 12:01:23', showSec: True), '2025-01-15 12:01:23', "La méthode dateTime() respecte le format personnalisé quand la locale n'est pas 'fr'");
// Test de sécurisation XSS
$date = '2025-01-15"><script>alert("XSS")</script>';
$tester->assertEqual(I18n::dateTime($date), '', "La méthode date() doit sécuriser les entrées contre les XSS");


// Test frMonth
$tester->header("Test I18n::frMonth");
$tester->assertEqual(I18n::frMonth('January'), "Janvier", 'test janvier');
$tester->assertEqual(I18n::frMonth('December'), "Décembre", 'test décembre');
$tester->assertEqual(I18n::frMonth('Inconnu'), "Inconnu", 'test inconnu');

// Test frDateDMY
$tester->header("Test I18n::frDateDMY");
$r = I18n::frDateDMY('2025-01-15');
$tester->assertEqual($r, "15 Janvier 2025", 'test janvier');
$r = I18n::frDateDMY('2025-06-15');
$tester->assertEqual($r, "15 Juin 2025", 'test juin');


$tester->footer(exit: false);