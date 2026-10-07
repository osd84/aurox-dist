<?php

namespace OsdAurox;

use PDO;
use PDOException;

/**
 * BrutalCrud : un CRUD générique pour les tables très simples.
 *
 * Assumé et volontairement limité : une table, des champs scalaires, pas de
 * relation many-to-many, pas d'upload, pas de sous-formulaire. Pour tout ce
 * qui dépasse, on écrit la page à la main.
 *
 * Une table gérée doit respecter le socle décrit dans la doc BaseModel :
 * id, created_at, updated_at, created_by, updated_by.
 *
 * Conforme à la doctrine aurox : pas d'objet, que des méthodes statiques et
 * des tableaux. Les définitions de champs sont des règles au format
 * BaseModel::getRules(), donc directement consommables par Validator.
 *
 * ⚠️ Le nom de table est interpolé dans le SQL. Il ne doit JAMAIS venir d'une
 * saisie utilisateur : il vient du registre, et tableOrDie() le vérifie.
 *
 * Exemple de registre :
 *
 *   $registry = [
 *       'zonage' => [
 *           'table' => 'zonage',
 *           'un'    => 'zonage',
 *           'des'   => 'Zonages',
 *           'fields' => [
 *               'nom' => [
 *                   'type' => 'varchar', 'required' => True, 'maxLength' => 255,
 *                   'label' => 'Nom', 'unique' => True, 'searchable' => True,
 *                   'inList' => True, 'upper' => True,
 *               ],
 *           ],
 *       ],
 *   ];
 */
class BrutalCrud
{
    /** Types de champs gérés par le CRUD générique. */
    public const TYPES = ['varchar', 'text', 'int', 'integer', 'float', 'bool', 'boolean', 'fk'];

    /**
     * Clés propres à BrutalCrud, ajoutées aux règles Field.
     * Field les ignore (il lit ses clés en ?? null), on peut donc passer le
     * même tableau à Validator sans le nettoyer.
     *
     *  unique       => unicité vérifiée en base avant écriture
     *  inList       => colonne affichée dans la liste
     *  searchable   => champ interrogé par la recherche
     *  upper        => valeur forcée en majuscules à la saisie
     *  fkLabelField => colonne à afficher dans un select fk (défaut : nom)
     *
     * Attention à ne pas confondre fkLabelField et fkFieldName : fkFieldName
     * est la colonne POINTÉE par la valeur stockée, c'est ce que Validator
     * interroge pour vérifier la fk, et c'est donc 'id' dans la quasi-totalité
     * des cas. fkLabelField est seulement ce qu'on montre à l'utilisateur.
     */
    public const OWN_KEYS = ['unique', 'inList', 'searchable', 'upper', 'fkLabelField'];

    /** Motif d'un nom de table ou de colonne acceptable. */
    public const NAME_PATTERN = '/^[a-z][a-z0-9_]{0,62}$/';

    // -------------------------------------------------------------------------
    // Registre
    // -------------------------------------------------------------------------

    /**
     * Retourne la définition d'une table du registre, ou null si le slug est
     * inconnu. Le slug est ajouté sous la clé 'slug'.
     */
    public static function table(array $registry, ?string $slug): ?array
    {
        if (!$slug || !array_key_exists($slug, $registry)) {
            return null;
        }
        $table = $registry[$slug];
        $table['slug'] = $slug;
        return $table;
    }

    /**
     * Idem table() mais coupe la requête si le slug est inconnu ou si la
     * définition est invalide. C'est le garde-fou qui garantit que le nom de
     * table interpolé dans le SQL vient bien du registre.
     */
    public static function tableOrDie(array $registry, ?string $slug): array
    {
        $table = self::table($registry, $slug);
        if (!$table) {
            Base::dieOrThrow('BrutalCrud : table inconnue');
        }
        self::checkTableOrDie($table);
        return $table;
    }

    /**
     * Vérifie qu'une définition est utilisable : nom de table et noms de
     * colonnes sûrs, types connus, fk complètes.
     */
    public static function checkTableOrDie(array $table): void
    {
        $name = $table['table'] ?? '';
        if (!preg_match(self::NAME_PATTERN, $name)) {
            Base::dieOrThrow('BrutalCrud : nom de table invalide');
        }

        foreach (($table['fields'] ?? []) as $field => $rule) {
            if (!preg_match(self::NAME_PATTERN, $field)) {
                Base::dieOrThrow('BrutalCrud : nom de colonne invalide : ' . Sec::hNoHtml($field));
            }
            $type = $rule['type'] ?? '';
            if (!in_array($type, self::TYPES, True)) {
                Base::dieOrThrow('BrutalCrud : type non géré : ' . Sec::hNoHtml($type));
            }
            if ($type === 'fk' && !preg_match(self::NAME_PATTERN, $rule['fkTableName'] ?? '')) {
                Base::dieOrThrow('BrutalCrud : fkTableName invalide sur ' . Sec::hNoHtml($field));
            }
        }
    }

    /**
     * Les tables du registre, groupées par leur clé 'groupe', dans l'ordre du
     * registre. Les tables sans groupe tombent dans ''.
     */
    public static function byGroup(array $registry): array
    {
        $out = [];
        foreach ($registry as $slug => $table) {
            $table['slug'] = $slug;
            $out[$table['groupe'] ?? ''][] = $table;
        }
        return $out;
    }

    // -------------------------------------------------------------------------
    // Champs
    // -------------------------------------------------------------------------

    /** Les règles de champs d'une table. */
    public static function fields(array $table): array
    {
        return $table['fields'] ?? [];
    }

    /**
     * Les champs portant un drapeau BrutalCrud à vrai (inList, searchable,
     * unique, upper).
     */
    public static function fieldsWith(array $table, string $flag): array
    {
        $out = [];
        foreach (self::fields($table) as $field => $rule) {
            if (!empty($rule[$flag])) {
                $out[$field] = $rule;
            }
        }
        return $out;
    }

    /** Le premier champ texte de la table, utilisé comme libellé d'une ligne. */
    public static function labelField(array $table): ?string
    {
        foreach (self::fields($table) as $field => $rule) {
            if (in_array($rule['type'] ?? '', ['varchar', 'text'], True)) {
                return $field;
            }
        }
        return array_key_first(self::fields($table));
    }

    /** Libellé lisible d'une ligne, pour les messages et les confirmations. */
    public static function rowLabel(array $table, array $row): string
    {
        $field = self::labelField($table);
        $label = trim((string) ($row[$field] ?? ''));
        return $label !== '' ? $label : ('#' . ($row['id'] ?? '?'));
    }

    // -------------------------------------------------------------------------
    // Lecture du formulaire
    // -------------------------------------------------------------------------

    /**
     * Lit les champs de la table depuis le POST, typés.
     *
     * Une case à cocher absente du POST vaut 0 : c'est la seule façon de
     * distinguer "décochée" de "non soumise" en HTML.
     *
     * Les chaînes vides repartent en null, sauf demande contraire, pour ne pas
     * écrire des '' là où la colonne est nullable.
     */
    public static function readPost(array $table): array
    {
        $datas = [];

        foreach (self::fields($table) as $field => $rule) {
            $type = $rule['type'] ?? 'varchar';

            if (in_array($type, ['bool', 'boolean'], True)) {
                // 0/1 et pas un booléen PHP : compat MySQL/PostgreSQL
                $datas[$field] = empty($_POST[$field]) ? 0 : 1;
                continue;
            }

            if (in_array($type, ['int', 'integer', 'fk'], True)) {
                $raw = trim((string) ($_POST[$field] ?? ''));
                $datas[$field] = ($raw === '') ? null : (int) $raw;
                continue;
            }

            if ($type === 'float') {
                // la virgule décimale est ce que les gens tapent réellement
                $raw = str_replace(',', '.', trim((string) ($_POST[$field] ?? '')));
                $datas[$field] = ($raw === '' || !is_numeric($raw)) ? null : (float) $raw;
                continue;
            }

            // varchar, text
            $raw = (string) Sec::getParam($field, 'nohtml', source: 1, upperCase: !empty($rule['upper']));
            $datas[$field] = ($raw === '') ? null : $raw;
        }

        return $datas;
    }

    /**
     * Valeurs brutes du POST, telles que tapées, pour réafficher un formulaire
     * rejeté sans rendre à l'utilisateur le null issu du rejet.
     */
    public static function rawPost(array $table): array
    {
        $raw = [];
        foreach (self::fields($table) as $field => $rule) {
            $raw[$field] = is_array($_POST[$field] ?? null) ? '' : trim((string) ($_POST[$field] ?? ''));
        }
        return $raw;
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    /**
     * Valide les données via les règles de la table.
     *
     * Les champs vides et non requis sont retirés avant validation : Validator
     * juge la valeur, pas l'absence de valeur, et une colonne nullable a le
     * droit d'être vide.
     */
    public static function validate(array $table, array $datas): FormValidator
    {
        $rules = [];
        $toCheck = [];
        $requiredErrors = [];

        foreach (self::fields($table) as $field => $rule) {
            $type = $rule['type'] ?? 'varchar';

            // readPost() garantit deja 0 ou 1 pour une case a cocher : il n'y a
            // rien a valider, et Validator::validateBoolean() exige un is_bool()
            // strict, incompatible avec le stockage en 0/1 de la maison
            if (in_array($type, ['bool', 'boolean'], True)) {
                continue;
            }

            $value = $datas[$field] ?? null;
            $empty = ($value === null || $value === '');

            if ($empty) {
                if (empty($rule['required'])) {
                    continue;
                }
                // champ requis et vide : on leve nous-meme l'erreur plutot que de
                // passer une valeur absente au Validator, qui lui reprocherait en
                // plus de ne pas etre du bon type et de ne pas avoir la bonne longueur
                $requiredErrors[$field][] = 'field is required';
                continue;
            }

            $rules[$field] = $rule;
            $toCheck[$field] = $value;
        }

        $validator = new FormValidator();
        $validator->addErrors($requiredErrors);
        // toujours appeler validate(), meme sans regle a jouer : FormValidator
        // refuse isValid() tant que validate() n'a pas ete appele une fois.
        // Il tient compte des erreurs deja presentes.
        $validator->validate($toCheck, $rules);
        return $validator;
    }

    /**
     * Erreurs d'unicité sur les champs marqués unique.
     * $excludeId permet à une ligne de ne pas se voir elle-même en édition.
     *
     * @return array<string, string[]> au format attendu par FormValidator::addErrors()
     */
    public static function uniqueErrors(PDO $pdo, array $table, array $datas, ?int $excludeId = null): array
    {
        $errors = [];

        foreach (self::fieldsWith($table, 'unique') as $field => $rule) {
            $value = $datas[$field] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            $sql = 'SELECT id FROM ' . $table['table'] . " WHERE $field = :value";
            $bind = [':value' => $value];
            if ($excludeId) {
                $sql .= ' AND id != :excludeId';
                $bind[':excludeId'] = $excludeId;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($bind);
            if ($stmt->fetchColumn()) {
                $errors[$field][] = 'already exists';
            }
        }

        return $errors;
    }

    // -------------------------------------------------------------------------
    // SQL
    // -------------------------------------------------------------------------

    /**
     * Requêtes liste + total pour Paginator, avec recherche optionnelle sur
     * les champs marqués searchable.
     *
     * @return array{query: string, count_query: string, bind_params: array}
     */
    public static function listQueries(array $table, ?string $searchLike): array
    {
        $name = $table['table'];
        $cols = array_merge(['id'], array_keys(self::fields($table)));
        $select = 'SELECT ' . implode(', ', $cols) . " FROM $name";
        $count = "SELECT COUNT(*) FROM $name";
        $order = ' ORDER BY ' . (self::labelField($table) ?? 'id') . ' ASC';

        $searchable = array_keys(self::fieldsWith($table, 'searchable'));
        if (!$searchLike || !$searchable) {
            return [
                'query'       => $select . $order,
                'count_query' => $count,
                'bind_params' => [],
            ];
        }

        $clauses = [];
        $bind = [];
        $i = 0;
        foreach ($searchable as $field) {
            $i++;
            $clauses[] = "$field LIKE :s$i";
            $bind[":s$i"] = $searchLike;
        }
        $where = ' WHERE (' . implode(' OR ', $clauses) . ')';

        return [
            'query'       => $select . $where . $order,
            'count_query' => $count . $where,
            'bind_params' => $bind,
        ];
    }

    public static function get(PDO $pdo, array $table, int $id): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM ' . $table['table'] . ' WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function count(PDO $pdo, array $table): int
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM ' . $table['table']);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /** Insère une ligne et retourne son id. */
    public static function insert(PDO $pdo, array $table, array $datas, ?int $userId = null): int
    {
        $cols = array_keys(self::fields($table));
        $bind = [];
        foreach ($cols as $col) {
            $bind[':' . $col] = $datas[$col] ?? null;
        }

        $cols[] = 'created_by';
        $cols[] = 'updated_by';
        $bind[':created_by'] = $userId;
        $bind[':updated_by'] = $userId;

        $placeholders = array_map(static fn($c) => ':' . $c, $cols);
        $sql = 'INSERT INTO ' . $table['table'] . ' (' . implode(', ', $cols) . ')'
             . ' VALUES (' . implode(', ', $placeholders) . ')';

        // RETURNING en pgsql : lastInsertId() y exige le nom de la séquence
        if (Dbo::isPgsql()) {
            $stmt = $pdo->prepare($sql . ' RETURNING id');
            $stmt->execute($bind);
            return (int) $stmt->fetchColumn();
        }

        $pdo->prepare($sql)->execute($bind);
        return (int) $pdo->lastInsertId();
    }

    public static function update(PDO $pdo, array $table, int $id, array $datas, ?int $userId = null): bool
    {
        $sets = [];
        $bind = [':id' => $id, ':updated_by' => $userId];

        foreach (array_keys(self::fields($table)) as $col) {
            $sets[] = "$col = :$col";
            $bind[':' . $col] = $datas[$col] ?? null;
        }
        $sets[] = 'updated_by = :updated_by';

        $sql = 'UPDATE ' . $table['table'] . ' SET ' . implode(', ', $sets) . ' WHERE id = :id';
        return $pdo->prepare($sql)->execute($bind);
    }

    /**
     * Supprime une ligne. Retourne False si elle est encore référencée
     * ailleurs : la clé étrangère fait son travail, on ne casse rien et on
     * laisse l'appelant expliquer.
     */
    public static function delete(PDO $pdo, array $table, int $id): bool
    {
        try {
            $stmt = $pdo->prepare('DELETE FROM ' . $table['table'] . ' WHERE id = :id');
            $stmt->execute([':id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            // 23503 = violation de clé étrangère en pgsql, 23000 en mysql
            if (in_array($e->getCode(), ['23503', '23000'], True)) {
                return False;
            }
            throw $e;
        }
    }

    /**
     * Options d'un champ fk : les lignes de la table pointée, sous la forme
     * id => libellé, pour alimenter un select.
     */
    public static function fkOptions(PDO $pdo, array $rule): array
    {
        $fkTable = $rule['fkTableName'] ?? '';
        $keyField = $rule['fkFieldName'] ?? 'id';
        $labelField = $rule['fkLabelField'] ?? 'nom';

        foreach ([$fkTable, $keyField, $labelField] as $name) {
            if (!preg_match(self::NAME_PATTERN, (string) $name)) {
                Base::dieOrThrow('BrutalCrud : fk invalide');
            }
        }

        $stmt = $pdo->prepare("SELECT $keyField, $labelField FROM $fkTable ORDER BY $labelField ASC");
        $stmt->execute();

        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[$row[$keyField]] = $row[$labelField];
        }
        return $out;
    }
}
