<?php

require_once '../aurox.php';

use OsdAurox\Sec;
use OsdAurox\Cache;
use osd84\BrutalTestRunner\BrutalTestRunner;

$tester = new BrutalTestRunner();
$tester->header(__FILE__);


$r = Sec::isLoggedBool();
$tester->assertEqual(false, $r, "pas connecté");

$_SESSION['user'] = ['id' => 1, 'role' => 'user']; // mock d'un user connecté

$r = Sec::isLoggedBool();
$tester->assertEqual(true, $r, "connecté");

$r = Sec::isAdminBool();
$tester->assertEqual(false, $r, "pas admin");

$_SESSION['user']['role'] = 'admin';
$r = Sec::isAdminBool();
$tester->assertEqual(true, $r, "admin");

$tester->header("Test de sqlIdent()");
$tester->assertEqual(Sec::sqlIdent('title'), 'title', 'sqlIdent : colonne simple ok');
$tester->assertEqual(Sec::sqlIdent('created_at2'), 'created_at2', 'sqlIdent : underscore et chiffre ok');
$tester->assertEqual(Sec::sqlIdent('posts.title'), 'posts.title', 'sqlIdent : table.colonne ok');
foreach (['', 'id; DROP TABLE users', 'title DESC', 'id,title', '1=1', 'a.b.c', '`title`', '2col', 'title--', 'SLEEP(5)'] as $bad) {
    try {
        Sec::sqlIdent($bad);
        $tester->assertEqual(0, 1, "sqlIdent : doit refuser '$bad'");
    } catch (InvalidArgumentException $e) {
        $tester->assertEqual(1, 1, "sqlIdent : refuse '$bad'");
    }
}

$tester->header("Test de getRealIpAddr()");
$_SERVER['REMOTE_ADDR'] = '203.0.113.7';
$_SERVER['HTTP_X_FORWARDED_FOR'] = '127.0.0.1, 198.51.100.1';
$tester->assertEqual(Sec::getRealIpAddr(), '203.0.113.7', 'getRealIpAddr : X-Forwarded-For ignoré, REMOTE_ADDR seul');
$_SERVER['REMOTE_ADDR'] = 'pas-une-ip';
$tester->assertEqual(Sec::getRealIpAddr(), '', 'getRealIpAddr : REMOTE_ADDR invalide => vide');
unset($_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['REMOTE_ADDR']);

$tester->header("Test de needChangePassword()");
// test de needChangePassword
$pdo = \OsdAurox\Dbo::getPdo();
$pdo->exec("UPDATE users SET password_change = 1 WHERE id = 2");
$_SESSION['user'] = ['id' => 2, 'role' => 'user', 'password_change' => 1]; // mock d'un user connecté
$r = Sec::needChangePassword($pdo);
$tester->assertEqual(true, $r, "doit changer de mot de passe");

$_SESSION['user'] = ['id' => 1, 'role' => 'user', 'password_change' => 0]; // mock d'un user connecté
$r = Sec::needChangePassword($pdo);
$tester->assertEqual(False, $r, "ne doit pas changer de mot de passe");

$pdo->exec("UPDATE users SET password_change = 0 WHERE id = 2");


$_SESSION['user'] = ['id' => 3, 'role' => 'user']; // mock d'un user connecté
$r = Sec::needChangePassword($pdo);
$tester->assertEqual(False, $r, "clef iconnue");

$tester->header("Test de la méthode getUserIdOrDie()");
// Test sans session utilisateur
$_SESSION = [];
try {
    Sec::getUserIdOrDie();
    $tester->assertEqual(true, false, "Doit lever une exception si pas de session user");
} catch (\Exception $e) {
    $tester->assertEqual($e->getMessage(), 'User not logged', "Message d'erreur correct pour absence de session");
}
// Test avec session utilisateur mais sans ID
$_SESSION['user'] = ['role' => 'user'];
try {
    Sec::getUserIdOrDie();
    $tester->assertEqual(true, false, "Doit lever une exception si pas d'ID utilisateur");
} catch (\Exception $e) {
    $tester->assertEqual($e->getMessage(), 'User not logged', "Message d'erreur correct pour absence d'ID");
}
// Test avec ID utilisateur valide
$_SESSION['user'] = ['id' => 42, 'role' => 'user'];
$userId = Sec::getUserIdOrDie();
$tester->assertEqual(42, $userId, "Doit retourner l'ID utilisateur correct");
// Test avec ID utilisateur sous forme de chaîne (doit être converti en entier)
$_SESSION['user'] = ['id' => '123', 'role' => 'user'];
$userId = Sec::getUserIdOrDie();
$tester->assertEqual(123, $userId, "Doit convertir l'ID en entier");

$tester->header("Test hArrayKey()");
$result = Sec::hArrayKey([], 'name');
$tester->assertEqual($result, [], "Devrait retourner un tableau vide pour un tableau vide");
// Test avec clé inexistante
$data = [['age' => 25]];
$result = Sec::hArrayKey($data, 'name');
$tester->assertEqual($result, [], "Devrait retourner un tableau vide si la clé n'existe pas");
// Test avec données simples
$data = [
    ['name' => 'John'],
    ['name' => 'Jane'],
    ['name' => 'Bob']
];
$result = Sec::hArrayKey($data, 'name');
$tester->assertEqual($result, ['John', 'Jane', 'Bob'], "Devrait extraire correctement les valeurs");
// Test avec contenus HTML
$data = [
    ['content' => '<p>Hello</p>'],
    ['content' => '<script>alert("XSS")</script>'],
    ['content' => '<b>Bold</b>']
];
$result = Sec::hArrayKey($data, 'content');
$tester->assertEqual(
    in_array('<p>', $result),
    false,
    "Les balises HTML devraient être supprimées"
);
$tester->assertEqual(
    in_array('<script>', $result),
    false,
    "Les balises script devraient être supprimées"
);
// Test avec valeurs null ou vides
$data = [
    ['value' => null],
    ['value' => ''],
    ['value' => 'test']
];
$result = Sec::hArrayKey($data, 'value');
$tester->assertEqual(
    $result,
    ['', '', 'test'],
    "Les valeurs null et vides devraient être converties en chaînes vides"
);

// Test avec types mixtes
$data = [
    ['value' => 123],
    ['value' => true],
    ['value' => 'string'],
    ['value' => 3.14]
];
$result = Sec::hArrayKey($data, 'value');
$expected = ['123', '1', 'string', '3.14'];
$tester->assertEqual(
    $result,
    $expected,
    "Devrait gérer correctement différents types de données"
);


// Test tableau vide
$tester->header("Test de hArrayInt()");
$result = Sec::hArrayInt([], 'id');
$tester->assertEqual($result, [], "Devrait retourner un tableau vide pour un tableau vide");
// Test données valides
$data = [
    ['id' => '1'],
    ['id' => '42'],
    ['id' => '-5'],
    ['id' => '0']
];
$result = Sec::hArrayInt($data, 'id');
$tester->assertEqual($result, [1, 42, -5, 0], "Devrait convertir correctement les chaînes en entiers");
// Test avec valeurs non numériques
$data = [
    ['id' => 'abc'],
    ['id' => '12.34'],
    ['id' => 'null'],
    ['id' => '']
];
$result = Sec::hArrayInt($data, 'id');
$tester->assertEqual($result, [0, 12, 0, 0], "Devrait convertir les valeurs non numériques en 0");
// Test avec clé inexistante
$data = [
    ['autre' => '1'],
    ['autre' => '2']
];
$result = Sec::hArrayInt($data, 'id');
$tester->assertEqual($result, [], "Devrait retourner un tableau vide si la clé n'existe pas");
// Test avec valeurs mixtes
$data = [
    ['id' => '123'],
    ['id' => '<script>alert(456)</script>'],
    ['id' => ' 789 '],
    ['id' => true],
    ['id' => false]
];
$result = Sec::hArrayInt($data, 'id');
$tester->assertEqual($result, [123, 0, 789, 1, 0], "Devrait gérer correctement les valeurs mixtes");

$tester->header("Test storeReferer() && getReferer()");
unset($_SERVER['REQUEST_URI']);
unset($_SERVER['HTTP_HOST']);
Sec::storeReferer();
$tester->assertEqual(
    isset($_SESSION['previous_url']),
    false,
    "Ne devrait pas stocker d'URL si REQUEST_URI et HTTP_HOST sont manquants"
);
// Test avec REQUEST_URI défini mais HTTP_HOST manquant
$_SERVER['REQUEST_URI'] = '/test-page';
unset($_SERVER['HTTP_HOST']);
Sec::storeReferer();
$tester->assertEqual(
    isset($_SESSION['previous_url']),
    false,
    "Ne devrait pas stocker d'URL si HTTP_HOST est manquant"
);
// Test avec HTTP_HOST défini mais REQUEST_URI manquant
unset($_SERVER['REQUEST_URI']);
$_SERVER['HTTP_HOST'] = 'example.com';
Sec::storeReferer();
$tester->assertEqual(
    isset($_SESSION['previous_url']),
    false,
    "Ne devrait pas stocker d'URL si REQUEST_URI est manquant"
);

// ---- Test 2: storeReferer() avec des valeurs valides ----
// Configuration des valeurs valides
$_SERVER['REQUEST_URI'] = '/test-page';
$_SERVER['HTTP_HOST'] = 'example.com';
$beforeTimestamp = time();
Sec::storeReferer();
$afterTimestamp = time();
// Vérification du stockage correct
$tester->assertEqual(
    isset($_SESSION['previous_url']),
    true,
    "Devrait stocker l'URL dans la session"
);
$tester->assertEqual(
    $_SESSION['previous_url']['url'],
    '/test-page',
    "Devrait stocker l'URL correcte"
);
$tester->assertEqual(
    $_SESSION['previous_url']['host'],
    'example.com',
    "Devrait stocker le host correct"
);
$tester->assertEqual(
    $_SESSION['previous_url']['timestamp'] >= $beforeTimestamp &&
    $_SESSION['previous_url']['timestamp'] <= $afterTimestamp, true,
    "Devrait stocker un timestamp valide"
);
// ---- Test 3: getReferer() quand aucune URL n'est stockée ----
$_SESSION = []; // Réinitialisation de la session
$result = Sec::getReferer();
$tester->assertEqual(
    $result,
    null,
    "Devrait retourner null si aucune URL n'est stockée"
);

// ---- Test 4: getReferer() avec host correspondant ----
// Préparation de la session
$_SESSION['previous_url'] = [
    'url' => '/dashboard',
    'host' => 'example.com',
    'timestamp' => time()
];
$_SERVER['HTTP_HOST'] = 'example.com';
$result = Sec::getReferer();
$tester->assertEqual(
    $result,
    '/dashboard',
    "Devrait retourner l'URL stockée quand le host correspond"
);
$tester->assertEqual(
    isset($_SESSION['previous_url']),
    true,
    "La session ne devrait pas être supprimée après la récupération"
);
// ---- Test 5: getReferer() avec host différent ----
// Préparation de la session
$_SESSION['previous_url'] = [
    'url' => '/dashboard',
    'host' => 'example.com',
    'timestamp' => time()
];
$_SERVER['HTTP_HOST'] = 'autre-domaine.com';
$result = Sec::getReferer();
$tester->assertEqual(
    $result,
    null,
    "Devrait retourner null quand le host ne correspond pas"
);
$tester->assertEqual(
    isset($_SESSION['previous_url']),
    false,
    "La session devrait être supprimée quand le host ne correspond pas"
);

// ---- Test 6: getReferer() avec HTTP_HOST manquant ----
// Préparation de la session
$_SESSION['previous_url'] = [
    'url' => '/dashboard',
    'host' => 'example.com',
    'timestamp' => time()
];
unset($_SERVER['HTTP_HOST']);

$result = Sec::getReferer();
$tester->assertEqual(
    $result,
    null,
    "Devrait retourner null quand HTTP_HOST est manquant"
);
$tester->assertEqual(
    isset($_SESSION['previous_url']),
    false,
    "La session devrait être supprimée quand HTTP_HOST est manquant"
);

// ---- Test 7: Séquence complète storeReferer() puis getReferer() ----
// Stockage initial
$_SERVER['REQUEST_URI'] = '/profile';
$_SERVER['HTTP_HOST'] = 'app.example.com';
Sec::storeReferer();
// Récupération avec le même host
$result = Sec::getReferer();
$tester->assertEqual(
    $result,
    '/profile',
    "Devrait retourner l'URL correcte dans un scénario réel"
);

$tester->header("Test de uuidV4()");
// Test de validation du format d'UUID v4
$uuid = Sec::uuidV4();
$tester->assertEqual(
    preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid),
    1,
    "L'UUID doit suivre le format standard UUID v4"
);
// Test d'unicité - génération de plusieurs UUIDs
$uuids = [];
for ($i = 0; $i < 100; $i++) {
    $uuids[] = Sec::uuidV4();
}
$uniqueUuids = array_unique($uuids);
$tester->assertEqual(
    count($uuids),
    count($uniqueUuids),
    "Tous les UUIDs générés doivent être uniques"
);

// Test de la version et de la variante
$uuid = Sec::uuidV4();
$hexParts = explode('-', $uuid);
$tester->assertEqual(
    substr($hexParts[2], 0, 1),
    '4',
    "Le premier caractère du troisième groupe doit être '4' (version 4)"
);
$tester->assertEqual(
    in_array(substr($hexParts[3], 0, 1), ['8', '9', 'a', 'b']),
    true,
    "Le premier caractère du quatrième groupe doit être 8, 9, a ou b (variante RFC 4122)"
);

// Test de longueur correcte
$tester->assertEqual(
    strlen($uuid),
    36,
    "L'UUID doit avoir une longueur de 36 caractères"
);

// Test que deux appels successifs renvoient des valeurs différentes
$uuid1 = Sec::uuidV4();
$uuid2 = Sec::uuidV4();
$tester->assertEqual(
    $uuid1 !== $uuid2,
    true,
    "Deux appels successifs doivent générer des UUIDs différents"
);

$tester->header("Test de la méthode getParam()");

// Test avec source = 0 (GET)
$_GET['test_param'] = 'test_value';
$result = Sec::getParam('test_param', 'nohtml', 0);
$tester->assertEqual(
    $result,
    'test_value',
    "Devrait récupérer un paramètre depuis GET quand source = 0"
);

// Test avec source = 1 (POST)
$_POST['test_post'] = 'post_value';
$result = Sec::getParam('test_post', 'nohtml', 1);
$tester->assertEqual(
    $result,
    'post_value',
    "Devrait récupérer un paramètre depuis POST quand source = 1"
);

// Test avec source = 2 (REQUEST)
$_REQUEST['test_request'] = 'request_value';
$result = Sec::getParam('test_request', 'nohtml', 2);
$tester->assertEqual(
    $result,
    'request_value',
    "Devrait récupérer un paramètre depuis REQUEST quand source = 2"
);

// Test avec source = 3 (POST puis GET) - POST prioritaire
$_POST['test_both'] = 'post_value';
$_GET['test_both'] = 'get_value';
$result = Sec::getParam('test_both', 'nohtml', 3);
$tester->assertEqual(
    $result,
    'post_value',
    "Devrait prioriser POST quand source = 3 et que le paramètre existe dans POST et GET"
);

// Test avec source = 3 (POST puis GET) - POST absent, GET présent
unset($_POST['test_both']);
$_GET['test_both'] = 'get_value';
$result = Sec::getParam('test_both', 'nohtml', 3);
$tester->assertEqual(
    $result,
    'get_value',
    "Devrait utiliser GET quand source = 3 et que le paramètre n'existe pas dans POST"
);

// Test avec paramètre inexistant
$result = Sec::getParam('inexistant', 'nohtml');
$tester->assertEqual(
    $result,
    null,
    "Devrait retourner null pour un paramètre inexistant"
);

// Test de nettoyage avec type 'int'
$_GET['number'] = '42abc';
$result = Sec::getParam('number', 'int', 0);
$tester->assertEqual(
    $result,
    42,
    "Devrait convertir en entier avec type='int'"
);

// Test de nettoyage avec type 'float'
$_GET['decimal'] = '3,14';
$result = Sec::getParam('decimal', 'float', 0);
$tester->assertEqual(
    $result,
    3.14,
    "Devrait convertir en nombre à virgule flottante avec type='float'"
);

// Test de nettoyage avec type 'alpha'
$_GET['alpha_test'] = 'ABC123 !@#';
$result = Sec::getParam('alpha_test', 'alpha', 0);
$tester->assertEqual(
    $result,
    'ABC',
    "Devrait ne conserver que les caractères alphabétiques avec type='alpha'"
);


// Test de nettoyage avec type 'alphaextra'
$_GET['alphaextra_test'] = 'ABC 123-DEF!@#';
$result = Sec::getParam('alphaextra_test', 'alphaextra', 0);
$tester->assertEqual(
    $result,
    'ABC -DEF',
    "Devrait ne conserver que les caractères alphabétiques, espaces et tirets avec type='alphaextra'"
);

// Test de nettoyage avec type 'aZ09'
$_GET['az09_test'] = 'ABC123!@#';
$result = Sec::getParam('az09_test', 'aZ09', 0);
$tester->assertEqual(
    $result,
    'ABC123',
    "Devrait ne conserver que les caractères alphanumériques avec type='aZ09'"
);

// Test de nettoyage avec type 'aZ09extra'
$_GET['alphaextra_test'] = 'ABC 123-DEF!@#';
$result = Sec::getParam('alphaextra_test', 'aZ09extra', 0);
$tester->assertEqual(
    $result,
    'ABC 123-DEF',
    "Devrait ne conserver que les caractères alphanumérique, espaces et tirets avec type='aZ09extra'"
);

// Test de nettoyage avec type 'aZ09_-'
$_GET['az09_dash_test'] = 'ABC123!@#-_ popo';
$result = Sec::getParam('az09_dash_test', 'aZ09_-', 0);
$tester->assertEqual(
    $result,
    'ABC123-_popo',
    "Devrait ne conserver que les caractères alphanumériques, - et _"
);

// Test de nettoyage avec type 'aZ09_-s'
$_GET['az09_dash_test'] = 'ABC123!@#-_ popo';
$result = Sec::getParam('az09_dash_test', 'aZ09_-s', 0);
$tester->assertEqual(
    $result,
    'ABC123-_ popo',
    "Devrait ne conserver que les caractères alphanumériques, - et _"
);

// Test de nettoyage + lowerCase
$_GET['az09_test'] = 'ABC123!@#';
$result = Sec::getParam('az09_test', 'aZ09', 0, lowerCase: true);;
$tester->assertEqual(
    $result,
    'abc123',
    "Devrait ne conserver que les caractères alphanumériques avec type='aZ09' + lowercase"
);

// Test de nettoyage + uppercase
$_GET['az09_test'] = 'abc123!@#';
$result = Sec::getParam('az09_test', 'aZ09', 0, upperCase: true);;
$tester->assertEqual(
    $result,
    'ABC123',
    "Devrait ne conserver que les caractères alphanumériques avec type='aZ09' + uppercase"
);


// Test de nettoyage avec type '09'
$_GET['alpha_test'] = 'ABC 0123 !@#';
$result = Sec::getParam('alpha_test', '09', 0);
$tester->assertEqual(
    $result,
    '0123',
    "Devrait ne conserver que les caractères numérique sans passer par un int"
);

// Test de nettoyage avec type 'nohtml'
$_GET['html_test'] = '<p>Test</p><script>alert("XSS")</script>';
$result = Sec::getParam('html_test', 'nohtml', 0);
$tester->assertEqual(
    $result,
    'Testalert("XSS")',
    "Devrait supprimer toutes les balises HTML avec type='nohtml'"
);

// Test de nettoyage avec type 'alphanohtml'
$_GET['alphanohtml_test'] = '<p>Test123</p><script>alert("XSS")</script>';
$result = Sec::getParam('alphanohtml_test', 'alphanohtml', 0);
$tester->assertEqual(
    $result,
    'TestalertXSS',
    "Devrait supprimer les balises HTML et ne conserver que les caractères alphabétiques avec type='alphanohtml'"
);

// Test de nettoyage avec type 'restricthtml'
$_GET['restricthtml_test'] = '<p>Test</p><b>Bold</b><script>alert("XSS")</script><strong>Strong</strong>';
$result = Sec::getParam('restricthtml_test', 'restricthtml', 0);
$tester->assertEqual(
    $result,
    'Test<b>Bold</b>alert("XSS")<strong>Strong</strong>',
    "Devrait ne conserver que certaines balises HTML avec type='restricthtml'"
);

// Test avec valeur de tableau (devrait retourner null)
$_GET['array_param'] = ['value1', 'value2'];
$result = Sec::getParam('array_param', 'nohtml', 0);
$tester->assertEqual(
    $result,
    null,
    "Devrait retourner null pour une valeur de type tableau"
);

// Test avec espaces superflus (devrait les supprimer)
$_GET['trimmed'] = '  test avec espaces  ';
$result = Sec::getParam('trimmed', 'nohtml', 0);
$tester->assertEqual(
    $result,
    'test avec espaces',
    "Devrait supprimer les espaces superflus en début et fin de chaîne"
);

// Test avec raw
$_GET['raw_param'] = ' <script>alert("XSS")</script> ';
$result = Sec::getParam('raw_param', 'raw', 0);
$tester->assertEqual(
    $result,
    '<script>alert("XSS")</script>',
    "Devrait retourner la valeur brute sans nettoyage avec type='raw'"
);

// Test avec raw
$_GET['raw_param'] = ' 0 ';
$result = Sec::getParam('raw_param', 'raw', 0);
$tester->assertEqual(
    $result,
    '0',
    "Devrait retourner la valeur brute sans nettoyage avec type='raw'",
    true
);

// Test avec date_us valide
$_GET['date_param'] = '2024-06-18';
$result = Sec::getParam('date_param', 'date_us', 0);
$tester->assertEqual(
    $result,
    '2024-06-18',
    "Devrait accepter une date US valide avec type='date_us'"
);

// Test avec date_us invalide (mauvais format)
$_GET['date_param'] = '18/06/2024';
$result = Sec::getParam('date_param', 'date_us', 0);
$tester->assertEqual(
    $result,
    '',
    "Devrait rejeter une date au mauvais format avec type='date_us'"
);

// Test avec date_us invalide (date impossible)
$_GET['date_param'] = '2024-02-30';
$result = Sec::getParam('date_param', 'date_us', 0);
$tester->assertEqual(
    $result,
    '',
    "Devrait rejeter une date impossible avec type='date_us'"
);

// Test avec date_us contenant du HTML
$_GET['date_param'] = '<b>2024-06-18</b>';
$result = Sec::getParam('date_param', 'date_us', 0);
$tester->assertEqual(
    $result,
    '2024-06-18',
    "Devrait nettoyer le HTML et accepter la date valide avec type='date_us'"
);

// Test avec datetime_us valide
$_GET['datetime_param'] = '2024-06-18 14:30:00';
$result = Sec::getParam('datetime_param', 'datetime_us', 0);
$tester->assertEqual(
    $result,
    '2024-06-18 14:30:00',
    "Devrait accepter un datetime US valide avec type='datetime_us'"
);

// Test avec datetime_us invalide (mauvais format)
$_GET['datetime_param'] = '18/06/2024 14:30:00';
$result = Sec::getParam('datetime_param', 'datetime_us', 0);
$tester->assertEqual(
    $result,
    '',
    "Devrait rejeter un datetime au mauvais format avec type='datetime_us'"
);

// Test avec datetime_us invalide (date impossible)
$_GET['datetime_param'] = '2024-02-30 14:30:00';
$result = Sec::getParam('datetime_param', 'datetime_us', 0);
$tester->assertEqual(
    $result,
    '',
    "Devrait rejeter un datetime avec date impossible avec type='datetime_us'"
);

// Test avec datetime_us contenant du HTML
$_GET['datetime_param'] = '<i>2024-06-18 14:30:00</i>';
$result = Sec::getParam('datetime_param', 'datetime_us', 0);
$tester->assertEqual(
    $result,
    '2024-06-18 14:30:00',
    "Devrait nettoyer le HTML et accepter le datetime valide avec type='datetime_us'"
);

// Test avec type invalide (devrait retourner null)
$_GET['invalid_type'] = 'test';
try {
    $result = Sec::getParam('invalid_type', 'type_inexistant', 0);
} catch (Exception $e) {
    $result = $e->getMessage();
    $tester->assertEqual($result, "Invalid type: type_inexistant", "Devrait lever une exception pour un type de nettoyage invalide");
}


// Nettoyage des variables globales après les tests
$_GET = [];
$_POST = [];
$_REQUEST = [];

$tester->header("Test de la méthode getAction()");

// Test avec action non définie (devrait retourner la valeur par noaction)
$_GET = [];
$_POST = [];
$result = Sec::getAction();
$tester->assertEqual(
    $result,
    'noaction',
    "Devrait retourner la valeur par défaut 'noaction' quand aucune action n'est définie"
);

// Test avec action définie dans GET
$_GET['action'] = 'list';
$result = Sec::getAction();
$tester->assertEqual(
    $result,
    'list',
    "Devrait récupérer l'action depuis GET quand elle est définie"
);

// Test avec action définie dans POST (prioritaire par défaut)
$_POST['action'] = 'create';
$_GET['action'] = 'show';
$result = Sec::getAction();
$tester->assertEqual(
    $result,
    'create',
    "Devrait prioriser l'action depuis POST quand source = 3"
);

// Test avec source = 0 (GET uniquement)
$_GET['action'] = 'edit';
$_POST['action'] = 'delete';
$result = Sec::getAction('home', 0);
$tester->assertEqual(
    $result,
    'edit',
    "Devrait récupérer l'action depuis GET quand source = 0"
);

// Test avec source = 1 (POST uniquement)
$_GET['action'] = 'show';
$_POST['action'] = 'update';
$result = Sec::getAction('home', 1);
$tester->assertEqual(
    $result,
    'update',
    "Devrait récupérer l'action depuis POST quand source = 1"
);

// Test avec valeur par défaut personnalisée
$_GET = [];
$_POST = [];
$result = Sec::getAction('dashboard');
$tester->assertEqual(
    $result,
    'dashboard',
    "Devrait utiliser la valeur par défaut personnalisée"
);

// Test avec action contenant des caractères spéciaux (devrait être nettoyée)
$_GET['action'] = 'edit-user!@#';
$result = Sec::getAction();
$tester->assertEqual(
    $result,
    'edit-user',
    "Devrait nettoyer l'action selon le type 'alphaextra'"
);

// Test avec action vide (devrait retourner la valeur par défaut)
$_GET['action'] = '';
$result = Sec::getAction();
$tester->assertEqual(
    $result,
    'noaction',
    "Devrait retourner la valeur par défaut quand l'action est vide"
);

// Test avec action nulle (devrait retourner la valeur par défaut)
$_GET['action'] = null;
$result = Sec::getAction();
$tester->assertEqual(
    $result,
    'noaction',
    "Devrait retourner la valeur par défaut quand l'action est null"
);

// Nettoyage après les tests
$_GET = [];
$_POST = [];

// TEST de la méthode setRateLimit()
$tester->header("Test de la méthode setRateLimit()");

// Mock IP et cache
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// Nettoyage du cache avant test
$cache = new Cache('rate_limit');
$key = 'rate_testEndpoint_' . md5('127.0.0.1');
$cache->delete($key);

// Test : premier appel sur endpoint "testEndpoint"
$result = Sec::setRateLimit(2, 3, 'testEndpoint');
$tester->assertEqual($result, true, "Premier appel, la limite ne doit pas être atteinte (endpoint spécifique)");

// Test : deuxième appel sur le même endpoint
$result = Sec::setRateLimit(2, 3, 'testEndpoint');
$tester->assertEqual($result, true, "Deuxième appel, la limite ne doit pas être atteinte (endpoint spécifique)");

// Test : troisième appel sur le même endpoint
$result = Sec::setRateLimit(2, 3, 'testEndpoint');
$tester->assertEqual($result, true, "Troisième appel, la limite ne doit pas être atteinte (endpoint spécifique)");

// Test : quatrième appel sur le même endpoint (limite atteinte)
$result = Sec::setRateLimit(2, 3, 'testEndpoint');
$tester->assertEqual($result, false, "Quatrième appel, la limite doit être atteinte (endpoint spécifique)");

// Test : appel sur un autre endpoint (compteur indépendant)
$result = Sec::setRateLimit(2, 3, 'autreEndpoint');
$tester->assertEqual($result, true, "Premier appel sur un autre endpoint, la limite ne doit pas être atteinte");

// Test : on regarde la limite actuelle
$result = Sec::checkCurrentRateLimit(2, 3, 'autreEndpoint');
$tester->assertEqual(true, true, "Premier check sur un autre endpoint, la limite ne doit pas être atteinte");
$result = Sec::checkCurrentRateLimit(2, 3, 'testEndpoint');
$tester->assertEqual($result, false, "Quatrième appel, la limite doit être atteinte (endpoint spécifique)");


// Test : après expiration (attente 2s), la limite doit être réinitialisée sur 'testEndpoint'
sleep(2);
$result = Sec::setRateLimit(2, 3, 'testEndpoint');
$tester->assertEqual($result, true, "Après expiration, la limite doit être réinitialisée (endpoint spécifique)");

// Test : sanitize tout seul
$result = Sec::sanitize('test <script>alert("XSS")</script>');
$tester->assertEqual($result, 'testalertXSS', "Le script doit être supprimé");

$result = Sec::sanitize('test <script>alert("XSS-89")</script>', '09');
$tester->assertEqual($result, '89', "Le script doit être supprimé");

// Test : sanitize slug
$result = Sec::sanitize('Mon Article 2025!', 'slug');
$tester->assertEqual($result, 'mon-article-2025', "Doit créer un slug propre");

$result = Sec::sanitize('  Test---Multiple   Spaces  ', 'slug');
$tester->assertEqual($result, 'test-multiple-spaces', "Doit nettoyer les tirets multiples et espaces");

$result = Sec::sanitize('Test<script>XSS</script>Slug', 'slug');
$tester->assertEqual($result, 'testxssslug', "Le script doit être supprimé");

$result = Sec::sanitize('---debut-et-fin---', 'slug');
$tester->assertEqual($result, 'debut-et-fin', "Doit supprimer les tirets en début et fin");

$result = Sec::sanitize('MAJUSCULES-et-minuscules', 'slug');
$tester->assertEqual($result, 'majuscules-et-minuscules', "Doit tout mettre en minuscules");

$result = Sec::sanitize('Special@#$%Characters!!!', 'slug');
$tester->assertEqual($result, 'special-characters', "Doit remplacer les caractères spéciaux par des tirets");

$tester->header("Test sanitize type 'time'");
// Valide
$_GET['t'] = '14:30';
$result = Sec::getParam('t', 'time', 0);
$tester->assertEqual('14:30', $result, "Heure valide 14:30");

$_GET['t'] = '00:00';
$result = Sec::getParam('t', 'time', 0);
$tester->assertEqual('00:00', $result, "Heure valide 00:00 (minuit)");

$_GET['t'] = '23:59';
$result = Sec::getParam('t', 'time', 0);
$tester->assertEqual('23:59', $result, "Heure valide 23:59 (max)");

// Invalide
$_GET['t'] = '24:00';
$result = Sec::getParam('t', 'time', 0);
$tester->assertEqual('', $result, "Heure invalide 24:00");

$_GET['t'] = '12:60';
$result = Sec::getParam('t', 'time', 0);
$tester->assertEqual('', $result, "Minutes invalides 12:60");

$_GET['t'] = '9:30';
$result = Sec::getParam('t', 'time', 0);
$tester->assertEqual('', $result, "Format invalide 9:30 (pas de zéro)");

$_GET['t'] = '14:30:00';
$result = Sec::getParam('t', 'time', 0);
$tester->assertEqual('', $result, "Format invalide avec secondes");

$_GET['t'] = '<b>14:30</b>';
$result = Sec::getParam('t', 'time', 0);
$tester->assertEqual('14:30', $result, "Strip HTML puis valide");

$_GET['t'] = 'not-a-time';
$result = Sec::getParam('t', 'time', 0);
$tester->assertEqual('', $result, "Chaîne non valide");


$tester->header("Test sanitize type 'phone'");
// Valide
$_GET['p'] = '+33612345678';
$result = Sec::getParam('p', 'phone', 0);
$tester->assertEqual('+33612345678', $result, "Numéro international valide");

$_GET['p'] = '0612345678';
$result = Sec::getParam('p', 'phone', 0);
$tester->assertEqual('0612345678', $result, "Numéro local valide");

$_GET['p'] = '06 12 34 56 78';
$result = Sec::getParam('p', 'phone', 0);
$tester->assertEqual('0612345678', $result, "Espaces strippés");

$_GET['p'] = '06-12-34-56-78';
$result = Sec::getParam('p', 'phone', 0);
$tester->assertEqual('0612345678', $result, "Tirets strippés");

$_GET['p'] = '06.12.34.56.78';
$result = Sec::getParam('p', 'phone', 0);
$tester->assertEqual('0612345678', $result, "Points strippés");

// Invalide
$_GET['p'] = '123';
$result = Sec::getParam('p', 'phone', 0);
$tester->assertEqual('', $result, "Trop court (< 6 chiffres)");

$_GET['p'] = '012345678901234567890';
$result = Sec::getParam('p', 'phone', 0);
$tester->assertEqual('', $result, "Trop long (> 20 chiffres)");

$_GET['p'] = 'abcdefgh';
$result = Sec::getParam('p', 'phone', 0);
$tester->assertEqual('', $result, "Que des lettres → vide après strip");

$_GET['p'] = '<b>+33612345678</b>';
$result = Sec::getParam('p', 'phone', 0);
$tester->assertEqual('+33612345678', $result, "Strip HTML puis valide");


$tester->header("Test sanitize type 'hex_color'");
// Valide
$_GET['c'] = '#ff6600';
$result = Sec::getParam('c', 'hex_color', 0);
$tester->assertEqual('#ff6600', $result, "Couleur hex 6 chiffres valide");

$_GET['c'] = '#FFF';
$result = Sec::getParam('c', 'hex_color', 0);
$tester->assertEqual('#fff', $result, "Couleur hex 3 chiffres normalisée en lowercase");

$_GET['c'] = '#ABC123';
$result = Sec::getParam('c', 'hex_color', 0);
$tester->assertEqual('#abc123', $result, "Majuscules normalisées en lowercase");

$_GET['c'] = '#000000';
$result = Sec::getParam('c', 'hex_color', 0);
$tester->assertEqual('#000000', $result, "Noir valide");

$_GET['c'] = '#ffffff';
$result = Sec::getParam('c', 'hex_color', 0);
$tester->assertEqual('#ffffff', $result, "Blanc valide");

// Invalide
$_GET['c'] = 'ff6600';
$result = Sec::getParam('c', 'hex_color', 0);
$tester->assertEqual('', $result, "Manque le # en début");

$_GET['c'] = '#ff660';
$result = Sec::getParam('c', 'hex_color', 0);
$tester->assertEqual('', $result, "5 chiffres invalide");

$_GET['c'] = '#zzzzzz';
$result = Sec::getParam('c', 'hex_color', 0);
$tester->assertEqual('', $result, "Caractères non hex");

$_GET['c'] = '<b>#ff6600</b>';
$result = Sec::getParam('c', 'hex_color', 0);
$tester->assertEqual('#ff6600', $result, "Strip HTML puis valide");

$_GET['c'] = 'red';
$result = Sec::getParam('c', 'hex_color', 0);
$tester->assertEqual('', $result, "Nom de couleur CSS rejeté");

$_GET['c'] = 'on';
$result = Sec::getParam('c', 'bool_int', 0);
$tester->assertEqual(1, $result, "bool bien parsé en int");

$_GET['c'] = '1';
$result = Sec::getParam('c', 'bool_int', 0);
$tester->assertEqual(1, $result, "bool bien parsé en int");

$_GET['c'] = 'true';
$result = Sec::getParam('c', 'bool_int', 0);
$tester->assertEqual(1, $result, "bool bien parsé en int");

$_GET['c'] = 'off';
$result = Sec::getParam('c', 'bool_int', 0);
$tester->assertEqual(0, $result, "bool bien parsé en int");

$_GET['c'] = '0';
$result = Sec::getParam('c', 'bool_int', 0);
$tester->assertEqual(0, $result, "bool bien parsé en int");

$_GET['c'] = 'false';
$result = Sec::getParam('c', 'bool_int', 0);
$tester->assertEqual(0, $result, "bool bien parsé en int");

$_GET['c'] = null;
$result = Sec::getParam('c', 'bool_int', 0);
$tester->assertEqual(0, $result, "bool bien parsé en int", true);

$_GET['c'] = null;
$result = Sec::getParam('c', 'bool', 0);
$tester->assertEqual(false, $result, "bool bien parsé en int", true);


$_GET = [];

// Test de isPublicDevelopperIp
// Test biaisés car Is_debug =  True TODO à patcher env ou debug off via code ?
//$_SERVER['REMOTE_ADDR'] = '8.8.8.8';
//$result = Sec::isPublicDevelopperIp();
//$tester->assertEqual($result, false, "L'IP 8.8.8.8 est retourne faux");
//$_SERVER['REMOTE_ADDR'] = '212.27.40.240';
//$result = Sec::isPublicDevelopperIp();
//$tester->assertEqual($result, true, "L'IP publique retourne vraie autorisée");
//$_SERVER['REMOTE_ADDR'] = '127.0.0.1';



$tester->footer(exit: false);
