<?php

namespace OsdAurox;

use PDO;

/**
 * Paginator : pagination SQL (LIMIT / OFFSET) ou tableau DataTables.
 *
 * Deux modes, la même page d'appel :
 *
 *   // 1. Pagination SQL classique, 25 lignes par page par défaut.
 *   //    ?per_page= dans l'URL reste prioritaire sur ce défaut.
 *   $p = new Paginator($pdo, $query, $countQuery, '/clients.php', per_page: 25);
 *
 *   // 2. Tout en mémoire du navigateur : select complet, pagination,
 *   //    recherche, tri et export CSV par DataTables.
 *   $p = new Paginator($pdo, $query, $countQuery, '/clients.php',
 *                      per_page: 25, as_datatable: True, datatable_id: 'clients',
 *                      datatable_search_fields: ['nom', 'ville'],
 *                      datatable_stripped: True);
 *
 *   foreach ($p->getItems() as $row) { ... }
 *   echo $p->renderTotalInfo();        // vide en mode DataTables
 *   echo $p->renderPerPageSelector();  // vide en mode DataTables
 *   echo $p->renderPagination();       // boutons, ou script d'init DataTables
 *
 * La recherche POST (search_text) et celle de DataTables ne cohabitent pas.
 * Construire un Paginator en mode DataTables bascule TOUTE la page : à partir
 * de là Forms::searchFormRow() ne rend plus rien, où qu'il soit appelé, et
 * Paginator::searchText() retourne null pour que la requête sorte toutes les
 * lignes. Rien d'autre à changer dans une page existante.
 *
 *   $search = Paginator::searchText();   // null dès qu'un tableau est en DataTables
 *   $sql = BrutalCrud::listQueries($table, $search ? "%$search%" : null);
 *
 * En mode DataTables le <table> doit porter l'id attendu, et les <th> leur
 * data-field si on veut limiter la recherche à certaines colonnes :
 *
 *   <table id="clients" class="table">
 *     <thead><tr><th data-field="nom">Nom</th><th data-field="ville">Ville</th>
 *                <th>Créé le</th></tr></thead>
 *
 * datatable_search_fields accepte aussi des index de colonne ([0, 1]), et
 * vide (défaut) laisse DataTables chercher partout.
 *
 * Le <head> doit charger jQuery puis DataTables, servi en local depuis
 * public/plugin/datatables/ comme select2 :
 *
 *   echo Paginator::datatableAssets();                       // + Buttons (export CSV)
 *   echo Paginator::datatableAssets(with_buttons: False);    // sans l'export
 *
 * Les libellés viennent de public/plugin/datatables/i18n/<locale>.json s'il
 * est là (fr-FR.json est livré), sinon de nos propres chaînes via I18n.
 *
 * Le bouton d'export CSV est toujours rendu. Pour en mettre d'autres, ou pour
 * l'enlever, on surcharge les options :
 *
 *   echo $p->renderDatatableScript([
 *       'buttons' => [Paginator::csvButton(), 'copy'],
 *   ]);
 *
 * ⚠️ Le mode DataTables sort TOUTES les lignes de la requête : c'est fait pour
 * des listes de quelques milliers de lignes au plus. Au-delà, pagination SQL.
 */
class Paginator
{
    /** Nombre d'elements par page si l'appelant n'en impose pas un. */
    public const DEFAULT_PER_PAGE = 200;

    /** Choix proposes dans le selecteur "par page" et dans le lengthMenu DataTables. */
    public const PER_PAGE_OPTIONS = [10, 20, 50, 100, 500, 1000];

    /** Id du <table> vise par l'init DataTables si l'appelant n'en donne pas. */
    public const DEFAULT_DATATABLE_ID = 'aurox-datatable';

    /** Repertoire ou sont deposes les fichiers DataTables (comme select2). */
    public const DATATABLE_ASSETS_PATH = '/plugin/datatables';

    /**
     * Decoupages de fichiers livres par datatables.net, par ordre de preference.
     * Le premier dont les fichiers existent sous public/ est utilise ; le
     * premier de la liste sert de repli si on ne trouve rien.
     *
     *  1. ce qui est livre dans public/plugin/datatables/ (DataTables 2 + bootstrap4)
     *  2. la meme chose en integration bootstrap5
     *  3. bundle du "download builder" : un css + un js, tout dedans
     *  4. fichiers ranges en css/ et js/ (build bootstrap5)
     *  5. DataTables seul, sans integration bootstrap
     */
    public const DATATABLE_LAYOUTS = [
            ['dataTables.bootstrap4.min.css', 'dataTables.min.js', 'dataTables.bootstrap4.min.js'],
            ['dataTables.bootstrap5.min.css', 'dataTables.min.js', 'dataTables.bootstrap5.min.js'],
            ['datatables.min.css', 'datatables.min.js'],
            ['css/dataTables.bootstrap5.min.css', 'js/dataTables.min.js', 'js/dataTables.bootstrap5.min.js'],
            ['dataTables.dataTables.min.css', 'dataTables.min.js'],
    ];

    /** Extension Buttons (export csv / excel / copie), chargee a la demande. */
    public const DATATABLE_BUTTONS = [
//        'buttons.dataTables.min.css',
            'buttons.bootstrap4.min.css',
            'dataTables.buttons.min.js',
            'buttons.bootstrap4.js',
            'buttons.html5.min.js',
    ];

    /**
     * True des qu'un Paginator de la page tourne en mode DataTables.
     *
     * C'est ce qui permet a Forms::searchFormRow() de se taire tout seul : la
     * recherche POST et celle de DataTables ne peuvent pas cohabiter, et le
     * formulaire est souvent rendu loin du paginator.
     */
    private static bool $datatableMode = False;

    private PDO $pdo;
    private string $query;       // Requête pour récupérer les données (avec LIMIT et OFFSET)
    private string $countQuery;  // Requête pour le total (sans LIMIT ni OFFSET)
    private array $bindParams;   // Paramètres à binder
    private int $currentPage;    // Page actuelle
    private int $perPage;        // Nombre d'éléments par page
    private int $defaultPerPage; // Valeur par défaut si ?per_page absent de l'URL
    private int $totalItems;     // Total d'éléments dans la base
    private int $totalPages;     // Total de pages
    private string $url;
    private bool $asDatatable;   // True : select complet, pagination et recherche côté DataTables
    private string $datatableId; // Id du <table> à initialiser en mode DataTables
    private array $datatableSearchFields; // Colonnes cherchées par DataTables (vide = toutes)
    private bool $datatableStripped;      // Zébrage des lignes (table-striped)

    /**
     * Constructeur
     *
     * @param PDO $pdo Instance PDO
     * @param string $query Requête SQL principale (doit inclure `LIMIT :limit OFFSET :offset`)
     * @param string $count_query Requête SQL pour compter le total des éléments
     * @param string $url URL de base (exemple : `company_list.php`)
     * @param array $bind_params Paramètres à binder (pour filtrage)
     * @param int $per_page Nombre d'éléments par page par défaut (écrasé par ?per_page dans l'URL)
     * @param bool $as_datatable True : on sort TOUTES les lignes et c'est DataTables qui
     *                           pagine, cherche et trie côté navigateur
     * @param string $datatable_id Id du <table> à initialiser en mode DataTables
     * @param array $datatable_search_fields Colonnes cherchées par DataTables :
     *              noms (attribut data-field du <th>) ou index. Vide = toutes.
     * @param bool $datatable_stripped Zébrage des lignes (classe table-striped)
     */
    public function __construct(
            PDO    $pdo,
            string $query,
            string $count_query,
            string $url,
            array  $bind_params = [],
            int    $per_page = self::DEFAULT_PER_PAGE,
            bool   $as_datatable = False,
            string $datatable_id = self::DEFAULT_DATATABLE_ID,
            array  $datatable_search_fields = [],
            bool   $datatable_stripped = False,
    )
    {
        $this->pdo = $pdo;
        $this->query = $query;
        $this->countQuery = $count_query;
        $this->bindParams = $bind_params;
        $this->defaultPerPage = $per_page > 0 ? $per_page : self::DEFAULT_PER_PAGE;
        $this->perPage = max(1, Sec::getPerPage($this->defaultPerPage));
        $this->currentPage = max(1, Sec::getPage()); // Minimum 1
        $this->asDatatable = $as_datatable;
        if ($as_datatable) {
            self::$datatableMode = True;
        }
        $this->datatableId = self::cleanId($datatable_id);
        $this->datatableStripped = $datatable_stripped;
        $this->datatableSearchFields = self::cleanSearchFields($datatable_search_fields);
        $this->totalItems = $this->calculateTotalItems();
        $this->totalPages = (int)ceil($this->totalItems / $this->perPage);
        $this->url = $url;
    }

    /**
     * Nettoie un id HTML : on ne laisse passer que ce qui est injectable sans
     * risque dans un sélecteur jQuery et dans un attribut id.
     */
    private static function cleanId(string $id): string
    {
        $clean = preg_replace('/[^A-Za-z0-9_\-]/', '', $id);
        return $clean !== '' ? $clean : self::DEFAULT_DATATABLE_ID;
    }

    /**
     * Normalise la liste des colonnes cherchables : des chaînes, qu'il s'agisse
     * d'un data-field ou d'un index de colonne. C'est le script d'init qui fera
     * la correspondance dans le <thead>.
     *
     * @param array $fields
     * @return string[]
     */
    private static function cleanSearchFields(array $fields): array
    {
        $clean = [];
        foreach ($fields as $field) {
            if (!is_scalar($field)) {
                continue;
            }
            $field = trim((string)$field);
            if ($field !== '') {
                $clean[] = $field;
            }
        }
        return array_values(array_unique($clean));
    }

    /**
     * Calcule le nombre total d'éléments dans la base
     *
     * @return int
     */
    private function calculateTotalItems(): int
    {
        $stmt = $this->pdo->prepare($this->countQuery);

        // Binder les paramètres nécessaires
        foreach ($this->bindParams as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Récupère les éléments pour la page actuelle.
     *
     * En mode DataTables on retourne TOUTES les lignes : c'est le navigateur
     * qui pagine. À réserver aux volumes raisonnables (quelques milliers de
     * lignes), sinon on reste sur la pagination SQL.
     *
     * @return array
     */
    public function getItems(): array
    {
        if ($this->asDatatable) {
            $stmt = $this->pdo->prepare($this->query);

            foreach ($this->bindParams as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if (!str_contains($this->query, 'LIMIT') && !str_contains($this->query, 'OFFSET')) {
            $this->query .= ' LIMIT :limit OFFSET :offset';
        }

        $stmt = $this->pdo->prepare($this->query);

        // Calcul des paramètres pour LIMIT et OFFSET
        $offset = ($this->currentPage - 1) * $this->perPage;

        // Ajouter LIMIT et OFFSET
        $stmt->bindValue(':limit', $this->perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        // Ajouter les autres paramètres
        foreach ($this->bindParams as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Génère les boutons de pagination.
     *
     * En mode DataTables il n'y a rien à paginer côté serveur : on retourne
     * à la place le script d'initialisation du tableau. Une page écrite pour
     * le Paginator classique fonctionne donc sans y toucher.
     *
     * @return string HTML des boutons de pagination
     */
    public function renderPagination(): string
    {
        if ($this->asDatatable) {
            return $this->renderDatatableScript();
        }

        $perPage = $this->perPage;

        // Construire les parties de l'URL et les paramètres existants
        $url = str_contains($this->url, '?') ? '&per_page=' : '?per_page=';

        $url .= $perPage . '&page=';

        $baseUrl = $this->url . $url;

        // Reconstruire l'URL de base

        $html = '<div class="row">';
        $html .= '<div class="col-12 d-flex justify-content-center">';

        $html .= '<nav><ul class="pagination">';

        // Bouton "Début |<"
        if ($this->currentPage > 1) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '1">&laquo;&laquo;</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">&laquo;&laquo;</span></li>';
        }

        // Bouton "Précédent <"
        if ($this->currentPage > 1) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . ($this->currentPage - 1) . '">&laquo;</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
        }

        // Boutons pour les pages principales
        $startPage = max(1, $this->currentPage - 5);
        $endPage = min($this->totalPages, $this->currentPage + 4);

        for ($page = $startPage; $page <= $endPage; $page++) {
            if ($page == $this->currentPage) {
                $html .= '<li class="page-item active"><span class="page-link">' . $page . '</span></li>';
            } else {
                $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . $page . '">' . $page . '</a></li>';
            }
        }

        // Bouton "Suivant >"
        if ($this->currentPage < $this->totalPages) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . ($this->currentPage + 1) . '">&raquo;</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
        }

        // Bouton "Fin >|"
        if ($this->currentPage < $this->totalPages) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . $this->totalPages . '">&raquo;&raquo;</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">&raquo;&raquo;</span></li>';
        }

        $html .= '</ul></nav>';
        $html .= '</div>'; // Fin de col-12
        $html .= '</div>'; // Fin de row

        return $html;
    }

    /**
     * Récupère le nombre total d'éléments
     *
     * @return int
     */
    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    /**
     * Récupère le nombre total de pages
     *
     * @return int
     */
    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    /**
     * Récupère la page actuelle
     *
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * Nombre d'éléments par page réellement utilisé (URL ou valeur par défaut)
     *
     * @return int
     */
    public function getPerPage(): int
    {
        return $this->perPage;
    }

    /**
     * Nombre d'éléments par page par défaut (celui passé au constructeur)
     *
     * @return int
     */
    public function getDefaultPerPage(): int
    {
        return $this->defaultPerPage;
    }

    /**
     * True si le paginator tourne en mode DataTables (select complet)
     *
     * @return bool
     */
    public function isDatatable(): bool
    {
        return $this->asDatatable;
    }

    /**
     * Id du <table> initialisé en mode DataTables
     *
     * @return string
     */
    public function getDatatableId(): string
    {
        return $this->datatableId;
    }

    /**
     * Colonnes cherchées par DataTables (vide = toutes)
     *
     * @return string[]
     */
    public function getDatatableSearchFields(): array
    {
        return $this->datatableSearchFields;
    }

    /**
     * True si les lignes sont zébrées (table-striped)
     *
     * @return bool
     */
    public function isDatatableStripped(): bool
    {
        return $this->datatableStripped;
    }

    /**
     * True dès qu'un Paginator de la page tourne en mode DataTables.
     *
     * Forms::searchFormRow() s'en sert pour ne pas rendre un formulaire de
     * recherche POST qui ferait doublon avec celui de DataTables.
     *
     * @return bool
     */
    public static function isDatatableMode(): bool
    {
        return self::$datatableMode;
    }

    /**
     * Remet le mode à zéro (tests, ou page qui rend plusieurs tableaux).
     *
     * @return void
     */
    public static function resetDatatableMode(): void
    {
        self::$datatableMode = False;
    }

    public function renderTotalInfo()
    {
        // En mode DataTables, c'est DataTables qui affiche "x à y sur n".
        if ($this->asDatatable) {
            return '';
        }

        $totalItems = $this->totalItems;
        $totalPages = $this->totalPages;
        $perPage = $this->perPage;

        $html = '<div class="row">';
        $html .= '<div class="col-12 d-flex justify-content-center ">';
        $html .= '<p>' . I18n::t('Affichage de ') . $perPage . I18n::t(' résultats sur un total de ') . $totalItems . ' - ' . $totalPages . ' ' . I18n::t('pages') . '</h3>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    /**
     * Génère un formulaire pour sélectionner le nombre d'éléments par page.
     *
     * En mode DataTables le sélecteur est fourni par DataTables lui-même :
     * on ne rend rien.
     *
     * @return string HTML du sélecteur
     */
    public function renderPerPageSelector(): string
    {
        if ($this->asDatatable) {
            return '';
        }

        $options = self::perPageOptions($this->defaultPerPage, $this->perPage);

        // Ajouter le HTML pour le sélecteur
        $html = '<form method="get" class="form-inline d-inline-block">';
        $html .= '<label for="perPageSelector" class="mr-2">' . I18n::t('Afficher par page :') . '</label>';
        $html .= '<input type="hidden" name="page" value="' . Sec::getPage() . '">';
        $html .= self::hiddenUrlParams($this->url);
        $html .= '<select id="perPageSelector" name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">';

        foreach ($options as $option) {
            $selected = $option == $this->perPage ? ' selected' : ''; // Rendre l'option sélectionnée
            $html .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
        }

        $html .= '</select>';
        $html .= '</form>';

        return $html;
    }

    /**
     * Rejoue les paramètres de l'URL du paginator (?t=, ?search_text=...) en
     * champs cachés : un formulaire GET remplace toute la query string, sans ça
     * le sélecteur de page perdrait le contexte de la liste.
     *
     * @param string $url
     * @return string
     */
    private static function hiddenUrlParams(string $url): string
    {
        parse_str((string)parse_url($url, PHP_URL_QUERY), $params);
        unset($params['page'], $params['per_page']);

        $html = '';
        foreach ($params as $name => $value) {
            if (!is_scalar($value)) {
                continue;
            }
            $html .= '<input type="hidden" name="' . Sec::hNoHtml($name)
                    . '" value="' . Sec::hNoHtml($value) . '">';
        }
        return $html;
    }

    /**
     * Liste des tailles de page proposées : les options standard plus, le cas
     * échéant, le défaut de l'appelant et la valeur courante.
     *
     * @return int[]
     */
    public static function perPageOptions(int ...$extra): array
    {
        $options = array_merge(self::PER_PAGE_OPTIONS, $extra);
        $options = array_values(array_unique(array_filter($options, fn($o) => $o > 0)));
        sort($options);
        return $options;
    }

    // -------------------------------------------------------------------------
    // Mode DataTables
    // -------------------------------------------------------------------------

    /**
     * Balises à mettre dans le <head> pour charger DataTables.
     *
     * Les fichiers sont servis en local, comme select2, depuis
     * public/plugin/datatables/. Les découpages livrés par datatables.net
     * ne se ressemblent pas d'une version à l'autre : on prend le premier
     * dont les fichiers sont réellement sur le disque, sinon le premier de
     * la liste.
     *
     * @param string $base Chemin web du dossier DataTables
     * @param bool $with_buttons Charge l'extension Buttons (export CSV), active
     *             par defaut puisque le bouton est toujours rendu
     * @return string
     */
    public static function datatableAssets(
            string $base = self::DATATABLE_ASSETS_PATH,
            bool   $with_buttons = True,
    ): string
    {
        $base = '/' . trim(Sec::hNoHtml($base), '/');

        $files = self::DATATABLE_LAYOUTS[0];
        foreach (self::DATATABLE_LAYOUTS as $layout) {
            if (self::layoutExists($base, $layout)) {
                $files = $layout;
                break;
            }
        }

        if ($with_buttons) {
            $files = array_merge($files, self::DATATABLE_BUTTONS);
        }

        $html = '';
        foreach ($files as $file) {
            $href = $base . '/' . $file;
            $html .= str_ends_with($file, '.css')
                    ? '<link rel="stylesheet" href="' . $href . '">' . "\r"
                    : '<script src="' . $href . '"></script>' . "\r";
        }

        return $html;
    }

    /**
     * True si tous les fichiers d'un découpage sont présents sous public/.
     *
     * @param string $base Chemin web du dossier DataTables
     * @param string[] $layout Fichiers à vérifier
     */
    private static function layoutExists(string $base, array $layout): bool
    {
        if (!defined('APP_ROOT')) {
            return False;
        }

        $dir = APP_ROOT . '/public' . $base;
        foreach ($layout as $file) {
            if (!is_file($dir . '/' . $file)) {
                return False;
            }
        }
        return True;
    }

    /**
     * Script d'initialisation DataTables sur le <table> du paginator.
     *
     * jQuery et DataTables doivent être chargés avant (voir datatableAssets()).
     * Les options passées écrasent les options par défaut.
     *
     * @param array $options Options DataTables supplémentaires
     * @return string
     */
    public function renderDatatableScript(array $options = []): string
    {
        $lengths = self::perPageOptions($this->defaultPerPage, $this->perPage);

        $defaults = [
                'pageLength' => $this->perPage,
                'lengthMenu' => $lengths,
                'searching'  => True,
                'paging'     => True,
                'info'       => True,
                'order'      => [], // on garde l'ordre du ORDER BY SQL
                'language'   => self::datatableLanguage(),
                'layout'     => ['topStart' => ['pageLength', 'buttons']],
                'buttons'    => [self::csvButton()],
        ];

        if (!$this->datatableStripped) {
            $defaults['stripeClasses'] = [];
        }

        $config = array_replace($defaults, $options);

        $json = self::toJson($config);
        $fields = self::toJson($this->datatableSearchFields);

        $html = '<script nonce="' . Sec::noneCsp() . '">' . "\r";
        $html .= '$(function () {' . "\r";
        $html .= 'let t = $("#' . $this->datatableId . '");' . "\r";
        // Sans les assets dans le <head>, on le dit au lieu de planter.
        $html .= 'if (!$.fn.DataTable) { console.warn("DataTables absent : ajoutez Paginator::datatableAssets() dans le <head>"); return; }' . "\r";
        $html .= 'if (!t.length) { console.warn("DataTables : aucun tableau #' . $this->datatableId . ' dans la page, ajoutez l\'id sur le <table>"); return; }' . "\r";
        $html .= 'if ($.fn.DataTable.isDataTable(t)) { return; }' . "\r";
        // Zebrage : la classe bootstrap d'un cote, les classes DataTables de l'autre.
        $html .= 't.toggleClass("table-striped", ' . ($this->datatableStripped ? 'true' : 'false') . ');' . "\r";
        $html .= 'let cfg = ' . $json . ';' . "\r";
        // Sans l'extension Buttons chargee, on retire le bouton plutot que de
        // planter l'init sur une feature inconnue.
        $html .= 'if (!$.fn.dataTable.Buttons) { delete cfg.buttons; cfg.layout = {topStart: "pageLength"}; }' . "\r";
        // Recherche limitee a certaines colonnes : on resout les data-field du
        // <thead> en index, et on eteint les autres colonnes.
        $html .= 'let fields = ' . $fields . ';' . "\r";
        $html .= 'if (fields.length) {' . "\r";
        $html .= 'let off = [], on = 0;' . "\r";
        $html .= 't.find("thead tr").first().find("th").each(function (i) {' . "\r";
        $html .= 'let f = $(this).attr("data-field") || "";' . "\r";
        $html .= 'if (fields.indexOf(f) === -1 && fields.indexOf(String(i)) === -1) { off.push(i); } else { on++; }' . "\r";
        $html .= '});' . "\r";
        // Aucune colonne reconnue : on cherche partout plutot que nulle part.
        $html .= 'if (!on) { console.warn("DataTables : datatable_search_fields ne correspond a aucune colonne, ajoutez data-field sur les <th>"); }' . "\r";
        $html .= 'else if (off.length) { cfg.columnDefs = (cfg.columnDefs || []).concat([{targets: off, searchable: false}]); }' . "\r";
        $html .= '}' . "\r";
        $html .= 't.DataTable(cfg);' . "\r";
        $html .= '});' . "\r";
        $html .= '$.fn.dataTable.Buttons.defaults.dom.button.className = "btn";' . "\r";
        $html .= '</script>' . "\r";

        return $html;
    }

    /**
     * Bouton d'export CSV, toujours present en mode DataTables.
     *
     * @return array
     */
    public static function csvButton(): array
    {
        return [
                'extend'    => 'csv',
                'text'      => I18n::t('Export CSV', safe: True),
                'className' => 'btn btn-sm btn-outline-secondary',
        ];
    }

    /**
     * json_encode cale pour une injection dans un <script> : les chevrons et
     * les quotes partent en \u00XX, donc pas de sortie de balise possible.
     *
     * @param mixed $value
     * @return string
     */
    private static function toJson(mixed $value): string
    {
        $json = json_encode(
                $value,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        return $json !== False ? $json : '[]';
    }

    /**
     * Formulaire de recherche POST du paginator classique.
     *
     * En mode DataTables on ne rend rien : la recherche est celle de
     * DataTables, cote navigateur, sur les lignes deja chargees.
     *
     * @param string|null $search_text Texte a reafficher dans le champ
     * @return string
     */
    public function renderSearchForm(?string $search_text = null): string
    {
        if ($this->asDatatable) {
            return '';
        }

        return Forms::searchFormRow($search_text ?? self::searchText());
    }

    /**
     * Texte cherche, envoye par le formulaire POST (champ search_text).
     *
     * En mode DataTables il n'y a pas de recherche serveur : on retourne null
     * pour que la requete sorte toutes les lignes.
     *
     *   $search = Paginator::searchText($asDatatable);
     *   $sql = BrutalCrud::listQueries($table, $search ? "%$search%" : null);
     *
     * @param bool|null $as_datatable Null : on suit le mode de la page
     * @return string|null
     */
    public static function searchText(?bool $as_datatable = null): ?string
    {
        if ($as_datatable ?? self::$datatableMode) {
            return null;
        }

        $text = $_POST['search_text'] ?? $_GET['search_text'] ?? null;
        if (!is_scalar($text)) {
            return null;
        }

        $text = trim((string)$text);
        return $text !== '' ? $text : null;
    }

    /**
     * Libellés DataTables.
     *
     * Si le fichier de langue de datatables.net est présent
     * (public/plugin/datatables/i18n/fr-FR.json), on le laisse faire le
     * travail : DataTables le charge tout seul. Sinon, repli sur nos propres
     * libellés, passés par I18n pour rester traduisibles.
     *
     * @param string $base Chemin web du dossier DataTables
     * @return array
     */
    public static function datatableLanguage(string $base = self::DATATABLE_ASSETS_PATH): array
    {
        $file = self::datatableLanguageFile($base);
        if ($file) {
            return ['url' => $file];
        }

        return [
                'search'      => I18n::t('Rechercher :', safe: True),
                'lengthMenu'  => I18n::t('Afficher _MENU_ lignes', safe: True),
                'info'        => I18n::t('Lignes _START_ à _END_ sur _TOTAL_', safe: True),
                'infoEmpty'   => I18n::t('Aucune ligne', safe: True),
                'infoFiltered' => I18n::t('(filtré sur _MAX_ lignes au total)', safe: True),
                'zeroRecords' => I18n::t('Aucun résultat', safe: True),
                'emptyTable'  => I18n::t('Aucune donnée', safe: True),
                'paginate'    => [
                        'first'    => I18n::t('Début', safe: True),
                        'previous' => I18n::t('Précédent', safe: True),
                        'next'     => I18n::t('Suivant', safe: True),
                        'last'     => I18n::t('Fin', safe: True),
                ],
        ];
    }

    /**
     * Cherche le fichier de langue datatables.net correspondant à la locale
     * courante (i18n/fr-FR.json pour 'fr'). Retourne son URL, ou null.
     *
     * @param string $base Chemin web du dossier DataTables
     * @return string|null
     */
    public static function datatableLanguageFile(string $base = self::DATATABLE_ASSETS_PATH): ?string
    {
        if (!defined('APP_ROOT')) {
            return null;
        }

        $locale = isset($GLOBALS['i18n']) ? I18n::currentLocale() : 'fr';
        $locale = preg_replace('/[^a-z]/', '', strtolower((string)$locale));
        if (!$locale) {
            return null;
        }

        $base = '/' . trim(Sec::hNoHtml($base), '/');
        $dir = APP_ROOT . '/public' . $base . '/i18n';

        // fr.json, puis fr-FR.json / fr-CA.json ... : on prend ce qui existe
        $variants = glob($dir . '/' . $locale . '-*.json') ?: [];
        $found = is_file($dir . '/' . $locale . '.json')
                ? $dir . '/' . $locale . '.json'
                : ($variants[0] ?? null);

        return $found ? $base . '/i18n/' . basename($found) : null;
    }
}