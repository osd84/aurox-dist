<?php

require_once '../aurox.php';

use OsdAurox\Forms;
use OsdAurox\FormValidator;
use OsdAurox\I18n;
use osd84\BrutalTestRunner\BrutalTestRunner;


$tester = new BrutalTestRunner();
$tester->header(__FILE__);

// Test de la méthode action
$elem = ['id' => 123];
$html = Forms::action($elem);
$tester->assertEqual(str_contains($html, '<a href="?action=edit&id=123"'), true, "action génère correctement le lien d'édition");
$tester->assertEqual(str_contains($html, '<a href="?action=detail&id=123"'), true, "action génère correctement le lien de détail");
$tester->assertEqual(str_contains($html, '<form action="?action=delete"'), true, "action génère correctement le formulaire de suppression");

// Test de la génération d'un champ select
$form = new Forms('/submit.php'); // Initialise un objet form
$list = [
    ['id' => 1, 'name' => 'Option 1'],
    ['id' => 2, 'name' => 'Option 2']
];
$htmlSelect = $form->select($list, 'testSelect', 'selectTestId');
$tester->assertEqual(str_contains($htmlSelect, '<select id="selectTestId" name="testSelect"'), true, "select génère correctement la balise <select>");
$tester->assertEqual(str_contains($htmlSelect, '<option value="1" class="" >Option 1</option>'), true, "select génère correctement l'option avec value 1");
$tester->assertEqual(str_contains($htmlSelect, '<option value="2" class="" >Option 2</option>'), true, "select génère correctement l'option avec value 2");

// select : $link_edit échappé et schémas dangereux refusés
$htmlSelect = $form->select($list, 'testSelect', link_edit: '/client/edit?id=3&tab=1');
$tester->assertEqual(str_contains($htmlSelect, 'href="/client/edit?id=3&amp;tab=1"'), true, "select link_edit relatif échappé");
$htmlSelect = $form->select($list, 'testSelect', link_edit: 'https://exemple.fr/edit');
$tester->assertEqual(str_contains($htmlSelect, 'href="https://exemple.fr/edit"'), true, "select link_edit https accepté");
$htmlSelect = $form->select($list, 'testSelect', link_edit: '/x" onmouseover="alert(1)');
$tester->assertEqual(str_contains($htmlSelect, '" onmouseover="'), false, "select link_edit pas de sortie d'attribut");
$tester->assertEqual(str_contains($htmlSelect, '&quot; onmouseover=&quot;'), true, "select link_edit guillemets échappés");
foreach (['javascript:alert(1)', 'JaVaScRiPt:alert(1)', " java\tscript:alert(1)", 'data:text/html,<script>alert(1)</script>', 'vbscript:msgbox(1)'] as $bad) {
    $htmlSelect = $form->select($list, 'testSelect', link_edit: $bad);
    $tester->assertEqual(str_contains($htmlSelect, '<a '), false, "select link_edit schéma refusé : " . json_encode($bad));
}

// Test de la génération d'un champ input
$htmlInput = $form->input('username', 'Nom utilisateur', 'inputUserId', 'text', 'Entrez votre nom');
$tester->assertEqual(str_contains($htmlInput, '<input type="text" id="inputUserId" name="username"'), true, "input génère correctement la balise <input>");
$tester->assertEqual(str_contains($htmlInput, 'placeholder="Entrez votre nom"'), true, "input inclut correctement l’attribut placeholder");
$tester->assertEqual(str_contains($htmlInput, '<label for="inputUserId"'), true, "input inclut correctement le label associé");

// Test de la méthode formStart
$htmlFormStart = $form->formStart('post', true, false);
$tester->assertEqual(str_contains($htmlFormStart, '<form action="/submit.php" method="post"'), true, "formStart génère correctement la balise <form>");
$tester->assertEqual(str_contains($htmlFormStart, 'enctype="multipart/form-data"'), true, "formStart inclut correctement l’attribut enctype pour les formulaires multipart");

// Test de la méthode formEnd
$htmlFormEnd = $form->formEnd();
$tester->assertEqual('</form></div>', $htmlFormEnd, "formEnd génère correctement la fermeture du formulaire");

// Test de la méthode checkbox
$htmlCheckbox = $form->checkbox('terms', 'Conditions d\'utilisation', 'checkboxId', 'form-check-input', true);
$tester->assertEqual(str_contains($htmlCheckbox, '<input type="checkbox" id="checkboxId" name="terms"'), true, "checkbox génère correctement la balise <input>");
$tester->assertEqual(str_contains($htmlCheckbox, 'checked'), true, "checkbox ajoute correctement l’attribut checked");
$tester->assertEqual(str_contains($htmlCheckbox, '<label class="form-check-label"'), true, "checkbox inclut correctement la balise label");

// Test de méthode submit avec un bouton AJAX
$form->ajax = true;
$htmlSubmitAjax = $form->submit('Envoyer');
$tester->assertEqual(str_contains($htmlSubmitAjax, '<a href="javascript:void(0)"'), true, "submit génère correctement un bouton pour soumission AJAX");
$tester->assertEqual(str_contains($htmlSubmitAjax, 'onclick="submitAjaxForm'), true, "submit inclut correctement la fonction JS de soumission AJAX");

$tester->header("Test valueAttrOrBlank()");
// Test clé inexistante
$arr = ['name' => 'John'];
$result = Forms::valueAttrOrBlank($arr, 'age');
$tester->assertEqual($result, "", "Retourne '' quand la clé n'existe pas");
// Test valeurs null ou vides mais clef existe
$arr = [
    'empty_string' => '',
    'null_value' => null
];
$result1 = Forms::valueAttrOrBlank($arr, 'empty_string');
$result2 = Forms::valueAttrOrBlank($arr, 'null_value');
$tester->assertEqual($result1, "value=''", "Retourne value='' pour une chaîne vide");
$tester->assertEqual($result2, "value=''", "Retourne value='' pour une valeur null");
// Test types scalaires supportés
$arr = [
    'integer' => 42,
    'float' => 3.14,
    'string' => 'Hello',
    'boolean' => true
];
$result1 = Forms::valueAttrOrBlank($arr, 'integer');
$result2 = Forms::valueAttrOrBlank($arr, 'float');
$result3 = Forms::valueAttrOrBlank($arr, 'string');
$result4 = Forms::valueAttrOrBlank($arr, 'boolean');

$tester->assertEqual($result1, "value='42'", "Gère correctement les entiers");
$tester->assertEqual($result2, "value='3.14'", "Gère correctement les flottants");
$tester->assertEqual($result3, "value='Hello'", "Gère correctement les chaînes");
$tester->assertEqual($result4, "value='1'", "Gère correctement les booléens");
// Test sécurité XSS
$arr = [
    'xss' => '<script>alert("XSS")</script>',
    'html' => '<p>Hello</p>'
];
$result1 = Forms::valueAttrOrBlank($arr, 'xss');
$result2 = Forms::valueAttrOrBlank($arr, 'xss', true);
$result3 = Forms::valueAttrOrBlank($arr, 'html');
$tester->assertEqual(str_contains($result1, '<script>'), false, "Le contenu XSS est échappé par défaut");
$tester->assertEqual(str_contains($result2, '<script>'), true, "Le contenu n'est pas échappé quand safe=true");
$tester->assertEqual(str_contains($result3, '<p>'), false, "Les balises HTML sont échappées");
// Test types non supportés
$arr = [
    'array' => [],
    'object' => new stdClass()
];
try {
    Forms::valueAttrOrBlank($arr, 'array');
    $tester->assertEqual(true, false, "Devrait lever une exception pour un tableau");
} catch (\Exception $e) {
    $tester->assertEqual(
        $e->getMessage(),
        'This type of var is not supported by valueAttrOrBlank, use scalar',
        "Lève l'exception appropriée pour un tableau"
    );
}

try {
    Forms::valueAttrOrBlank($arr, 'object');
    $tester->assertEqual(true, false, "Devrait lever une exception pour un objet");
} catch (\Exception $e) {
    $tester->assertEqual(
        $e->getMessage(),
        'This type of var is not supported by valueAttrOrBlank, use scalar',
        "Lève l'exception appropriée pour un objet"
    );
}
// on teste si un entité est nulle
$arr = null;
$result1 = Forms::valueAttrOrBlank($arr, 'fakeKey');
$tester->assertEqual($result1, '', "Une entité nulle, retourne '' ");


// Préparation
$validator = new FormValidator();
$forms = new Forms('test.php', $validator);
$GLOBALS['i18n'] = new I18n('fr');

$tester->header("Test errorDiv()");
$result = $forms->errorDiv('champInexistant');
$tester->assertEqual($result, '', "Retourne une chaîne vide quand il n'y a pas d'erreur");
// avec erreur
$validator->addError('email', 'Email invalide');
$result = $forms->errorDiv('email');
$expected = '<div class="text-danger">* Email invalide</div>';
$tester->assertEqual($result, $expected, "Affiche correctement une seule erreur");
// Test errorDiv avec plusieurs erreurs
$validator->clearErrors();
$validator->addError('password', 'Mot de passe trop court');
$validator->addError('password', 'Doit contenir un chiffre');
$result = $forms->errorDiv('password');
$expected = '<div class="text-danger">* Mot de passe trop court</div>' .
    '<div class="text-danger">* Doit contenir un chiffre</div>';
$tester->assertEqual($result, $expected, "Affiche correctement plusieurs erreurs");
// Test errorDiv avec traduction
$validator->clearErrors();
$validator->addError('name', 'NAME_REQUIRED');
$result = $forms->errorDiv('name');
$translated = '<div class="text-danger">* NAME_REQUIRED</div>';
$tester->assertEqual($result, $translated, "Traduit correctement les messages d'erreur");
// Test errorDiv avec caractères spéciaux
$validator->clearErrors();
$validator->addError('field', 'Message avec <script> & caractères spéciaux');
$result = $forms->errorDiv('field');
$tester->assertEqual(str_contains($result, '&lt;script&gt;'), true, "Échappe correctement les balises HTML");
$tester->assertEqual(str_contains($result, '&amp;'), true, "Échappe correctement les caractères spéciaux");

$tester->footer(exit: false);