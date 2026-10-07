<?php

require_once '../aurox.php';

use OsdAurox\Modal;
use OsdAurox\I18n;
use osd84\BrutalTestRunner\BrutalTestRunner;

$tester = new BrutalTestRunner();
$tester->header(__FILE__);

// Test du constructeur et des valeurs par défaut
$tester->header("Test constructeur Modal");
$modal = new Modal('Titre Test', 'Message Test');
$tester->assertEqual($modal->title, 'Titre Test', "La propriété title est correctement définie");
$tester->assertEqual($modal->msg, 'Message Test', "La propriété msg est correctement définie");
$tester->assertEqual($modal->type, 'info', "La propriété type est 'info' par défaut");
$tester->assertEqual($modal->template, 'modal.php', "Le template par défaut est 'modal.php'");
$tester->assertEqual($modal->id, 'modal-default', "L'ID par défaut est 'modal-default'");
$tester->assertEqual($modal->class, 'modal fade', "La classe par défaut est 'modal fade'");
$tester->assertEqual($modal->showBtn, true, "Les boutons sont affichés par défaut");
$tester->assertEqual($modal->showClose, true, "Le bouton close est affiché par défaut");
$tester->assertEqual($modal->showInput, false, "Le champ de saisie n'est pas affiché par défaut");

// Test avec type personnalisé valide
$tester->header("Test avec type personnalisé valide");
$modalSuccess = new Modal('Titre Succès', 'Message Succès', 'success');
$tester->assertEqual($modalSuccess->type, 'success', "La propriété type accepte 'success'");

// Test avec type personnalisé invalide
$tester->header("Test avec type personnalisé invalide");
$modalInvalidType = new Modal('Titre', 'Message', 'typeInvalide');
$tester->assertEqual($modalInvalidType->type, 'info', "Un type invalide est remplacé par 'info'");

// Test méthode render
$tester->header("Test méthode render");
$modalRender = new Modal('Titre Render', 'Message Render');
$renderedOutput = $modalRender->render();
$tester->assertEqual(is_string($renderedOutput), true, "La méthode render retourne une chaîne");
$tester->assertEqual(str_contains($renderedOutput, 'Titre Render'), true, "Le rendu contient le titre");
$tester->assertEqual(str_contains($renderedOutput, 'Message Render'), true, "Le rendu contient le message");
$tester->assertEqual(str_contains($renderedOutput, 'modal-default'), true, "Le rendu contient l'ID par défaut");

// Test méthode newModal
$tester->header("Test méthode newModal");
$newModalOutput = Modal::newModal('Nouveau Modal', 'Message du nouveau modal', 'warning');
$tester->assertEqual(is_string($newModalOutput), true, "La méthode newModal retourne une chaîne");
$tester->assertEqual(str_contains($newModalOutput, 'Nouveau Modal'), true, "Le rendu contient le titre personnalisé");
$tester->assertEqual(str_contains($newModalOutput, 'Message du nouveau modal'), true, "Le rendu contient le message personnalisé");

// Test méthode newLoader
$tester->header("Test méthode newLoader");
$GLOBALS['i18n'] = new I18n('en'); // Définir la langue pour les traductions
$loaderOutput = Modal::newLoader();
$tester->assertEqual(is_string($loaderOutput), true, "La méthode newLoader retourne une chaîne");
$tester->assertEqual(str_contains($loaderOutput, 'Loading...'), true, "Le loader contient le titre par défaut");
$tester->assertEqual(str_contains($loaderOutput, 'Please wait'), true, "Le loader contient le message par défaut");
$tester->assertEqual(str_contains($loaderOutput, 'modal-loader'), true, "Le loader utilise l'ID 'modal-loader'");

// Test méthode newLoader avec paramètres personnalisés
$tester->header("Test newLoader personnalisé");
$customLoaderOutput = Modal::newLoader('Chargement personnalisé', 'Veuillez patienter...');
$tester->assertEqual(str_contains($customLoaderOutput, 'Chargement personnalisé'), true, "Le loader contient le titre personnalisé");
$tester->assertEqual(str_contains($customLoaderOutput, 'Veuillez patienter...'), true, "Le loader contient le message personnalisé");

// Test méthode newPrompt
$tester->header("Test méthode newPrompt");
$promptOutput = Modal::newPrompt();
$tester->assertEqual(is_string($promptOutput), true, "La méthode newPrompt retourne une chaîne");
$tester->assertEqual(str_contains($promptOutput, 'Please complete the form below'), true, "Le prompt contient le titre par défaut");
$tester->assertEqual(str_contains($promptOutput, 'Enter the required information'), true, "Le prompt contient le message par défaut");
$tester->assertEqual(str_contains($promptOutput, 'modal-prompt'), true, "Le prompt utilise l'ID 'modal-prompt'");

// Test méthode newPrompt avec paramètres personnalisés
$tester->header("Test newPrompt personnalisé");
$customPromptOutput = Modal::newPrompt('Formulaire', 'Entrez vos informations :', 'info', null, 'Envoyer', 'Annuler');
$tester->assertEqual(str_contains($customPromptOutput, 'Formulaire'), true, "Le prompt contient le titre personnalisé");
$tester->assertEqual(str_contains($customPromptOutput, 'Entrez vos informations :'), true, "Le prompt contient le message personnalisé");
$tester->assertEqual(str_contains($customPromptOutput, 'Envoyer'), true, "Le prompt contient le texte personnalisé pour le bouton d'acceptation");
$tester->assertEqual(str_contains($customPromptOutput, 'Annuler'), true, "Le prompt contient le texte personnalisé pour le bouton d'annulation");

// Test avec changement de langue
$tester->header("Test traduction fr");
$GLOBALS['i18n'] = new I18n('fr');
$frenchModalOutput = Modal::newModal('Titre', 'Message');
$tester->assertEqual(str_contains($frenchModalOutput, 'Accepter'), true, "Les boutons sont traduits en français");

$frenchPromptOutput = Modal::newPrompt();
$tester->assertEqual(str_contains($frenchPromptOutput, 'Veuillez compléter le formulaire ci-dessous'), true, "Le titre du prompt est traduit en français");
$tester->assertEqual(str_contains($frenchPromptOutput, 'Veuillez entrer les informations requises'), true, "Le message du prompt est traduit en français");

$tester->footer(exit: false);