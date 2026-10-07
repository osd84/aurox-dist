<?php

require_once '../aurox.php';

use OsdAurox\Validator;
use OsdAurox\I18n;
use osd84\BrutalTestRunner\BrutalTestRunner;


$tester = new BrutalTestRunner();
$tester->header(__FILE__);

// Test de la méthode addError et getErrors
$GLOBALS['i18n'] = new I18n('fr');

$rules = [
    'email' => ['type' => 'mail', 'minLength' => 20, 'maxLength' => 25],
    'username' => ['type' => 'varchar', 'notEmpty' => true],
    'lastname' => ['type' => 'varchar', 'len' => [20,25] ], // len est un raccourcis pour minLength + maxLength
];
$data = [
    'email' => 'invalid-email',
    'username' => '',
    'lastname' => 'invalid-name'
];

$tester->header("Test de la méthode combiné");


$validator = new Validator();
$result = $validator->validate($rules, $data);

$tester->assertEqual($result[0]['msg'],  'doit être une email valide', 'email + length : doit être une email valide');
$tester->assertEqual($result[1]['msg'],  'doit contenir entre 20 et 25 caractères', 'email + length : doit contenir minimum 20 caractères');
$tester->assertEqual($result[2]['msg'],  'doit être rempli', 'notEmpty ok');

$tester->header("Test de la méthode len()");
$tester->assertEqual($result[3]['msg'],  'doit contenir entre 20 et 25 caractères', 'lastname + len : doit contenir minimum 20 caractères');


// test notEmpty
$tester->header("Test de la méthode notEmpty()");

$result = $validator->validate(['username' => ['type' => 'varchar', 'notEmpty' => true]], $data);
$tester->assertEqual($result[0]['msg'], 'doit être rempli', 'notEmpty : doit être rempli');

$result = $validator->validate(['username' => ['type' => 'varchar', 'notEmpty' => true]], null);
$tester->assertEqual($result[0]['msg'], 'aucune donnée fournie pour la validation', 'aucune donnée fournie pour la validation');


$tester->header("Test de la méthode length()");
// test length
$result = $validator->validate(['email' => ['type' => 'mail', 'minLength' => 0, 'maxLength' => 10]], $data);
$tester->assertEqual($result[1]['msg'], 'doit contenir maximum 10 caractères', 'length : doit contenir maximum 10 caractères');

$result = $validator->validate(['email' => ['type' => 'mail', 'minLength' => 15]], $data);
$tester->assertEqual($result[1]['msg'], 'doit contenir minimum 15 caractères', 'length : doit contenir minimum 10 caractères');

$result = $validator->validate(['email' => ['type' => 'mail', 'minLength' => 5, 'maxLength' => 10]], $data);
$tester->assertEqual($result[1]['msg'], 'doit contenir entre 5 et 10 caractères', 'length :  doit contenir entre 5 et 10 caractères');

// test required
$tester->header("Test de la méthode required()");

// Test avec une chaîne vide
$result = $validator->validate(['field' => ['type' => 'varchar', 'required' => true]], ['field' => '']);
$tester->assertEqual($result[0]['msg'], 'champ obligatoire', 'required : chaîne vide doit être invalide');
$tester->assertEqual($result[0]['valid'], false, 'required : chaîne vide doit retourner false');

// Test avec une chaîne contenant uniquement des espaces
$result = $validator->validate(['field' => ['type' => 'varchar', 'required' => true]], ['field' => '   ']);
$tester->assertEqual($result[0]['valid'], false, 'required : chaîne avec espaces doit être invalide');

// Test avec une chaîne valide
$result = $validator->validate(['field' => ['type' => 'varchar', 'required' => true]], ['field' => 'valeur']);
$tester->assertEqual(count($result), 0, 'required : chaîne non-vide doit être valide');

// Test avec null
$result = $validator->validate(['field' => ['type' => 'varchar', 'required' => true]], ['field' => null]);
$tester->assertEqual($result[0]['valid'], false, 'required : null doit être invalide');

// Test avec tableau vide
$result = $validator->validate(['field' => ['type' => 'varchar', 'required' => true]], ['field' => []]);
$tester->assertEqual($result[0]['valid'], false, 'required : tableau vide doit être invalide');

// Test avec tableau non-vide
$result = $validator->validate(['field' => ['type' => 'varchar', 'required' => true]], ['field' => ['item']]);
$tester->assertEqual(count($result), 2, 'required : tableau non-vide doit être invalide pour un varchar');

// Test avec nombre
$result = $validator->validate(['field' => ['type' => 'integer', 'required' => true]], ['field' => 0]);
$tester->assertEqual(count($result), 0, 'required : nombre doit être valide');

// Test avec booléen
$result = $validator->validate(['field' => ['type' => 'bool', 'required' => true]], ['field' => false]);
$tester->assertEqual(count($result), 0, 'required : booléen doit être valide');


$tester->header("Test de la méthode stringType()");
$result = $validator->validate(['field' => ['type' => 'varchar']], ['field' => 'test']);
$tester->assertEqual(count($result), 0, 'stringType : chaîne valide doit passer');
$result = $validator->validate(['field' => ['type' => 'text']], ['field' => 'test']);
$tester->assertEqual(count($result), 0, 'stringType : chaîne valide doit passer');

$result = $validator->validate(['field' => ['type' => 'varchar']], ['field' => '']);
$tester->assertEqual(count($result), 0, 'stringType : chaîne vide doit passer');

$result = $validator->validate(['field' => ['type' => 'varchar']], ['field' => 123]);
$tester->assertEqual($result[0]['valid'], false, 'stringType : nombre doit échouer');

$result = $validator->validate(['field' => ['type' => 'varchar']], ['field' => null]);
$tester->assertEqual($result[0]['valid'], false, 'stringType : null doit échouer');

$result = $validator->validate(['field' => ['type' => 'varchar']], ['field' => []]);
$tester->assertEqual($result[0]['valid'], false, 'stringType : tableau doit échouer');

// Tests pour intType()
$tester->header("Test de la méthode intType()");

$result = $validator->validate(['field' => ['type' => 'integer']], ['field' => 123]);
$tester->assertEqual(count($result), 0, 'intType : entier valide doit passer');

$result = $validator->validate(['field' => ['type' => 'integer']], ['field' => 0]);
$tester->assertEqual(count($result), 0, 'intType : zéro doit passer');

$result = $validator->validate(['field' => ['type' => 'integer']], ['field' => -123]);
$tester->assertEqual(count($result), 0, 'intType : entier négatif doit passer');

// Le validateur ne convertit pas : une chaine sur un champ entier est une
// erreur de type. Le cast du formulaire est fait par BrutalCrud::readPost().
$result = $validator->validate(['field' => ['type' => 'integer']], ['field' => "123"]);
$tester->assertEqual($result[0]['valid'], false, 'intType : chaîne numérique doit échouer');

$result = $validator->validate(['field' => ['type' => 'integer', 'nullable' => True]], ['field' => null]);
$tester->assertEqual(count($result), 0, 'intType : null accepté sur un champ nullable');

$result = $validator->validate(['field' => ['type' => 'integer']], ['field' => 12.3]);
$tester->assertEqual($result[0]['valid'], false, 'intType : float doit échouer');

// nullable remplace optional, optional reste accepte
$tester->header("Test de nullable et de la retro-compatibilite optional");

$result = $validator->validate(['field' => ['type' => 'varchar', 'nullable' => True]], ['field' => null]);
$tester->assertEqual(count($result), 0, 'nullable : null accepté');

$result = $validator->validate(['field' => ['type' => 'varchar', 'optional' => True]], ['field' => null]);
$tester->assertEqual(count($result), 0, 'optional : toujours accepté, alias de nullable');

$result = $validator->validate(['field' => ['type' => 'varchar']], ['field' => null]);
$tester->assertEqual(count($result) > 0, true, 'ni nullable ni optional : null refusé');

$result = $validator->validate(['field' => ['type' => 'varchar', 'nullable' => True, 'required' => True]], ['field' => null]);
$tester->assertEqual(count($result) > 0, true, 'required l\'emporte sur nullable');

$f = new \OsdAurox\Field('f', ['type' => 'varchar', 'nullable' => True], 'x');
$tester->assertEqual($f->optional, true, 'Field : la propriété optional reste synchronisée sur nullable');

$f = new \OsdAurox\Field('f', ['type' => 'varchar', 'optional' => True], 'x');
$tester->assertEqual($f->nullable, true, 'Field : une règle en optional alimente nullable');

// Un null autorise est une valeur, pas une absence : il doit se retrouver dans
// clean() pour qu'on puisse ecrire un NULL explicite en base.
$tester->header("Test de clean() sur un champ nullable a null");

$validatorNull = new Validator();
$validatorNull->validate(
    [
        'a' => ['type' => 'varchar', 'nullable' => True],
        'b' => ['type' => 'int', 'nullable' => True],
        'c' => ['type' => 'varchar', 'nullable' => True],
    ],
    ['a' => null, 'b' => null, 'c' => 'plein', 'hors_regles' => 'zzz']
);
$cleanNull = $validatorNull->clean();

$tester->assertEqual(array_key_exists('a', $cleanNull), true, 'clean() contient le champ nullable a null');
$tester->assertEqual($cleanNull['a'], null, 'clean() rend bien null et pas une chaine vide');
$tester->assertEqual($cleanNull['b'], null, 'clean() rend null sur un entier nullable');
$tester->assertEqual($cleanNull['c'], 'plein', 'clean() rend la valeur des champs remplis');
$tester->assertEqual(array_key_exists('hors_regles', $cleanNull), false, 'clean() ignore toujours les champs hors regles');

// idem via l'ancien nom
$validatorNull = new Validator();
$validatorNull->validate(['a' => ['type' => 'varchar', 'optional' => True]], ['a' => null]);
$tester->assertEqual(array_key_exists('a', $validatorNull->clean()), true, 'clean() se comporte pareil avec optional');

// un champ en erreur reste exclu
$validatorNull = new Validator();
$validatorNull->validate(['a' => ['type' => 'int', 'nullable' => True]], ['a' => 'pas un entier']);
$tester->assertEqual(array_key_exists('a', $validatorNull->clean()), false, 'clean() exclut toujours un champ en erreur');

// un champ requis laisse a null reste exclu
$validatorNull = new Validator();
$validatorNull->validate(['a' => ['type' => 'varchar', 'required' => True]], ['a' => null]);
$tester->assertEqual(array_key_exists('a', $validatorNull->clean()), false, 'clean() exclut un champ requis laisse vide');

// Tests pour floatType()
$tester->header("Test de la méthode floatType()");

$result = $validator->validate(['field' => ['type' => 'float']], ['field' => 12.3]);
$tester->assertEqual(count($result), 0, 'floatType : float valide doit passer');

$result = $validator->validate(['field' => ['type' => 'float']], ['field' => -12.3]);
$tester->assertEqual(count($result), 0, 'floatType : float négatif doit passer');

$result = $validator->validate(['field' => ['type' => 'float']], ['field' => 123]);
$tester->assertEqual($result[0]['valid'], false, 'floatType : entier doit échouer');

$result = $validator->validate(['field' => ['type' => 'float']], ['field' => "12.3"]);
$tester->assertEqual($result[0]['valid'], false, 'floatType : chaîne numérique doit échouer');

// Tests pour min()
$tester->header("Test de la méthode min()");

$result = $validator->validate(['field' => ['type' => 'integer', 'min' => 10]], ['field' => 15]);
$tester->assertEqual(count($result), 0, 'min : nombre supérieur doit passer');

$result = $validator->validate(['field' => ['type' => 'integer', 'min' => 10]], ['field' => 10]);
$tester->assertEqual(count($result), 0, 'min : nombre égal doit passer');

$result = $validator->validate(['field' => ['type' => 'integer', 'min' => 10]], ['field' => 5]);
$tester->assertEqual($result[0]['valid'], false, 'min : nombre inférieur doit échouer');

$result = $validator->validate(['field' => ['type' => 'float', 'min' => 10.5]], ['field' => 10.6]);
$tester->assertEqual(count($result), 0, 'min : float supérieur doit passer');

$result = $validator->validate(['field' => ['type' => 'integer', 'min' => 10]], ['field' => "abc"]);
$tester->assertEqual($result[0]['valid'], false, 'min : chaîne non numérique doit échouer');

// Tests pour max()
$tester->header("Test de la méthode max()");

$result = $validator->validate(['field' => ['type' => 'integer', 'max' => 10]], ['field' => 5]);
$tester->assertEqual(count($result), 0, 'max : nombre inférieur doit passer');

$result = $validator->validate(['field' => ['type' => 'integer', 'max' => 10]], ['field' => 10]);
$tester->assertEqual(count($result), 0, 'max : nombre égal doit passer');

$result = $validator->validate(['field' => ['type' => 'integer', 'max' => 10]], ['field' => 15]);
$tester->assertEqual($result[0]['valid'], false, 'max : nombre supérieur doit échouer');

$result = $validator->validate(['field' => ['type' => 'float', 'max' => 10.5]], ['field' => 10.4]);
$tester->assertEqual(count($result), 0, 'max : float inférieur doit passer');

$result = $validator->validate(['field' => ['type' => 'integer', 'max' => 10]], ['field' => "abc"]);
$tester->assertEqual($result[0]['valid'], false, 'max : chaîne non numérique doit échouer');

// Tests pour startWith()
$tester->header("Test de la méthode startWith()");

$result = $validator->validate(['field' => ['type' => 'varchar', 'startWith' => 'test']], ['field' => 'test123']);
$tester->assertEqual(count($result), 0, 'startWith : correspondance exacte doit passer');

$result = $validator->validate(['field' => ['type' => 'varchar', 'startWith' => 'test']], ['field' => 'abc123']);
$tester->assertEqual($result[0]['valid'], false, 'startWith : sans correspondance doit échouer');

$result = $validator->validate(['field' => ['type' => 'varchar', 'startWith' => 'test']], ['field' => 'test123']);
$tester->assertEqual(count($result), 0, 'startWith : insensible à la casse doit passer');

$result = $validator->validate(['field' => ['type' => 'varchar', 'startWith' => 'Test', 'startWithCaseSensitive' => true]], ['field' => 'test123']);
$tester->assertEqual($result[0]['valid'], false, 'startWith : sensible à la casse doit échouer');

$result = $validator->validate(['field' => ['type' => 'integer', 'startWith' => 'test', 'startWithCaseSensitive' => true]], ['field' => 123]);
$tester->assertEqual($result[0]['valid'], false, 'startWith : non-string doit échouer');

$exceptionCaught = false;
try {
    $result = $validator->validate(['field' => ['type' => 'varchar', 'startWith' => '']], ['field' => 'test']);
} catch (Exception $e) {
    $exceptionCaught = true;
}
$tester->assertEqual($exceptionCaught, true, 'startWith : sans préfixe vide doit lever une erreur');
unset($exceptionCaught);

// Tests combinés
$tester->header("Tests combinés");


$rules = ['field' => ['type' => 'integer', 'required' => true, 'min' => 0, 'max' => 100]];
$result = $validator->validate($rules, ['field' => 50]);
$tester->assertEqual(count($result), 0, 'combinaison : valeur valide doit passer');

$rules = ['field' => ['type' => 'varchar', 'required' => true, 'startWith' => 'test']];
$result = $validator->validate($rules, ['field' => 'test123']);
$tester->assertEqual(count($result), 0, 'combinaison : chaîne valide doit passer');


// Tests pour positive()
$tester->header("Test de la méthode positive()");

$result = $validator->validate(['field' => ['type' => 'integer', 'positive' => true]], ['field' => 15]);
$tester->assertEqual(count($result), 0, 'positive : nombre positif doit passer');

$result = $validator->validate(['field' => ['type' => 'integer', 'positive' => true]], ['field' => 0]);
$tester->assertEqual($result[0]['valid'], false, 'positive : zéro doit échouer');

$result = $validator->validate(['field' => ['type' => 'integer', 'positive' => true]], ['field' => -5]);
$tester->assertEqual($result[0]['valid'], false, 'positive : nombre négatif doit échouer');

$result = $validator->validate(['field' => ['type' => 'float', 'positive' => true]], ['field' => 10.5]);
$tester->assertEqual(count($result), 0, 'positive : float positif doit passer');

$result = $validator->validate(['field' => ['type' => 'varchar', 'positive' => true]], ['field' => "15"]);
$tester->assertEqual(count($result), 0, 'positive : chaîne numérique positive doit passer');

$result = $validator->validate(['field' => ['type' => 'varchar', 'positive' => true]], ['field' => "abc"]);
$tester->assertEqual($result[0]['valid'], false, 'positive : chaîne non numérique doit échouer');

// Tests pour date()
$tester->header("Test de la méthode date()");

$result = $validator->validate(['field' => ['type' => 'date']], ['field' => '2024-03-13']);
$tester->assertEqual(count($result), 0, 'date : format Y-m-d valide doit passer');

$result = $validator->validate(['field' => ['type' => 'date']], ['field' => '2024-13-13']);
$tester->assertEqual($result[0]['valid'], false, 'date : mois invalide doit échouer');

$result = $validator->validate(['field' => ['type' => 'date', 'dateFormat' => 'd/m/Y']], ['field' => '13/03/2024']);
$tester->assertEqual(count($result), 0, 'date : format d/m/Y valide doit passer');

$result = $validator->validate(['field' => ['type' => 'date']], ['field' => '2024-03-13 14:30:00']);
$tester->assertEqual($result[0]['valid'], false, 'date : datetime dans date doit échouer');

$result = $validator->validate(['field' => ['type' => 'date']], ['field' => "invalid-date"]);
$tester->assertEqual($result[0]['valid'], false, 'date : format invalide doit échouer');

$result = $validator->validate(['field' => ['type' => 'date']], ['field' => 12345]);
$tester->assertEqual($result[0]['valid'], false, 'date : nombre doit échouer');

// Tests pour dateTime()
$tester->header("Test de la méthode dateTime()");

$result = $validator->validate(['field' => ['type' => 'datetime']], ['field' => "2024-03-13 14:30:00"]);
$tester->assertEqual(count($result), 0, 'dateTime : format Y-m-d H:i:s valide doit passer');

$result = $validator->validate(['field' => ['type' => 'datetime']], ['field' => "2024-03-13"]);
$tester->assertEqual($result[0]['valid'], false, 'dateTime : date sans heure doit échouer');

$result = $validator->validate(['field' => ['type' => 'datetime', 'dateTimeFormat' => 'd/m/Y H:i']], ['field' => "13/03/2024 14:30"]);
$tester->assertEqual(count($result), 0, 'dateTime : format personnalisé valide doit passer');

$result = $validator->validate(['field' => ['type' => 'datetime']], ['field' => "2024-03-13 25:00:00"]);
$tester->assertEqual($result[0]['valid'], false, 'dateTime : heure invalide doit échouer');

$result = $validator->validate(['field' => ['type' => 'datetime']], ['field' => "invalid-datetime"]);
$tester->assertEqual($result[0]['valid'], false, 'dateTime : format invalide doit échouer');

$result = $validator->validate(['field' => ['type' => 'datetime']], ['field' => 12345]);
$tester->assertEqual($result[0]['valid'], false, 'dateTime : nombre doit échouer');


// Tests pour inArray()
$tester->header("Test de la méthode inArray()");

// Test avec des valeurs simples
$values = [1, 2, 3, 4, 5];
$result = $validator->validate(['field' => ['type' => 'integer', 'inArray' => $values]], ['field' => 3]);
$tester->assertEqual(count($result), 0, 'inArray : valeur présente doit passer');

$result = $validator->validate(['field' => ['type' => 'integer', 'inArray' => $values]], ['field' => 99]);
$tester->assertEqual($result[0]['valid'], false, 'inArray : valeur absente doit échouer');
$tester->assertEqual($result[0]['msg'], 'must be one of the following values : 1, 2, 3, 4, 5', 'inArray : message d\'erreur correct');

// Test avec des chaînes
$fruits = ['pomme', 'poire', 'banane'];
$result = $validator->validate(['field' => ['type' => 'varchar', 'inArray' => $fruits]], ['field' => 'poire']);
$tester->assertEqual(count($result), 0, 'inArray : chaîne présente doit passer');

$result = $validator->validate(['field' => ['type' => 'varchar', 'inArray' => $fruits]], ['field' => 'orange']);
$tester->assertEqual($result[0]['valid'], false, 'inArray : chaîne absente doit échouer');

// Test avec tableau vide
$result = $validator->validate(['field' => ['type' => 'varchar', 'inArray' => []]], ['field' => 'test']);
$tester->assertEqual($result[0]['valid'], false, 'inArray : tableau vide doit échouer');

// Test avec valeur null
$nullableValues = ['test', null, 1];
$result = $validator->validate(['field' => ['type' => 'varchar', 'optional' => true,  'inArray' => $nullableValues]], ['field' => null]);
$tester->assertEqual(count($result), 0, 'inArray : valeur null présente doit passer');

// Tests combinés
$rules = ['field' => ['type' => 'varchar', 'required' => true, 'inArray' => ['pomme', 'poire', 'banane']]];
$result = $validator->validate($rules, ['field' => 'pomme']);
$tester->assertEqual(count($result), 0, 'combinaison : valeur valide doit passer');

$rules = ['field' => ['type' => 'varchar', 'required' => true, 'inArray' => ['pomme', 'poire', 'banane']]];
$result = $validator->validate($rules, ['field' => '']);
$tester->assertEqual($result[0]['valid'], false, 'combinaison : valeur vide doit échouer');



// Tests pour optional()
$tester->header("Test de la méthode optional()");

// Test avec une chaîne vide
$result = $validator->validate(['field' => ['type' => 'varchar', 'optional' => true, 'required' => true]], ['field' => '']);
$tester->assertEqual(count($result), 1, 'optional + required : doit bloquer, required est plus fort');

// Test avec null
$result = $validator->validate(['field' => ['type' => 'varchar', 'optional' => true, 'notEmpty' => true]], ['field' => null]);
$tester->assertEqual(count($result), 0, 'optional : null doit passer');

// Test avec une valeur valide
$result = $validator->validate(['field' => ['type' => 'mail', 'optional' => true, 'notEmpty' => true]], ['field' => 'test@example.com']);
$tester->assertEqual(count($result), 0, 'optional : email valide doit passer');

// Test avec valeur invalide (ne doit pas être ignoré)
$result = $validator->validate(['field' => ['type' => 'mail', 'optional' => true, 'notEmpty' => true]], ['field' => 'invalid-email']);
$tester->assertEqual($result[0]['valid'], false, 'optional : email invalide ne doit pas passer');

// Test combiné avec plusieurs validateurs
$result = $validator->validate(['field' => ['type' => 'varchar', 'optional' => true, 'notEmpty' => true, 'minLength' => 3, 'maxLength' => 10]], ['field' => '']);
$tester->assertEqual(count($result), 2, 'optional : champ vide avec multiples validateurs ne doit pas passer');

$result = $validator->validate(['field' => ['type' => 'varchar', 'optional' => true, 'notEmpty' => true, 'minLength' => 3, 'maxLength' => 10]], ['field' => null]);
$tester->assertEqual(count($result), 0, 'optional : champ vide avec multiples validateurs ne doit passer car null ');


// Test avec une valeur non vide mais invalide
$result = $validator->validate(['field' => ['type' => 'varchar', 'optional' => true, 'notEmpty' => true, 'minLength' => 3, 'maxLength' => 10]], ['field' => 'ab']);
$tester->assertEqual($result[0]['valid'], false, 'optional : valeur non vide invalide ne doit pas passer');

// Test avec tableau vide
$result = $validator->validate(['field' => ['type' => 'varchar', 'optional' => true, 'notEmpty' => true]], ['field' => []]);
$tester->assertEqual(count($result), 3, 'optional : tableau vide ne doit pas passer');

// Test avec chaîne contenant uniquement des espaces
$result = $validator->validate(['field' => ['type' => 'varchar', 'optional' => true, 'notEmpty' => true]], ['field' => '   ']);
$tester->assertEqual(count($result), 1, 'optional : chaîne avec espaces ne doit pas passer');

// Test avec chaîne vide
$result = $validator->validate(['field' => ['type' => 'varchar', 'optional' => true, 'notEmpty' => true]], ['field' => '']);
$tester->assertEqual(count($result), 1, 'optional : chaîne vide doit pas passer');

$result = $validator->validate(['field' => ['type' => 'varchar', 'optional' => true, 'notEmpty' => true]], ['field' => null]);
$tester->assertEqual(count($result), 0, 'optional : chaîne null doit passer');


// Test de alpha
$tester->header("Test de la méthode validateAlpha()");

$result = $validator->validate(['field' => ['type' => 'varchar', 'alpha' => true, 'notEmpty' => true]], ['field' => 'not @ Aplha']);
$tester->assertEqual($result[0]['valid'], false, 'alpha : valeur non alpha doit échouer');

$result = $validator->validate(['field' => ['type' => 'varchar', 'alpha' => true, 'notEmpty' => true]], ['field' => 'not Aplha']);
$tester->assertEqual($result[0]['valid'], false, 'alpha : valeur non alpha doit échouer');

$result = $validator->validate(['field' => ['type' => 'varchar', 'alpha' => true, 'notEmpty' => true]], ['field' => 'IsOkAplha']);
$tester->assertEqual(count($result), 0, 'alpha : valeur alpha doit passer');



$tester->header("Test de la méthode validateNumericString()");

$result = $validator->validate(['field' => ['type' => 'varchar', 'numericString' => true, 'notEmpty' => true]], ['field' => 'not @ Aplha']);
$tester->assertEqual($result[0]['valid'], false, 'numericString : valeur non numericString doit échouer');

$result = $validator->validate(['field' => ['type' => 'varchar', 'numericString' => true, 'notEmpty' => true]], ['field' => 'not Aplha']);
$tester->assertEqual($result[0]['valid'], false, 'numericString : valeur non numericString doit échouer');

$result = $validator->validate(['field' => ['type' => 'varchar', 'numericString' => true, 'notEmpty' => true]], ['field' => 'not 156']);
$tester->assertEqual($result[0]['valid'], false, 'numericString : valeur non numericString doit échouer');

$result = $validator->validate(['field' => ['type' => 'varchar', 'numericString' => true, 'notEmpty' => true]], ['field' => '45 156']);
$tester->assertEqual($result[0]['valid'], false, 'numericString : valeur non numericString doit échouer');

$result = $validator->validate(['field' => ['type' => 'varchar', 'numericString' => true, 'notEmpty' => true]], ['field' => '123 ']);
$tester->assertEqual(count($result), 0, 'numericString : valeur numerique en string doit passer car Validator va trim()');

// Test de la méthode sanitizedDatas()
$tester->header("Test de l'attribut clean()");

$result = $validator->validate(
    [
        'field' => ['type' => 'varchar', 'numericString' => true, 'notEmpty' => true],
        'field2' => ['type' => 'integer'],
        'field3' => ['type' => 'integer'],
        'field4' => ['type' => 'varchar'],
        'field5' => ['type' => 'html'],
    ],
    ['field' => '123 ', 'field2' => '123', 'field3' => 123, 'field4' => '<span>test</span>', 'field5' => '<script>alert("test")</script>']);
$arrayCleaned = $validator->clean();
$tester->assertEqual($arrayCleaned['field'], '123', 'le validator a bien trim() le champ');
$tester->assertEqual(array_key_exists('field2', $arrayCleaned), false, 'le validator a bien exclu ce champ');
$tester->assertEqual($arrayCleaned['field3'], 123, 'le validator a bien trim() le champ');
$tester->assertEqual(array_key_exists('field4', $arrayCleaned), false, 'le validator a bien exclu ce champ');
$tester->assertEqual(array_key_exists('field5', $arrayCleaned), true, 'le validator a bien accepté ce champ');

$tester->header("Test alias integer");
$result = $validator->validate(
    [
        'field2' => ['type' => 'integer'],
        'field3' => ['type' => 'int'],
    ],
    ['field2' => 123, 'field3' => 123]);
$arrayCleaned = $validator->clean();
$tester->assertEqual($arrayCleaned['field2'], 123, 'type integer ok');
$tester->assertEqual($arrayCleaned['field3'], $arrayCleaned['field2'], 'type int est un alias de integer');

$tester->header("Test alias bool");
$result = $validator->validate(
    [
        'field2' => ['type' => 'bool'],
        'field3' => ['type' => 'boolean'],
    ],
    ['field2' => true, 'field3' => true]);
$arrayCleaned = $validator->clean();
$tester->assertEqual($arrayCleaned['field2'], 123, 'type bool ok');
$tester->assertEqual($arrayCleaned['field3'], $arrayCleaned['field2'], 'type boolean est un alias de bool');



$tester->footer(exit: false);