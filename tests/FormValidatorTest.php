<?php

require_once '../aurox.php';

use OsdAurox\FormValidator;
use OsdAurox\I18n;
use OsdAurox\Validator;
use osd84\BrutalTestRunner\BrutalTestRunner;


$tester = new BrutalTestRunner();
$tester->header(__FILE__);

// Test de la méthode addError et getErrors
$validator = new FormValidator();
$tester->header("Test addError");
$validator->addError('email', 'Email invalide');
$validator->addError('username', 'Nom d\'utilisateur requis');
$tester->assertEqual(str_contains(json_encode($validator->getErrors()), 'Email invalide'), true, "Ajout et récupération des erreurs fonctionne correctement pour 'email'");
$tester->assertEqual(str_contains(json_encode($validator->getErrors()), 'Nom d\'utilisateur requis'), true, "Ajout et récupération des erreurs fonctionne correctement pour 'username'");

// Test de la méthode hasError
$tester->header("Test hasError");
$tester->assertEqual($validator->hasError('email'), true, "La méthode hasError retourne true pour 'email' avec des erreurs");
$tester->assertEqual($validator->hasError('password'), false, "La méthode hasError retourne false pour un champ sans erreur");

// Test de la méthode popError
$tester->header("Test popError");
$errorsEmail = $validator->popError('email');
$tester->assertEqual(is_array($errorsEmail), true, "La méthode popError retourne un tableau d'erreurs");
$tester->assertEqual(str_contains(json_encode($errorsEmail), 'Email invalide'), true, "popError retourne correctement les erreurs du champ 'email'");
$tester->assertEqual($validator->hasError('email'), false, "Le champ 'email' n'a plus d'erreur après l'appel à popError");

$tester->header("Test avec une instance de Validator");
$GLOBALS['i18n'] = new I18n('en');
// Test de la méthode validate avec respect/validation
$rules = [
    'email' => ['type' => 'mail'],
    'username' => ['type' => 'varchar', 'notEmpty' => true, 'maxLength' => 100],
];
$data = [
    'email' => 'invalid-email',
    'username' => '',
];
$result = $validator->validate($data, $rules);
$tester->assertEqual($result, false, "La validation échoue lorsque les données ne respectent pas les règles");

$tester->header("Test traduction en");
$errors = $validator->getErrors();
$tester->assertEqual(str_contains($errors['email'][0], 'must be valid email'), true, "La validation retourne une erreur pour un email non valide en");
$tester->assertEqual(str_contains($errors['username'][1], 'must not be empty'), true, "La validation retourne une erreur pour un champ vide en");

// on met en français pour tester les traductions du Core
$tester->header("Test traduction fr");
$GLOBALS['i18n'] = new I18n('fr');
$validator->clearErrors(); // on efface les erreurs précédentes
$result = $validator->validate($data, $rules);
$errors = $validator->getErrors();
$tester->assertEqual(str_contains($errors['email'][0], 'doit être une email valide'), true, "La validation retourne une erreur pour un email non valide fr");
$tester->assertEqual(str_contains($errors['username'][0], 'doit être rempli'), true, "La validation retourne une erreur pour un champ vide fr");


// Test de la méthode isValid avant validation
$tester->header("Test isValid()");
$validator2 = new FormValidator();
try {
    $validator2->isValid();
} catch (Exception $e) {
    $tester->assertEqual(str_contains($e->getMessage(), 'You must call validate() method before calling isValid()'), true, "La méthode isValid déclenche une exception si appelée sans validation");
}

// Test de genApiResult
$tester->header("Test genApiResult() pour reponses Ajax Front");
$GLOBALS['i18n'] = new I18n('en');
$validator->clearErrors(); // on efface les erreurs précédentes
$result = $validator->validate($data, $rules);
$apiResult = $validator->genApiResult();
$tester->assertEqual(get_class($apiResult), OsdAurox\Api::class, "La méthode genApiResult retourne bien une instance d'Api");
$tester->assertEqual(str_contains(json_encode($apiResult), 'Please correct the following errors'), true, "La méthode genApiResult inclut un message global d'erreur");
$apiResultArr = json_decode(json_encode($apiResult), true);
$tester->assertEqual(str_contains($apiResultArr['validators'][0]['msg'], 'must be valid email'), true, "genApiResult inclut correctement les erreurs de validation pour 'email'");
$tester->assertEqual(str_contains($apiResultArr['validators'][1]['msg'], 'must not be empty'), true, "genApiResult inclut correctement les erreurs de validation pour 'username'");


// Test de addErrors
$errors = ['slug' => ['err1', 'err2'], 'name' => ['err3']];
$validator->clearErrors();
$validator->addErrors($errors);
$out = $validator->getErrors();
$tester->assertEqual($out['slug'][0], 'err1', 'addErrors injecte bien les erreurs - 1');
$tester->assertEqual($out['slug'][1], 'err2', 'addErrors injecte bien les erreurs - 2');
$tester->assertEqual($out['name'][0], 'err3', 'addErrors injecte bien les erreurs - 3');

$tester->footer(exit: false);
