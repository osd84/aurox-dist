<?php


require_once '../aurox.php';

use App\PostsModel;
use OsdAurox\BaseModel;
use OsdAurox\Dbo;
use osd84\BrutalTestRunner\BrutalTestRunner;

$tester = new BrutalTestRunner();
$tester->header(__FILE__);

$pdo = Dbo::getPdo();

// check SSL
$ssl = Dbo::sslVersion();
$tester->assertEqual(str_contains($ssl, 'TLS'), true, 'ssl TLS active');


// remise en ordre des datas
$stmt = $pdo->prepare('UPDATE posts SET 
                 title = :title, updated_by = :updated_by, status = :status
                 WHERE id = :id');
$stmt->execute([
    'title' => "title1",
    'updated_by' => null, // null pour spécifier que la colonne doit accepter une valeur NULL
    'status' => 'draft', // null pour spécifier que la colonne doit accepter une valeur NULL
    'id' => 1
]);

$instancePost = new PostsModel();
$tester->assertEqual($instancePost->getTable(), 'posts', 'getTable() ok');

$stmt = $pdo->prepare('SELECT * FROM posts ORDER BY id DESC');
$stmt->execute();
$sourcePosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
$sourcePost = $sourcePosts[0];
$sourcePost2 = $sourcePosts[1];

$post = PostsModel::get($pdo, $sourcePost['id']);
$tester->assertEqual($post['id'], $sourcePost['id'], 'get() ok');

// Test get null
$post = PostsModel::get($pdo, 0);
$tester->assertEqual($post, null, 'get() null ok');

$post = PostsModel::getBy($pdo, 'title', $sourcePost['title']);
$tester->assertEqual($post['id'], $sourcePost['id'], 'getBy() ok');

$posts = PostsModel::getAllBy($pdo, 'status', 'draft');
$tester->assertEqual(count($posts), 2, 'getAllBy() ok');

$count = PostsModel::count($pdo);
$tester->assertEqual($count, 2, 'count() ok');

$uniq = PostsModel::check_uniq($pdo, 'title', 'test');
$tester->assertEqual($uniq, false, 'check_uniq() ok');
$uniq = PostsModel::check_uniq($pdo, 'title', 'titleFake');
$tester->assertEqual($uniq, true, 'check_uniq() ok');

$jsonAgg = '[{"id": 3, "name": "Elem1", "name_translated": "Elem1Fr"}, {"id": 4, "name": "Elem2", "name_translated": "Elem2Fr"}]';
$entity = ['keyJson' => $jsonAgg, 'keyNone' => null, 'keyNoJson' => 'noJson'];
$r = BaseModel::jsonArrayAggDecode($entity, 'keyJson');
$tester->assertEqual(array_column($r, 'name'), ['Elem1', 'Elem2'], 'jsonArrayAggDecode() keyJson ok');

$r = BaseModel::jsonArrayAggDecode($entity, 'keyNone');
$tester->assertEqual($r, [], 'jsonArrayAggDecode() keyNone ok');

$r = BaseModel::jsonArrayAggDecode($entity, 'keyNoJson');
$tester->assertEqual($r, [], 'jsonArrayAggDecode() keyNoJson ok');

$r = BaseModel::jsonArrayAggDecode($entity, 'keyNoJson', default: ['my', 'default']);
$tester->assertEqual($r, ['my', 'default'], 'jsonArrayAggDecode() keyNoJson + default ok');


// Tests pour idsExistsOrEmpty
$tester->header("Test de la méthode idsExistsOrEmpty()");

$result = BaseModel::idsExistsOrEmpty($pdo, 'posts', []);
$tester->assertEqual($result, true, 'idsExistsOrEmpty : tableau vide doit retourner true');
// Test avec ID existant
$result = BaseModel::idsExistsOrEmpty($pdo, 'posts', [1]);
$tester->assertEqual($result, true, 'idsExistsOrEmpty : ID existant doit retourner true');
// Test avec plusieurs IDs existants
$result = BaseModel::idsExistsOrEmpty($pdo, 'posts', [1, 2]);
$tester->assertEqual($result, true, 'idsExistsOrEmpty : plusieurs IDs existants doivent retourner true');
// Test avec ID inexistant
$result = BaseModel::idsExistsOrEmpty($pdo, 'posts', [999]);
$tester->assertEqual($result, false, 'idsExistsOrEmpty : ID inexistant doit retourner false');
// Test avec ID invalide
try {
    BaseModel::idsExistsOrEmpty($pdo, 'posts', ['abc']);
    $tester->assertEqual(0,1 ,'idsExistsOrEmpty : ID non numérique doit lever une exception');
} catch (InvalidArgumentException $e) {
    $tester->assertEqual(1, 1,'idsExistsOrEmpty : ID non numérique lève bien une exception');
}

// Tests pour getByIds
$tester->header("Test de la méthode getByIds()");
// Test avec tableau vide
$result = BaseModel::getByIds($pdo, 'posts', []);
$tester->assertEqual($result, [], 'getByIds : tableau vide doit retourner tableau vide');

// Test avec un seul ID
$result = BaseModel::getByIds($pdo, 'posts', [$sourcePost['id']]);
$tester->assertEqual(count($result), 1, 'getByIds : un ID doit retourner un résultat');
$tester->assertEqual($result[0]['id'], $sourcePost['id'], 'getByIds : ID correct retourné');
$tester->assertEqual($result[0]['title'], $sourcePost['title'], 'getByIds : données correctes retournées');

// Test avec plusieurs IDs
$result = BaseModel::getByIds($pdo, 'posts', [1, 2]);
$tester->assertEqual(count($result), 2, 'getByIds : deux IDs doivent retourner deux résultats');

// Test avec ID inexistant
$result = BaseModel::getByIds($pdo, 'posts', [999]);
$tester->assertEqual($result, [], 'getByIds : ID inexistant doit retourner tableau vide');

// Test avec ID invalide
try {
    BaseModel::getByIds($pdo, 'posts', ['abc']);
    $tester->assertEqual(0,1 ,'getByIds : ID non numérique doit lever une exception');
} catch (InvalidArgumentException $e) {
    $tester->assertEqual(1,1 ,'getByIds : ID non numérique lève bien une exception');
}
// Test avec select spécifique
$result = BaseModel::getByIds($pdo, 'posts', [1], 'title');
$tester->assertEqual(in_array('title', array_keys($result[0])), true, 'getByIds : select spécifique retourne uniquement les colonnes demandées');
$tester->assertEqual(in_array('id', array_keys($result[0])), false, 'getByIds : select spécifique retourne uniquement les colonnes demandées');



// Tests pour exist
$tester->header("Test de la méthode exist()");

// Test avec ID existant
$result = PostsModel::exist($pdo, 1);
$tester->assertEqual($result, true, 'exist : ID existant doit retourner true');
// Test avec ID inexistant
$result = PostsModel::exist($pdo, 999);
$tester->assertEqual($result, false, 'exist : ID inexistant doit retourner false');
// Test avec ID zéro (généralement invalide)
$result = PostsModel::exist($pdo, 0);
$tester->assertEqual($result, false, 'exist : ID zéro doit retourner false');
// Test avec valeur non numérique (qui sera convertie en entier)
$result = PostsModel::exist($pdo, "abc");
$tester->assertEqual($result, false, 'exist : ID non numérique converti en 0 doit retourner false');
// Test avec valeur numérique sous forme de chaîne
$result = PostsModel::exist($pdo, "1");
$tester->assertEqual($result, true, 'exist : ID numérique sous forme de chaîne doit être converti et retourner true');


// Test pour getAll
$tester->header("Test de la méthode getAll()");

// Test basique sans paramètres
$result = PostsModel::getAll($pdo);
$tester->assertEqual(count($result) > 0, true, 'getAll : doit retourner des résultats');
// Test avec tri ascendant
$result = PostsModel::getAll($pdo, 'title', 'ASC');
$tester->assertEqual($result[0]['title'], 'title1', 'getAll : tri ascendant sur title fonctionne');
// Test avec tri descendant
$result = PostsModel::getAll($pdo, 'title', 'DESC');
$tester->assertEqual($result[0]['title'], 'title2', 'getAll : tri descendant sur title fonctionne');
// Test avec limite
$result = PostsModel::getAll($pdo, limit: 1);
$tester->assertEqual(count($result), 1, 'getAll : limite fonctionne correctement');
// Test avec tri et limite combinés
$result = PostsModel::getAll($pdo, 'title', 'ASC', 1);
$tester->assertEqual(count($result), 1, 'getAll : tri et limite combinés fonctionnent');
$tester->assertEqual($result[0]['title'], 'title1', 'getAll : tri et limite retournent le bon enregistrement');
// Test avec direction de tri invalide (devrait defaulter à ASC)
$result = PostsModel::getAll($pdo, 'title', 'INVALID');
$tester->assertEqual($result[0]['title'], 'title1', 'getAll : direction de tri invalide utilise ASC par défaut');
// Test avec une colonne de tri qui n'existe pas (devrait gérer l'erreur PDO)
try {
    PostsModel::getAll($pdo, 'colonne_inexistante');
    $tester->assertEqual(0, 1, 'getAll : devrait lever une exception pour une colonne inexistante');
} catch (RuntimeException $e) {
    $tester->assertEqual(1, 1, 'getAll : gère correctement l\'erreur pour une colonne inexistante');
}


// Tests pour getAllBy avec tri et limite
$tester->header("Test de la méthode getAllBy() avec tri et limite");

// Test basique sans tri ni limite
$posts = PostsModel::getAllBy($pdo, 'status', 'draft');
$tester->assertEqual(count($posts), 2, 'getAllBy : retourne le bon nombre de résultats sans tri ni limite');
// Test avec tri ascendant
$posts = PostsModel::getAllBy($pdo, 'status', 'draft', 'title', 'ASC');
$tester->assertEqual($posts[0]['title'], 'title1', 'getAllBy : tri ascendant sur title fonctionne');
$tester->assertEqual($posts[1]['title'], 'title2', 'getAllBy : tri ascendant sur title maintient l\'ordre');
// Test avec tri descendant
$posts = PostsModel::getAllBy($pdo, 'status', 'draft', 'title', 'DESC');
$tester->assertEqual($posts[0]['title'], 'title2', 'getAllBy : tri descendant sur title fonctionne');
$tester->assertEqual($posts[1]['title'], 'title1', 'getAllBy : tri descendant sur title maintient l\'ordre');
// Test avec limite
$posts = PostsModel::getAllBy($pdo, 'status', 'draft', limit: 1);
$tester->assertEqual(count($posts), 1, 'getAllBy : limite fonctionne correctement');
// Test avec tri et limite combinés
$posts = PostsModel::getAllBy($pdo, 'status', 'draft', 'title', 'ASC', 1);
$tester->assertEqual(count($posts), 1, 'getAllBy : tri et limite combinés fonctionnent');
$tester->assertEqual($posts[0]['title'], 'title1', 'getAllBy : tri et limite retournent le bon enregistrement');
// Test avec direction de tri invalide (devrait basculer à ASC)
$posts = PostsModel::getAllBy($pdo, 'status', 'draft', 'title', 'INVALID');
$tester->assertEqual($posts[0]['title'], 'title1', 'getAllBy : direction de tri invalide utilise ASC par défaut');
// Test avec une colonne de tri qui n'existe pas
try {
    PostsModel::getAllBy($pdo, 'status', 'draft', 'colonne_inexistante');
    $tester->assertEqual(0, 1, 'getAllBy : devrait lever une exception pour une colonne inexistante');
} catch (RuntimeException $e) {
    $tester->assertEqual(1, 1, 'getAllBy : gère correctement l\'erreur pour une colonne inexistante');
}
// Test avec champ de recherche inexistant
try {
    PostsModel::getAllBy($pdo, 'champ_inexistant', 'valeur');
    $tester->assertEqual(0, 1, 'getAllBy : devrait lever une exception pour un champ de recherche inexistant');
} catch (RuntimeException $e) {
    $tester->assertEqual(1, 1, 'getAllBy : gère correctement l\'erreur pour un champ de recherche inexistant');
}
// Test avec valeur null
$posts = PostsModel::getAllBy($pdo, 'status', null);
$tester->assertEqual(count($posts), 0, 'getAllBy : gère correctement les valeurs null');


$tester->header('Test de getValueFrom()');
$r = PostsModel::getValueFrom($pdo, 1, 'title');
$tester->assertEqual($r, 'title1', 'getValueFrom ok');

$tester->header('Test liste blanche des identifiants SQL');
$injections = [
    'getBy $field'         => fn() => PostsModel::getBy($pdo, 'title = title OR 1', 'x'),
    'getAll $orderBy'      => fn() => PostsModel::getAll($pdo, '(SELECT SLEEP(1))'),
    'getAllBy $field'      => fn() => PostsModel::getAllBy($pdo, '1=1 OR status', 'draft'),
    'getAllBy $orderBy'    => fn() => PostsModel::getAllBy($pdo, 'status', 'draft', 'title; DROP TABLE posts'),
    'getValueFrom $field'  => fn() => PostsModel::getValueFrom($pdo, 1, 'password FROM users --'),
    'check_uniq $field'    => fn() => PostsModel::check_uniq($pdo, 'title OR 1', 'x'),
    'getByIds $table'      => fn() => BaseModel::getByIds($pdo, 'users --', [1]),
    'idsExistsOrEmpty $table' => fn() => BaseModel::idsExistsOrEmpty($pdo, 'posts p, users', [1]),
];
foreach ($injections as $label => $call) {
    try {
        $call();
        $tester->assertEqual(0, 1, "$label : injection doit être refusée");
    } catch (InvalidArgumentException $e) {
        $tester->assertEqual(1, 1, "$label : injection refusée");
    }
}

$tester->footer(exit: false);