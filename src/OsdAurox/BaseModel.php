<?php

namespace OsdAurox;

use Exception;
use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;

/**
 * Classe abstraite fournissant des fonctionnalités de base pour les modèles d'accès aux données.
 *
 * Cette classe implémente le modèle de conception Active Record pour interagir avec la base de données
 * et fournit des méthodes communes pour effectuer des opérations CRUD (Create, Read, Update, Delete).
 *
 * Les classes filles doivent définir la constante TABLE pour spécifier la table de base de données associée.
 *
 * Attention: Certaines méthodes présentent des risques d'injection SQL lorsque les paramètres ne sont pas
 * correctement sécurisés. Ces risques sont documentés dans les méthodes concernées.
 *
 * Conventions de nommage: lire la documentation pour voir les conseils de nommage utilisées sur les méthodes.
 *
 */
abstract class BaseModel {

    /**
     * @var int   Identifiant unique de l'objet
     */
    public ?int $id;



    public const TABLE = "unknown";



    public function getTable(): string
    {
        return static::TABLE;
    }

    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        return null;
    }


    /**
     *
     * Alias pour récupérer un array via FETCH_ASSOC
     * Sqli possible via $select, $id est sécurisé
     *
     * @param $pdo
     * @param $id int safe
     * @param $select string  attention sqli possible
     * @return mixed
     */
    public static function get(\PDO $pdo, mixed $id, string $select = '*'): ?array
    {
        try {
            $table = static::TABLE;
            $stmt = $pdo->prepare("SELECT $select FROM $table WHERE id = :id");
            $id = (int) $id;
            $stmt->execute(['id' => $id]);
            $entity = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (empty($entity)) {
                return null;
            }
            return $entity;
        } catch (PDOException $e) {
            error_log('Database connection error: ' . $e->getMessage());
            throw new RuntimeException('Database connection error.');
        }
    }

    /**
     * Récupère un enregistrement avec ses relations associées depuis la base de données.
     *
     * Cette méthode permet de récupérer un enregistrement spécifique identifié par son ID
     * ainsi que les données des relations associées à cet enregistrement.
     *
     * @param \PDO $pdo Instance de connexion PDO à la base de données
     * @param mixed $id Identifiant unique de l'enregistrement à récupérer
     *
     * @return array|null Tableau contenant l'enregistrement avec ses relations ou null si non trouvé
     *
     * @throws Exception Si la méthode n'est pas implémentée
     */
    public static function getWithRelations(\PDO $pdo, mixed $id): ?array
    {
        throw new Exception('Not implemented');
    }

    /**
     * Vérifie si un enregistrement avec l'ID spécifié existe dans la table
     *
     * @param PDO $pdo
     * @param int $id sécurisé
     * @return bool true si l'enregistrement existe, false sinon
     * @throws RuntimeException Si une erreur de connexion à la base de données survient
     */
    public static function exist(\PDO $pdo, mixed $id): bool
    {
        try {
            if (!filter_var($id, FILTER_VALIDATE_INT)) {
                return false;
            }
            $id = (int) $id;
            if(empty($id)) {
                return false;
            }

            $table = static::TABLE;
            $stmt = $pdo->prepare("SELECT id FROM $table WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('Database connection error: ' . $e->getMessage());
            throw new RuntimeException('Database connection error.');
        }
    }

    /**
     *
     * Permet de retourner un array via FETCH_ASSOC en cherchant par un champ spécifique
     *
     * $field filtré par liste blanche via Sec::sqlIdent()
     *
     * @param string $field le nom de la colonne où chercher, liste blanche via Sec::sqlIdent()
     * @param mixed $value  sécurisé, la valeur qu'on cherche
     *
     * @return array|false L'enregistrement récupéré sous forme de tableau associatif, ou false si aucun enregistrement n'est trouvé.
     * * @throws RuntimeException Si une erreur de connexion à la base de données survient.
     */
    public static function getBy(\PDO $pdo, string $field, mixed $value): ?array
    {
        try {
            $table = static::TABLE;
            $field = Sec::sqlIdent($field);
            $stmt = $pdo->prepare("SELECT * FROM $table WHERE $field = :search");
            $stmt->execute(['search' => $value]);
            $entity = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (empty($entity)) {
                return null;
            }
            return $entity;

        } catch (PDOException $e) {
            error_log('Database connection error: ' . $e->getMessage());
            throw new RuntimeException('Database connection error.');
        }
    }


    /**
     * Récupère tous les enregistrements avec options de tri et limite
     *
     * @param PDO $pdo
     * @param string|null $orderBy nom de colonne, liste blanche via Sec::sqlIdent()
     * @param string $orderDir sécurisé, 'ASC' ou 'DESC'
     * @param int|null $limit sécurisé, Nombre maximum d'enregistrements à retourner
     * @return array
     * @throws RuntimeException Si une erreur de connexion à la base de données survient
     */
    public static function getAll(PDO $pdo, ?string $orderBy = 'id', string $orderDir = 'ASC', ?int $limit = 100): array {
        try {
            $table = static::TABLE;
            $sql = "SELECT * FROM $table";

            if ($orderBy !== null) {
                $orderBy = Sec::sqlIdent($orderBy);
                $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
                $sql .= " ORDER BY $orderBy $orderDir";
            }

            if ($limit !== null) {
                $sql .= " LIMIT :limit";
            }

            $stmt = $pdo->prepare($sql);

            if ($limit !== null) {
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            if(in_array($e->getCode(), ['42S22', '42703'], true)) {
                throw new RuntimeException('Column `' . Sec::hNoHtml($orderBy) . '` in table `' . Sec::hNoHtml(static::TABLE) . '` does not exist');
            }
            error_log('Database connection error: ' . $e->getMessage());
            throw new RuntimeException('Database connection error.');
        }
    }


    /**
     * Retourne tous les enregistrements correspondant à un critère de recherche
     *
     * @param PDO $pdo
     * @param string $field nom de colonne, liste blanche via Sec::sqlIdent()
     * @param mixed $value Valeur sécurisée
     * @param string|null $orderBy nom de colonne, liste blanche via Sec::sqlIdent()
     * @param string $orderDir 'ASC' ou 'DESC'
     * @param int|null $limit Nombre maximum d'enregistrements à retourner
     * @return array
     * @throws RuntimeException Si une erreur de connexion à la base de données survient
     */
    public static function getAllBy(PDO $pdo, string $field, mixed $value, ?string $orderBy = 'id', string $orderDir = 'ASC', ?int $limit = 100): array {
        try {
            $table = static::TABLE;
            $field = Sec::sqlIdent($field);
            $sql = "SELECT * FROM $table WHERE $field = :search";

            if ($orderBy !== null) {
                $orderBy = Sec::sqlIdent($orderBy);
                $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
                $sql .= " ORDER BY $orderBy $orderDir";
            }

            if ($limit !== null) {
                $sql .= " LIMIT :limit";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':search', $value);

            if ($limit !== null) {
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            if(in_array($e->getCode(), ['42S22', '42703'], true)) {
                throw new RuntimeException('Column `' . Sec::hNoHtml($orderBy) . '` in table `' . Sec::hNoHtml(static::TABLE) . '` does not exist');
            }
            error_log('Database connection error: ' . $e->getMessage());
            throw new RuntimeException('Database connection error.');
        }
    }


    /**
     * Récupère une valeur spécifique d'une table en fonction de l'ID et du champ demandé.
     *
     * @param PDO    $PDO   Instance de connexion PDO à la base de données.
     * @param mixed  $id    Identifiant de l'élément à rechercher.
     * @param string $field Nom du champ dont on souhaite récupérer la valeur. (liste blanche via Sec::sqlIdent())
     *
     * @return string|null Valeur du champ demandé ou null si aucune correspondance n'a été trouvée.
     */
    public static function getValueFrom(PDO $PDO, $id, $field): ?string
    {
        $table = static::TABLE;
        $field = Sec::sqlIdent($field);
        $stmt = $PDO->prepare("SELECT $field FROM $table WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $entity = $stmt->fetch();
        if (empty($entity)) {
            return null;
        }
        return $entity[$field];
    }

    public static function count(\PDO $pdo)
    {
        $table = static::TABLE;
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * Supprime une entrée en BDD
     *
     * @param $pdo
     * @param int $id  sécurisé
     * @return bool
     */
    public static function delete(\PDO $pdo, int $id): int
    {
        $id = (int)$id;
        $stmt = $pdo->prepare("DELETE FROM " . static::TABLE . " WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount();
    }

    /**
     * Regarde si la valeur pour le champ est unique en table, $field filtré par liste blanche via Sec::sqlIdent(), securisé sur $value
     * @param $pdo
     * @param string $field  champ à verifier, liste blanche via Sec::sqlIdent()
     * @param mixed $value  sécurisé, motif à chercher
     * @param int|null $excludeId  si fourni, exclut cette ligne (id) de la vérification — utile en édition
     * @return bool
     */
    public static function check_uniq($pdo, string $field, mixed $value, ?int $excludeId = null): bool
    {
        $table = static::TABLE;
        $field = Sec::sqlIdent($field);
        $sql = "SELECT $field FROM $table WHERE $field = :value";
        if ($excludeId !== null) {
            $sql .= " AND id != :excludeId";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':value', $value);
        if ($excludeId !== null) {
            $stmt->bindValue(':excludeId', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $entity = $stmt->fetch();
        if ($entity) {
            return false;
        } else {
            return true;
        }
    }

    /**
     *
     * Retourne les règles de validation de type OsdAurox\Validator
     *
     *  Toutes les règles sont détailées ici : https://aurox.fr/doc.php/#baseModelGetRules
     *
     * @return array
     * @throws Exception
     */
    public static function getRules(): array
    {
        // détail dans la doc https://aurox.fr/doc.php/#baseModelGetRules
        $rules = [
            'email' => ['type' => 'mail'],
            'username' => ['type' => 'string', 'minLength' => 3, 'maxLength' => 255, 'required' => true],
        ];
        throw new Exception('Not implemented');
    }

    public static function validate(): bool
    {
        throw new Exception('Not implemented');
    }

    /**
     *  Raccourcis pour extraire un JSON_ARRAYAGG ou [ ] si erreur; d'un résultat Array PDO
     *
     * @param array $array
     * @param string $key
     * @param array|null $default
     * @return array
     */
    public static function jsonArrayAggDecode(array $array, string $key, ?array $default = null): array
    {
        if(!is_array($default)) {
            $default = [];
        }

        if (!array_key_exists($key, $array)) {
            return $default;
        }

        $json = $array[$key];
        if (!$json) {
            return $default;
        }
        $decoded = json_decode($json, true);
        if ($decoded === null) {
            return $default;
        }
        return $decoded;
    }

    /**
     *
     * Alias pour récupérer des entrées SQL FETCH_ASSOC par une list d'Ids
     *
     * Attention Sqli - Injection SQL possible sur $select ($table filtré par liste blanche via Sec::sqlIdent()), doit être sécurisé et
     * ne pas venir d'une saisie utilisateur
     *
     * $ids est sécurisé par PDO + cast peut provenir d'un formulaire
     *
     * @param $pdo
     * @param string $table
     * @param array $ids
     * @return array
     */
    public static function getByIds(\PDO $pdo, string $table, array $ids, string $select='*'): array
    {
        if (empty($ids)) {
            return [];
        }

        // on met à plat le tableau
        $ids = array_values($ids);

        $placeholders = [];
        for ($i = 0; $i < count($ids); $i++) {
            $placeholders[] = ':id' . $i;
        }
        $table = Sec::sqlIdent($table);
        $sql = "SELECT $select FROM $table WHERE id IN (" . implode(',', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);

        for ($i = 0; $i < count($ids); $i++) {
            $val = $ids[$i];
            if (!filter_var($val, FILTER_VALIDATE_INT)) {
                throw new InvalidArgumentException('ID invalide, il faut un entier');
            }
            $val = (int)$val;
            $stmt->bindValue(':id' . $i, $val, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function idsExistsOrEmpty(\PDO $pdo, string $table, array $ids): bool
    {
        if (empty($ids)) {
            return True;
        }

        // on met à plat le tableau
        $ids = array_values($ids);

        $placeholders = [];
        for ($i = 0; $i < count($ids); $i++) {
            $placeholders[] = ':id' . $i;
        }
        $table = Sec::sqlIdent($table);
        $sql = "SELECT COUNT(id) as count FROM $table WHERE id IN (" . implode(',', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);

        for ($i = 0; $i < count($ids); $i++) {
            $val = $ids[$i];
            if (!filter_var($val, FILTER_VALIDATE_INT)) {
                throw new InvalidArgumentException('ID invalide, il faut un entier');
            }
            $val = (int)$val;
            $stmt->bindValue(':id' . $i, $val, PDO::PARAM_INT);
        }

        $stmt->execute();
        $r = $stmt->fetch();
        return $r['count'] > 0;
    }

    /**
     * Retourne un select HTML avec les options de la table
     *
     * @param bool $required
     * @param mixed $selected
     * @return string
     * @throws Exception
     */
    public static function getSelect(bool $required = true, mixed $selected = null): string
    {
//        public static function getSelect(bool $required = true, mixed $selected = null, string $domId = 'legalform-select',
//                                         string $name = 'legalform-id', int $countryId = 1, bool $select2 = true): string
        throw new Exception('Not implemented');
    }

    public static function canEditOrDie(\PDO $pdo, int $id): void
    {
        if (!self::canEdit($pdo, $id)) {
            throw new RuntimeException('You do not have the rights to edit this.');
        }
    }

    /**
     * Vérifie si l'utilisateur a le droit de modifier l'élément spécifié par son identifiant.
     *
     * Cette méthode détermine si l'utilisateur actuel a le droit de modifier un élément
     * en fonction de son identifiant. Les administrateurs ont toujours les droits d'édition.
     * Pour les autres utilisateurs, cette fonctionnalité n'est pas encore implémentée.
     *
     * @param \PDO $pdo La connexion à la base de données
     * @param int $id L'identifiant de l'élément à vérifier
     * @return bool Retourne true si l'utilisateur peut modifier l'élément, false sinon
     * @throws \RuntimeException Si l'utilisateur n'est pas administrateur (fonctionnalité non implémentée)
     */
    public static function canEdit(\PDO $pdo, int $id): bool
    {
        if(empty($id)) {
            return false;
        }

        if(Sec::isAdminBool()) {
            return true;
        }

        throw new RuntimeException('Not implemented');
    }


}