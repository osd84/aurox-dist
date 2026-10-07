<?php

namespace OsdAurox;

class Field
{
    public mixed $input = ''; // Valeur saisie
    public string $field = ''; // Nom du champs
    public string $type = ''; // Type du champ (varchar, int, etc.)
    public array $errors = []; // Erreur en train d'être levée


    public bool $nullable = false; // La valeur a le droit d'etre null
    public bool $optional = false; // @deprecated alias de $nullable, garde pour la retro-compat
    public bool $required = false;
    public bool $valid = false;
    public array $options = []; // Valeurs possibles (ex : dropdown)
    public string $label = ''; // Label affiché
    public mixed $default = null; // Valeur par défaut
    public bool $notEditable = false; // Si le champ ne peut pas être édité
    public string $comment = ''; // Commentaire interne ou annexe. Ne sera pas affiché directement.


    // ===== Validateurs =====
    public ?int $maxLength = null; // Longueur maximale autorisée (varchar ou texte)
    public ?int $minLength = null; // Longueur minimale autorisée
    public ?float $min = null; // Valeur minimale (pour les nombres)
    public ?float $max = null; // Valeur maximale (pour les nombres)
    public bool $notEmpty = false; // Si le champ ne doit pas être vide
    public ?string $startWith = null; // String d'avoir quoi doit commencer le champ
    public bool $positive = false; // Si le champ doit être un entier positif
    public ?array $inArray = null; // Tableau des valeurs possibles
    public ?string $regex = null; // Expression régulière pour valider la valeur
    public ?bool $alpha = null; // Si le champ ne doit contenir sur des caractères alpha numérique
    public ?bool $numericString = null; // Si le champ ne doit contenir que des caractères numériques mais est une string ex : ZipCode

    // ===== Attributs de type =====
    public string $dateFormat = 'Y-m-d';
    public string $dateTimeFormat = 'Y-m-d H:i:s';
    public ?string $fkTableName = null;
    public ?string $fkFieldName = null;
    public ?int $doublePrecision = 24;
    public ?int $doubleScale = 8;
    public ?bool $startWithCaseSensitive = null ;



    // ===== Informations pour l'HTML/DOM =====
    public string $class = ''; // Classe CSS utilisée pour création et mise à jour
    public string $classView = ''; // Classe CSS utilisée pour le mode vue uniquement
    public string $classList = ''; // Classe CSS utilisée dans les listes d'affichage (ex : colonnes de tableau)
    public string $help = ''; // Texte d'aide ou descriptif
    public bool $disabled = false; // Si le champ doit être désactivé
    public bool $autoFocusOnCreate = false; // Si ce champ doit être autofocus lors de sa création



    public const TYPES_LIST = [
        'integer',                        // Champ de type entier
        'int',                            // alias pour integer
        'fk',     // Clé étrangère vers une classe et son chemin
        'varchar', // Champ varchar avec une longueur maximale précisée (à remplir dynamiquement)
        'bool',
        'boolean', // alias de bool
        'text' ,                           // Texte long
        'html',                           // Texte HTML autorisé
        'float',                          // Champ de type flottant
        'price',                          // Champ de type prix (flottant ou format monétaire)
        'date', // Date avec format standard complet
        'datetime',   // Date uniquement
        'mail',                           // Champ email (validation incluse)
        'phoneFr' ,                          // Champ numéro de téléphone
        'url'                            // Champ URL
    ];


    public const CONFUSE_LIST = [ // Champ souvent confondus ou mauvaise clef, lève une erreur si utilisés
        'length'
    ];

    public function isString()
    {
        return in_array($this->type, ['varchar', 'text', 'html', 'mail', 'phone', 'url']);
    }


    public function __construct(string $fieldName, array $field, mixed $input)
    {
        $this->input = $input;
        $this->field = $fieldName;
        if(!$fieldName) {
            throw new \Exception('Le nom du champ est obligatoire');
        }
        $this->errors = [];
        $this->valid = false;

        // on gère les alias
        if($field['type'] == 'int') {
            $field['type'] = 'integer';
        }
        if($field['type'] == 'boolean') {
            $field['type'] = 'bool';
        }

        foreach (self::CONFUSE_LIST as $confuse) {
            if(array_key_exists($confuse, $field)) {
                throw new \Exception('Le nom du champ est invalide : ' . Sec::hNoHtml($confuse) . '');
            }
        }


        // 'nullable' est le nom actuel, 'optional' reste accepte pour ne pas
        // casser les regles existantes. Les deux proprietes restent synchronisees.
        $this->nullable = $field['nullable'] ?? $field['optional'] ?? false;
        $this->optional = $this->nullable;
        $this->required = $field['required'] ?? false;

        // determination du type de champ
        $type = $field['type'] ?? null;
        if(!$type || !in_array($type, self::TYPES_LIST)) {
            throw new \Exception('Invalid rule type : ' . Sec::hNoHtml($type) . '');
        }
        $this->type = $type;
        if($type == 'fk') {
            $this->fkTableName = $field['fkTableName'] ?? null;
            $this->fkFieldName = $field['fkFieldName'] ?? null;
            if(!$this->fkTableName) {
                throw new \Exception('With fk type, you must specify fkTableName');
            }
            if(!$this->fkFieldName) {
                throw new \Exception('With fk type, you must specify fkFieldName');
            }
        }

        $this->options = $field['options'] ?? [];
        $this->label = $field['label'] ?? '';
        $this->default = $field['default'] ?? null;
        $this->notEditable = $field['notEditable'] ?? false;
        $this->comment = $field['comment'] ?? '';

        // ===== Validateurs =====
        $this->maxLength = $field['maxLength'] ?? null;
        $this->minLength = $field['minLength'] ?? null;

        // on autorise un petit alias à maxLength; minLength, len =>  [ <min>, <max> ]
        $lenAlias = $field['len'] ?? null;
        if(is_array($lenAlias)) {
            $this->minLength = $lenAlias[0] ?? null;
            $this->maxLength = $lenAlias[1] ?? null;
        }

        $this->min = $field['min'] ?? null;
        $this->max = $field['max'] ?? null;
        $this->notEmpty = $field['notEmpty'] ?? false;
        $this->startWith = $field['startWith'] ?? null;
        $this->positive = $field['positive'] ?? false;
        $this->inArray = $field['inArray'] ?? null;
        $this->regex = $field['regex'] ?? null;
        $this->alpha = $field['alpha'] ?? null;
        $this->numericString = $field['numericString'] ?? null;

        // ===== Attributs de type =====
        $this->dateFormat = $field['dateFormat'] ?? 'Y-m-d';
        $this->dateTimeFormat = $field['dateTimeFormat'] ?? 'Y-m-d H:i:s';
        $this->doublePrecision = $field['doublePrecision'] ?? 24;
        $this->doubleScale = $field['doubleScale'] ?? 8;
        $this->startWithCaseSensitive = $field['startWithCaseSensitive'] ?? false;



        // ===== Informations pour l'HTML/DOM =====
        $this->class = $field['class'] ?? '';
        $this->classView = $field['classView'] ?? '';
        $this->classList = $field['classList'] ?? '';
        $this->help = $field['help'] ?? '';
        $this->disabled = $field['disabled'] ?? false;
        $this->autoFocusOnCreate = $field['autoFocusOnCreate'] ?? false;

        // Si il y a un valeur par défault on l'affecte
        if(empty($input) && $this->default) {
            $this->input = $this->default;
        }

        // required l'emporte sur nullable
        if($this->required && $this->nullable) {
           $this->nullable = false;
           $this->optional = false;
        }

    }


}