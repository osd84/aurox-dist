<?php

require_once '../aurox.php';

use OsdAurox\BrutalCrud;
use OsdAurox\Dbo;
use osd84\BrutalTestRunner\BrutalTestRunner;

$tester = new BrutalTestRunner();
$tester->header(__FILE__);

$pdo = Dbo::getPdo();

// -----------------------------------------------------------------------------
// Jeu d'essai : deux tables, une fk de l'une vers l'autre
// -----------------------------------------------------------------------------

$pdo->exec('DROP TABLE IF EXISTS brutalcrud_cepage');
$pdo->exec('DROP TABLE IF EXISTS brutalcrud_couleur');

$isPgsql = Dbo::isPgsql();
$serial = $isPgsql ? 'serial PRIMARY KEY' : 'int unsigned auto_increment primary key';
$stamp = $isPgsql ? 'timestamp' : 'datetime';

$pdo->exec("CREATE TABLE brutalcrud_couleur (
    id $serial,
    created_at $stamp DEFAULT CURRENT_TIMESTAMP,
    updated_at $stamp DEFAULT CURRENT_TIMESTAMP,
    created_by int NULL,
    updated_by int NULL,
    nom varchar(255) NOT NULL
)");

$pdo->exec("CREATE TABLE brutalcrud_cepage (
    id $serial,
    created_at $stamp DEFAULT CURRENT_TIMESTAMP,
    updated_at $stamp DEFAULT CURRENT_TIMESTAMP,
    created_by int NULL,
    updated_by int NULL,
    nom varchar(255) NOT NULL,
    abreviation varchar(255) NULL,
    couleur_id int NULL REFERENCES brutalcrud_couleur (id),
    rang int NULL,
    rendement_hl float NULL,
    is_actif smallint NULL
)");

$pdo->exec("INSERT INTO brutalcrud_couleur (nom) VALUES ('BLANC')");
$pdo->exec("INSERT INTO brutalcrud_couleur (nom) VALUES ('ROUGE')");

$registry = [
    'couleur' => [
        'table' => 'brutalcrud_couleur',
        'un' => 'couleur', 'des' => 'Couleurs', 'groupe' => 'Vigne',
        'fields' => [
            'nom' => ['type' => 'varchar', 'required' => True, 'maxLength' => 255,
                      'unique' => True, 'searchable' => True, 'inList' => True, 'upper' => True],
        ],
    ],
    'cepage' => [
        'table' => 'brutalcrud_cepage',
        'un' => 'cepage', 'des' => 'Cepages', 'groupe' => 'Vigne',
        'fields' => [
            'nom'          => ['type' => 'varchar', 'required' => True, 'maxLength' => 255,
                               'unique' => True, 'searchable' => True, 'inList' => True, 'upper' => True],
            'abreviation'  => ['type' => 'varchar', 'maxLength' => 255, 'searchable' => True, 'inList' => True],
            'couleur_id'   => ['type' => 'fk', 'fkTableName' => 'brutalcrud_couleur',
                               'fkFieldName' => 'id', 'fkLabelField' => 'nom', 'inList' => True],
            'rang'         => ['type' => 'int', 'min' => 0],
            'rendement_hl' => ['type' => 'float'],
            'is_actif'     => ['type' => 'bool', 'inList' => True],
        ],
    ],
];

$couleurs = $pdo->query('SELECT id, nom FROM brutalcrud_couleur ORDER BY id')->fetchAll(PDO::FETCH_KEY_PAIR);
$idBlanc = (int) array_key_first($couleurs);
$idRouge = (int) array_key_last($couleurs);

// -----------------------------------------------------------------------------
$tester->header('Registre');

$table = BrutalCrud::tableOrDie($registry, 'cepage');
$tester->assertEqual($table['slug'], 'cepage', 'tableOrDie retourne la table et ajoute le slug');
$tester->assertEqual($table['table'], 'brutalcrud_cepage', 'tableOrDie retourne le nom de table du registre');
$tester->assertEqual(BrutalCrud::table($registry, 'inconnu'), null, 'table() retourne null sur un slug inconnu');
$tester->assertEqual(BrutalCrud::table($registry, null), null, 'table() retourne null sur un slug vide');
$tester->assertEqual(count(BrutalCrud::byGroup($registry)['Vigne']), 2, 'byGroup regroupe les tables');

// -----------------------------------------------------------------------------
$tester->header('Champs');

$tester->assertEqual(BrutalCrud::labelField($table), 'nom', 'labelField prend le premier champ texte');
$tester->assertEqual(array_keys(BrutalCrud::fieldsWith($table, 'searchable')), ['nom', 'abreviation'], 'fieldsWith(searchable)');
$tester->assertEqual(array_keys(BrutalCrud::fieldsWith($table, 'unique')), ['nom'], 'fieldsWith(unique)');
$tester->assertEqual(BrutalCrud::rowLabel($table, ['id' => 7, 'nom' => 'SYRAH']), 'SYRAH', 'rowLabel prend le libelle');
$tester->assertEqual(BrutalCrud::rowLabel($table, ['id' => 7, 'nom' => '']), '#7', 'rowLabel retombe sur l id');

// -----------------------------------------------------------------------------
$tester->header('Lecture du POST');

$_POST = ['nom' => ' syrah ', 'abreviation' => 'Syr', 'couleur_id' => (string) $idRouge,
          'rang' => '3', 'rendement_hl' => '55,5'];
$datas = BrutalCrud::readPost($table);
$tester->assertEqual($datas['nom'], 'SYRAH', 'readPost force en majuscules et trim quand upper est pose');
$tester->assertEqual($datas['abreviation'], 'Syr', 'readPost laisse la casse quand upper est absent');
$tester->assertEqual($datas['couleur_id'], $idRouge, 'readPost caste la fk en entier');
$tester->assertEqual($datas['rang'], 3, 'readPost caste l entier');
$tester->assertEqual($datas['rendement_hl'], 55.5, 'readPost accepte la virgule decimale');
$tester->assertEqual($datas['is_actif'], 0, 'readPost met une case a cocher absente a 0');

$_POST = ['nom' => 'X', 'is_actif' => 'on', 'abreviation' => '', 'rang' => ''];
$datas = BrutalCrud::readPost($table);
$tester->assertEqual($datas['is_actif'], 1, 'readPost met une case a cocher presente a 1');
$tester->assertEqual($datas['abreviation'], null, 'readPost rend null sur une chaine vide');
$tester->assertEqual($datas['rang'], null, 'readPost rend null sur un entier vide');

// le piege du zero : '0' est falsy en PHP mais c'est une valeur, pas une absence
$_POST = ['nom' => 'X', 'rang' => '0', 'rendement_hl' => '0'];
$datas = BrutalCrud::readPost($table);
$tester->assertEqual($datas['rang'], 0, 'readPost rend 0 sur la chaine "0", pas null');
$tester->assertEqual($datas['rang'] === null, false, 'readPost distingue "0" de la valeur absente');
$tester->assertEqual($datas['rendement_hl'], 0.0, 'readPost rend 0.0 sur la chaine "0" pour un flottant');
$_POST = [];

// -----------------------------------------------------------------------------
$tester->header('Validation');

$bon = ['nom' => 'SYRAH', 'abreviation' => 'SYR', 'couleur_id' => $idRouge,
        'rang' => 1, 'rendement_hl' => 55.5, 'is_actif' => 1];
$tester->assertEqual(BrutalCrud::validate($table, $bon)->isValid(), true, 'validate accepte des donnees correctes');

$v = BrutalCrud::validate($table, ['nom' => null, 'is_actif' => 0]);
$tester->assertEqual($v->isValid(), false, 'validate refuse un champ requis vide');
$tester->assertEqual(count($v->getError('nom')), 1, 'un champ requis vide ne leve qu une seule erreur');

$v = BrutalCrud::validate($table, ['nom' => 'X', 'rang' => -3]);
$tester->assertEqual($v->hasError('rang'), true, 'validate applique min = 0 (valeur falsy)');

$v = BrutalCrud::validate($table, ['nom' => 'X', 'couleur_id' => 999999]);
$tester->assertEqual($v->hasError('couleur_id'), true, 'validate refuse une fk inexistante');

$v = BrutalCrud::validate($table, ['nom' => 'X', 'couleur_id' => $idBlanc]);
$tester->assertEqual($v->hasError('couleur_id'), false, 'validate accepte une fk existante');

$v = BrutalCrud::validate($table, ['nom' => 'X', 'is_actif' => 1]);
$tester->assertEqual($v->isValid(), true, 'un bool en 0/1 ne declenche pas validateBoolean');

// le metier francais est plein d'apostrophes et d'esperluettes : ce ne sont
// pas du HTML et elles doivent passer (regression validateNoHtml)
$tester->assertEqual(BrutalCrud::validate($table, ['nom' => "A L'OEIL"])->isValid(), true,
    'validate accepte une apostrophe');
$tester->assertEqual(BrutalCrud::validate($table, ['nom' => 'DUPONT & FILS'])->isValid(), true,
    'validate accepte une esperluette');
$tester->assertEqual(BrutalCrud::validate($table, ['nom' => 'CHATEAU "X"'])->isValid(), true,
    'validate accepte des guillemets');
// deux lignes de defense distinctes : readPost() nettoie a la saisie, et
// validate() refuse si du HTML arrive par un autre chemin
$tester->assertEqual(BrutalCrud::validate($table, ['nom' => '<script>alert(1)</script>'])->isValid(), false,
    'validate refuse une balise html (filet de securite)');

$_POST = ['nom' => '<script>alert(1)</script>'];
$tester->assertEqual(BrutalCrud::readPost($table)['nom'], 'ALERT(1)',
    'readPost retire les balises html a la saisie');
$_POST = [];

// -----------------------------------------------------------------------------
$tester->header('Ecriture');

$id = BrutalCrud::insert($pdo, $table, $bon, 42);
$tester->assertEqual($id > 0, true, 'insert retourne un id');

$row = BrutalCrud::get($pdo, $table, $id);
$tester->assertEqual($row['nom'], 'SYRAH', 'get relit la ligne');
$tester->assertEqual((int) $row['created_by'], 42, 'insert renseigne created_by');
$tester->assertEqual((int) $row['is_actif'], 1, 'insert ecrit le bool en 0/1');
$tester->assertEqual((float) $row['rendement_hl'], 55.5, 'insert ecrit le flottant');

BrutalCrud::update($pdo, $table, $id, ['nom' => 'SYRAH NOIR', 'couleur_id' => null,
                                       'abreviation' => null, 'rang' => null,
                                       'rendement_hl' => null, 'is_actif' => 0], 43);
$row = BrutalCrud::get($pdo, $table, $id);
$tester->assertEqual($row['nom'], 'SYRAH NOIR', 'update modifie la ligne');
$tester->assertEqual($row['couleur_id'], null, 'update remet une fk a null');
$tester->assertEqual((int) $row['updated_by'], 43, 'update renseigne updated_by');
$tester->assertEqual(BrutalCrud::count($pdo, $table), 1, 'count compte les lignes');

// -----------------------------------------------------------------------------
$tester->header('Unicite');

$tester->assertEqual(BrutalCrud::uniqueErrors($pdo, $table, ['nom' => 'SYRAH NOIR'], null) !== [], true,
    'uniqueErrors detecte un doublon');
$tester->assertEqual(BrutalCrud::uniqueErrors($pdo, $table, ['nom' => 'SYRAH NOIR'], $id), [],
    'uniqueErrors ignore la ligne editee');
$tester->assertEqual(BrutalCrud::uniqueErrors($pdo, $table, ['nom' => 'AUTRE'], null), [],
    'uniqueErrors laisse passer une valeur libre');

// -----------------------------------------------------------------------------
$tester->header('Liste et recherche');

$q = BrutalCrud::listQueries($table, null);
$tester->assertEqual(str_contains($q['query'], 'ORDER BY nom ASC'), true, 'listQueries trie sur le libelle');
$tester->assertEqual($q['bind_params'], [], 'listQueries sans recherche ne binde rien');

$q = BrutalCrud::listQueries($table, 'SY%');
$tester->assertEqual(count($q['bind_params']), 2, 'listQueries binde un parametre par champ cherchable');
$stmt = $pdo->prepare($q['query']);
$stmt->execute($q['bind_params']);
$tester->assertEqual(count($stmt->fetchAll()), 1, 'la recherche trouve la ligne');

$stmt = $pdo->prepare($q['count_query']);
$stmt->execute($q['bind_params']);
$tester->assertEqual((int) $stmt->fetchColumn(), 1, 'la requete de total est coherente');

$q = BrutalCrud::listQueries($table, 'ZZZ%');
$stmt = $pdo->prepare($q['query']);
$stmt->execute($q['bind_params']);
$tester->assertEqual(count($stmt->fetchAll()), 0, 'la recherche sans resultat ne renvoie rien');

// -----------------------------------------------------------------------------
$tester->header('Options de fk');

$options = BrutalCrud::fkOptions($pdo, $table['fields']['couleur_id']);
$tester->assertEqual($options[$idBlanc], 'BLANC', 'fkOptions retourne id => libelle');
$tester->assertEqual(count($options), 2, 'fkOptions retourne toutes les lignes');

// -----------------------------------------------------------------------------
$tester->header('Suppression');

$tableCouleur = BrutalCrud::tableOrDie($registry, 'couleur');
BrutalCrud::update($pdo, $table, $id, ['nom' => 'SYRAH NOIR', 'couleur_id' => $idBlanc,
                                       'abreviation' => null, 'rang' => null,
                                       'rendement_hl' => null, 'is_actif' => 0], 43);

$tester->assertEqual(BrutalCrud::delete($pdo, $tableCouleur, $idBlanc), false,
    'delete refuse une ligne encore referencee par une fk');
$tester->assertEqual(BrutalCrud::delete($pdo, $tableCouleur, $idRouge), true,
    'delete supprime une ligne libre');
$tester->assertEqual(BrutalCrud::delete($pdo, $table, $id), true,
    'delete supprime la ligne du cepage');
$tester->assertEqual(BrutalCrud::delete($pdo, $table, 999999), false,
    'delete retourne false sur un id inexistant');
$tester->assertEqual(BrutalCrud::count($pdo, $table), 0, 'la table est vide apres suppression');

// -----------------------------------------------------------------------------
$pdo->exec('DROP TABLE IF EXISTS brutalcrud_cepage');
$pdo->exec('DROP TABLE IF EXISTS brutalcrud_couleur');

$tester->footer(exit: false);
