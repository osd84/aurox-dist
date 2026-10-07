<?php

require_once '../aurox.php';

use OsdAurox\Forms;
use OsdAurox\Paginator;
use OsdAurox\Sec;
use osd84\BrutalTestRunner\BrutalTestRunner;

$tester = new BrutalTestRunner();
$tester->header(__FILE__);

// -----------------------------------------------------------------------------
// Jeu d'essai : sqlite en memoire, le Paginator ne prend qu'un PDO
// -----------------------------------------------------------------------------

if (!in_array('sqlite', PDO::getAvailableDrivers(), True)) {
    echo "pdo_sqlite absent : test ignore\n";
    return;
}

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE paginator_test (id INTEGER PRIMARY KEY, nom TEXT)');
for ($i = 1; $i <= 42; $i++) {
    $pdo->exec("INSERT INTO paginator_test (nom) VALUES ('LIGNE $i')");
}

$query = 'SELECT id, nom FROM paginator_test ORDER BY id ASC';
$count = 'SELECT COUNT(*) FROM paginator_test';
$url = '/liste.php';

// -----------------------------------------------------------------------------
// Per page par defaut
// -----------------------------------------------------------------------------

$tester->header('Per page par defaut');

unset($_GET['per_page'], $_GET['page']);

$p = new Paginator($pdo, $query, $count, $url);
$tester->assertEqual($p->getPerPage(), Paginator::DEFAULT_PER_PAGE, 'sans argument on garde le defaut historique (10)');
$tester->assertEqual($p->getTotalItems(), 42, 'getTotalItems compte toutes les lignes');
$tester->assertEqual($p->getTotalPages(), 5, '42 lignes par 10 font 5 pages');
$tester->assertEqual(count($p->getItems()), 10, 'getItems retourne une page');

$p = new Paginator($pdo, $query, $count, $url, per_page: 25);
$tester->assertEqual($p->getPerPage(), 25, 'per_page du constructeur applique');
$tester->assertEqual($p->getDefaultPerPage(), 25, 'getDefaultPerPage retourne la valeur du constructeur');
$tester->assertEqual($p->getTotalPages(), 2, '42 lignes par 25 font 2 pages');
$tester->assertEqual(count($p->getItems()), 25, 'getItems retourne 25 lignes');

$p = new Paginator($pdo, $query, $count, $url, per_page: 0);
$tester->assertEqual($p->getPerPage(), 10, 'un per_page absurde retombe sur 10');

// L'URL reste prioritaire sur le defaut
$_GET['per_page'] = '5';
$p = new Paginator($pdo, $query, $count, $url, per_page: 25);
$tester->assertEqual($p->getPerPage(), 5, '?per_page ecrase le defaut du constructeur');
$tester->assertEqual(count($p->getItems()), 5, 'getItems suit le per_page de l URL');

$_GET['per_page'] = 'abc';
$p = new Paginator($pdo, $query, $count, $url, per_page: 25);
$tester->assertEqual($p->getPerPage(), 25, 'un ?per_page non numerique retombe sur le defaut');

$_GET['per_page'] = '-3';
$tester->assertEqual(Sec::getPerPage(25), 25, 'Sec::getPerPage refuse les valeurs negatives');
unset($_GET['per_page']);
$tester->assertEqual(Sec::getPerPage(), 10, 'Sec::getPerPage garde 10 par defaut');
$tester->assertEqual(Sec::getPerPage(50), 50, 'Sec::getPerPage accepte un defaut');

// -----------------------------------------------------------------------------
// Pagination SQL classique
// -----------------------------------------------------------------------------

$tester->header('Pagination SQL classique');

$_GET['page'] = '2';
$p = new Paginator($pdo, $query, $count, $url, per_page: 20);
$items = $p->getItems();
$tester->assertEqual($p->getCurrentPage(), 2, 'la page vient de l URL');
$tester->assertEqual(count($items), 20, 'page 2 pleine');
$tester->assertEqual($items[0]['id'], 21, 'l offset est applique');

$html = $p->renderPagination();
$tester->assertEqual(str_contains($html, 'per_page=20'), True, 'les liens portent le per_page courant');
$tester->assertEqual(str_contains($html, 'pagination'), True, 'les boutons bootstrap sont rendus');

$selector = $p->renderPerPageSelector();
$tester->assertEqual(str_contains($selector, 'name="per_page"'), True, 'le selecteur est rendu');
$tester->assertEqual(str_contains($selector, '<option value="20" selected>'), True, 'la valeur courante est selectionnee');
$tester->assertEqual(str_contains($p->renderTotalInfo(), '42'), True, 'renderTotalInfo affiche le total');

// Le selecteur ne doit pas perdre le contexte de la liste (?t=, ?search_text=)
$avecParams = new Paginator($pdo, $query, $count, '/bru_crud.php?t=cepage&search_text=syrah',
                            per_page: 20);
$selector = $avecParams->renderPerPageSelector();
$tester->assertEqual(str_contains($selector, '<input type="hidden" name="t" value="cepage">'), True,
    'les parametres de l URL sont rejoues en champs caches');
$tester->assertEqual(str_contains($selector, 'name="search_text" value="syrah"'), True,
    'la recherche en cours est conservee');
$tester->assertEqual(substr_count($selector, 'name="per_page"'), 1,
    'per_page n est pas duplique en champ cache');

// Un defaut hors liste standard doit apparaitre dans le selecteur
unset($_GET['per_page']);
$p = new Paginator($pdo, $query, $count, $url, per_page: 25);
$tester->assertEqual(str_contains($p->renderPerPageSelector(), '<option value="25" selected>'), True,
    'un per_page hors liste est ajoute aux options');

// -----------------------------------------------------------------------------
// Mode DataTables
// -----------------------------------------------------------------------------

$tester->header('Mode DataTables');

unset($_GET['per_page']);
$_GET['page'] = '2';

$p = new Paginator($pdo, $query, $count, $url, per_page: 25, as_datatable: True, datatable_id: 'liste-clients');

$tester->assertEqual($p->isDatatable(), True, 'isDatatable expose le mode');
$tester->assertEqual($p->getDatatableId(), 'liste-clients', 'l id du tableau est conserve');
$tester->assertEqual(count($p->getItems()), 42, 'en mode datatable on sort TOUTES les lignes');
$tester->assertEqual($p->renderPerPageSelector(), '', 'pas de selecteur : DataTables le fournit');
$tester->assertEqual($p->renderTotalInfo(), '', 'pas de total : DataTables l affiche');

$script = $p->renderPagination();
$tester->assertEqual(str_contains($script, '<script'), True, 'renderPagination rend le script d init');
$tester->assertEqual(str_contains($script, '#liste-clients'), True, 'le script vise le bon tableau');
$tester->assertEqual(str_contains($script, '"pageLength":25'), True, 'pageLength suit le per_page');
$tester->assertEqual(str_contains($script, 'DataTable('), True, 'DataTable est initialise');
$tester->assertEqual(str_contains($script, 'isDataTable'), True, 'double init evitee');

// Id sale : on ne laisse pas passer n importe quoi dans le selecteur jQuery
$p = new Paginator($pdo, $query, $count, $url, as_datatable: True, datatable_id: 'x"); alert(1); //');
$tester->assertEqual($p->getDatatableId(), 'xalert1', 'l id est nettoye');
$p = new Paginator($pdo, $query, $count, $url, as_datatable: True, datatable_id: '<>');
$tester->assertEqual($p->getDatatableId(), Paginator::DEFAULT_DATATABLE_ID, 'un id vide retombe sur le defaut');

// Options surchargeables
$p = new Paginator($pdo, $query, $count, $url, as_datatable: True);
$script = $p->renderDatatableScript(['searching' => False, 'order' => [[1, 'asc']]]);
$tester->assertEqual(str_contains($script, '"searching":false'), True, 'les options sont surchargeables');
$tester->assertEqual(str_contains($script, '"order":[[1,"asc"]]'), True, 'un ordre peut etre impose');

// Recherche : POST cote serveur, ou native DataTables cote front
$tester->header('Recherche');

Paginator::resetDatatableMode();
$_POST['search_text'] = '  syrah  ';

$classique = new Paginator($pdo, $query, $count, $url);
$tester->assertEqual(Paginator::isDatatableMode(), False, 'un paginator classique ne bascule pas la page');
$tester->assertEqual(Paginator::searchText(), 'syrah', 'searchText lit le POST et trim');
$tester->assertEqual(str_contains($classique->renderSearchForm(), 'name="search_text"'), True,
    'le formulaire POST est rendu en mode classique');
$tester->assertEqual(str_contains($classique->renderSearchForm(), 'syrah'), True,
    'le texte cherche est reaffiche');
$tester->assertEqual(str_contains(Forms::searchFormRow('syrah'), 'name="search_text"'), True,
    'Forms::searchFormRow rend le formulaire en mode classique');

// Des qu un paginator datatable existe, toute la page bascule : le formulaire
// POST se tait, meme appele directement par la vue.
$dt = new Paginator($pdo, $query, $count, $url, as_datatable: True);
$tester->assertEqual(Paginator::isDatatableMode(), True, 'le mode datatable est global a la page');
$tester->assertEqual($dt->renderSearchForm(), '', 'en mode datatable le formulaire POST disparait');
$tester->assertEqual(Forms::searchFormRow('syrah'), '', 'Forms::searchFormRow se tait aussi');
$tester->assertEqual(Paginator::searchText(), null, 'searchText suit le mode de la page');
$tester->assertEqual(Paginator::searchText(True), null, 'en mode datatable searchText rend null');

Paginator::resetDatatableMode();
$tester->assertEqual(Paginator::searchText(), 'syrah', 'apres reset, la recherche POST revient');
$tester->assertEqual(Paginator::searchText(True), null, 'le flag explicite reste prioritaire');

$_POST['search_text'] = '   ';
$tester->assertEqual(Paginator::searchText(), null, 'une recherche vide vaut null');
unset($_POST['search_text']);
$tester->assertEqual(Paginator::searchText(), null, 'pas de POST : null');

// Zebrage
$tester->header('Zebrage');

$p = new Paginator($pdo, $query, $count, $url, as_datatable: True);
$tester->assertEqual($p->isDatatableStripped(), False, 'pas de zebrage par defaut');
$script = $p->renderDatatableScript();
$tester->assertEqual(str_contains($script, 'toggleClass("table-striped", false)'), True,
    'la classe bootstrap est retiree');
$tester->assertEqual(str_contains($script, '"stripeClasses":[]'), True,
    'les classes de zebrage DataTables sont neutralisees');

$p = new Paginator($pdo, $query, $count, $url, as_datatable: True, datatable_stripped: True);
$tester->assertEqual($p->isDatatableStripped(), True, 'datatable_stripped est expose');
$script = $p->renderDatatableScript();
$tester->assertEqual(str_contains($script, 'toggleClass("table-striped", true)'), True,
    'la classe bootstrap est posee');
$tester->assertEqual(str_contains($script, 'stripeClasses'), False,
    'on laisse DataTables zebrer comme il sait');

// Assets manquants : on previent au lieu de planter
$tester->assertEqual(str_contains($script, 'if (!$.fn.DataTable)'), True,
    'le script verifie que DataTables est charge');

// Colonnes cherchees
$tester->header('Colonnes cherchees');

$p = new Paginator($pdo, $query, $count, $url, as_datatable: True,
                   datatable_search_fields: ['nom', ' ville ', 3, '', 'nom']);
$tester->assertEqual($p->getDatatableSearchFields(), ['nom', 'ville', '3'],
    'les champs sont normalises : trim, vides et doublons enleves');

$script = $p->renderDatatableScript();
$tester->assertEqual(str_contains($script, 'let fields = ["nom","ville","3"];'), True,
    'les champs partent dans le script');
$tester->assertEqual(str_contains($script, 'data-field'), True,
    'le script resout les data-field du thead');
$tester->assertEqual(str_contains($script, 'searchable: false'), True,
    'les autres colonnes sont sorties de la recherche');

$p = new Paginator($pdo, $query, $count, $url, as_datatable: True);
$tester->assertEqual($p->getDatatableSearchFields(), [], 'vide par defaut');
$tester->assertEqual(str_contains($p->renderDatatableScript(), 'let fields = [];'), True,
    'sans champ precise DataTables cherche partout');

// Export CSV toujours present
$tester->header('Export CSV');

$script = $p->renderPagination();
$tester->assertEqual(str_contains($script, '"extend":"csv"'), True, 'le bouton CSV est toujours la');
$tester->assertEqual(str_contains($script, '"buttons"'), True, 'les buttons sont configures');
$tester->assertEqual(str_contains($script, '"topStart":["pageLength","buttons"]'), True,
    'le bouton ne mange pas le selecteur de longueur');
$tester->assertEqual(str_contains($script, '$.fn.dataTable.Buttons'), True,
    'sans l extension Buttons on retire le bouton au lieu de planter');
$tester->assertEqual(Paginator::csvButton()['extend'], 'csv', 'csvButton est reutilisable');

$script = $p->renderDatatableScript(['buttons' => [Paginator::csvButton(), 'copy']]);
$tester->assertEqual(str_contains($script, '"copy"'), True, 'on peut ajouter d autres boutons');

// Assets : on detecte ce qui est reellement livre dans public/plugin/datatables/
$assets = Paginator::datatableAssets();
$tester->assertEqual(str_contains($assets, '/plugin/datatables/dataTables.min.js'), True, 'le js DataTables est charge');
$tester->assertEqual(str_contains($assets, 'dataTables.bootstrap4.min.css'), True, 'le css de l integration livree est charge');
$tester->assertEqual(str_contains($assets, 'dataTables.bootstrap4.min.js'), True, 'le js de l integration livree est charge');
$tester->assertEqual(str_contains($assets, 'dataTables.buttons.min.js'), True, 'Buttons est charge par defaut (export CSV)');
$tester->assertEqual(str_contains($assets, '<link rel="stylesheet"'), True, 'les assets chargent le css');
$tester->assertEqual(str_contains(Paginator::datatableAssets('/vendor/dt/'), '/vendor/dt/'), True,
    'le chemin des assets est parametrable');

$tester->assertEqual(str_contains($assets, 'buttons.html5.min.js'), True, 'l export html5 est charge par defaut');
$tester->assertEqual(str_contains(Paginator::datatableAssets(with_buttons: False), 'buttons'), False,
    'on peut se passer de Buttons');

// Detection : un dossier sans aucun fichier retombe sur le premier decoupage
$assets = Paginator::datatableAssets('/plugin/inexistant');
$tester->assertEqual(str_contains($assets, '/plugin/inexistant/dataTables.min.js'), True,
    'sans fichier sur le disque on garde le decoupage par defaut');

// Langue : le fr-FR.json livre est utilise tel quel
$GLOBALS['i18n']->setLocale('fr');
$tester->assertEqual(Paginator::datatableLanguageFile(), '/plugin/datatables/i18n/fr-FR.json',
    'le fichier de langue livre est trouve');
$tester->assertEqual(Paginator::datatableLanguage(), ['url' => '/plugin/datatables/i18n/fr-FR.json'],
    'DataTables charge le fichier de langue lui-meme');
$tester->assertEqual(str_contains($p->renderDatatableScript(), 'i18n/fr-FR.json'), True,
    'le script d init pointe sur le fichier de langue');

// Pas de fichier de langue : repli sur nos chaines
$tester->assertEqual(Paginator::datatableLanguageFile('/plugin/inexistant'), null,
    'pas de fichier de langue : null');
$fallback = Paginator::datatableLanguage('/plugin/inexistant');
$tester->assertEqual(isset($fallback['paginate']['next']), True, 'repli sur les libelles I18n');

unset($_GET['page']);

$tester->footer();
