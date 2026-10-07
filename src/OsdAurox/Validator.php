<?php


namespace OsdAurox;


use DateTime;
use OsdAurox\Field;

class Validator
{
    public string $field = ''; // Nom du champs
    public array $rule = []; // Règle en cours de tests
    public array $errors = []; // Erreur en train d'être levée
    public array $formValidatorErrors = [];

    public array $rules = []; // history des règles testées sur la session
    public array $fieldChecked = []; // liste des champ testés
    public array $fieldIgnored = []; // lite des champs ignorés


    public bool $valid = false;
    public bool $validationIsOk = false;

    public array $sanitizedDatas = [];

    public function __construct()
    {
        $this->rules = [];

    }

    public function clean(): array
    {
        if(empty($this->validationIsOk)) {
            throw new \Exception('validation not ok, call validate() first');
        }
        return $this->sanitizedDatas;
    }

    public function validate(array $rules, ?array $datasInput): array
    {
        $errors = [];
        $this->formValidatorErrors = [];
        $this->sanitizedDatas = [];


        $this->rules[] = $rules;
        if(empty($datasInput)) {
            $this->errors['datasInput'] = [
                'valid' => false,
                'msg' => I18n::t('no data provided for validation')
            ];

            $this->formValidatorErrors[] = [
                'field' => 'datasInput',
                'valid' => false,
                'msg' => I18n::t('no data provided for validation')
            ];
            return $this->formValidatorErrors;
        }
        $this->fieldIgnored = array_diff_key(array_keys($datasInput), array_keys($rules));


        foreach ($rules as $fieldName => $fieldArray) {

            $input = $datasInput[$fieldName] ?? null;
            $field = new Field($fieldName, $fieldArray, $input);

            $this->fieldChecked[] = $fieldName;

            if($field->nullable && $field->required === False && $field->input === null) {
                // Un null sur un champ nullable est une valeur legitime, pas une
                // absence : on l'enregistre pour que clean() permette d'ecrire un
                // NULL explicite en base. On saute la suite, il n'y a rien a
                // valider sur une valeur nulle autorisee.
                $this->sanitizedDatas[$fieldName] = null;
                continue;
            }

            // si il y a des options, la valeur doit y être
            if ($field->options && !is_array($field->options)) {
                $error = I18n::t('value must be an options array');;
                $field->errors[] = $error;
                $this->errors[$fieldName] = $field->errors;
            }

            if ($field->type === 'integer') {
                $r = $this->validateIntType($field);
                if (!$r['valid']) {
                    $field->errors[] = $r['msg'];
                    $this->errors[$fieldName] = $field->errors;
                }
            }

            if ($field->type === 'fk') {
                $r = $this->validateFk($field);
                if (!$r['valid']) {
                    $field->errors[] = $r['msg'];
                    $this->errors[$fieldName] = $field->errors;
                }
            }

            if($field->isString()) {
                $r = $this->validateStringType($field);
                if (!$r['valid']) {
                    $field->errors[] = $r['msg'];
                    $this->errors[$fieldName] = $field->errors;
                }
            }

            if($field->type === 'float') {
                $r = $this->validateFloatType($field);
                if (!$r['valid']) {
                    $field->errors[] = $r['msg'];
                    $this->errors[$fieldName] = $field->errors;
                }
            }

            if($field->type === 'price') {
                $r = $this->validatePrice($field);
                if (!$r['valid']) {
                    $field->errors[] = $r['msg'];
                    $this->errors[$fieldName] = $field->errors;
                }
            }

            if($field->type === 'date') {
                $r = $this->validateDate($field);
                if (!$r['valid']) {
                    $field->errors[] = $r['msg'];
                    $this->errors[$fieldName] = $field->errors;
                }
            }

            if($field->type === 'datetime') {
                $r = $this->validateDateTime($field);
                if (!$r['valid']) {
                    $field->errors[] = $r['msg'];
                    $this->errors[$fieldName] = $field->errors;
                }
            }

            if($field->type === 'bool') {
                $r = $this->validateBoolean($field);
                if (!$r['valid']) {
                    $field->errors[] = $r['msg'];
                    $this->errors[$fieldName] = $field->errors;
                }
            }

            // on verifie si il y a du HTML injecté
            if($field->type !== 'html') {
                $r = $this->validateNoHtml($field);
                if (!$r['valid']) {
                    $field->errors[] = $r['msg'];
                    $this->errors[$fieldName] = $field->errors;
                }
            }

            if($field->type === 'mail') {
                $r = $this->validateEmail($field);
                if (!$r['valid']) {
                    $field->errors[] = $r['msg'];
                    $this->errors[$fieldName] = $field->errors;
                }
            }

            if($field->type === 'url') {
                $r = $this->validateUrl($field);
                if (!$r['valid']) {
                    $field->errors[] = $r['msg'];
                    $this->errors[$fieldName] = $field->errors;
                }
            }

            if($field->type === 'phoneFr') {
                $r = $this->validatePhoneFr($field);
                if (!$r['valid']) {
                    $field->errors[] = $r['msg'];
                    $this->errors[$fieldName] = $field->errors;
                }
            }

            if($field->nullable === False) {
                if ($field->input === null) {
                    $field->errors[] = I18n::t('field is required');
                    $this->errors[$fieldName] = $field->errors;
                }
            }

            if($field->required === True) {
                $r = $this->validateRequired($field);
                if (!$r['valid']) {
                    $field->errors[] = $r['msg'];
                    $this->errors[$fieldName] = $field->errors;
                }
            }

            if($field->maxLength !== null ||
                    $field->minLength !== null) {
                $r = $this->ValidateLength($field);
                if (!$r['valid']) {
                    $field->errors[] = $r['msg'];
                    $this->errors[$fieldName] = $field->errors;
                }
            }

            if($field->notEmpty === True) {
                $r = $this->validateNotEmpty($field);
                if (!$r['valid']) {
                    $field->errors[] = $r['msg'];
                    $this->errors[$fieldName] = $field->errors;
                }
            }

            if($field->startWith !== null) {
                $r = $this->validateStartWith($field);
                if (!$r['valid']) {
                    $field->errors[] = $r['msg'];
                    $this->errors[$fieldName] = $field->errors;
                }
            }

            if($field->positive === True) {
                $r = $this->validatePositive($field);
                if (!$r['valid']) {
                    $field->errors[] = $r['msg'];
                    $this->errors[$fieldName] = $field->errors;
                }
            }

            if ($field->regex !== null) {
                $r = $this->validateRegex($field);
                if (!$r['valid']) {
                    $field->errors[] = $r['msg'];
                    $this->errors[$fieldName] = $field->errors;
                }
            }

            if($field->inArray !== null) {
                $r = $this->validateInArray($field);
                if (!$r['valid']) {
                    $field->errors[] = $r['msg'];
                }
            }

            if($field->min !== null) {
                $r = $this->validateMin($field);
                if (!$r['valid']) {
                    $field->errors[] = $r['msg'];
                    $this->errors[$fieldName] = $field->errors;
                }
            }

            if($field->max !== null) {
                $r = $this->validateMax($field);
                if (!$r['valid']) {
                    $field->errors[] = $r['msg'];
                    $this->errors[$fieldName] = $field->errors;
                }
            }

            if($field->alpha !== null) {
                $r = $this->validateAlpha($field);
                if (!$r['valid']) {
                    $field->errors[] = $r['msg'];
                    $this->errors[$fieldName] = $field->errors;
                }
            }

            if($field->numericString !== null) {
                $r = $this->validateNumericString($field);
                if (!$r['valid']) {
                    $field->errors[] = $r['msg'];
                    $this->errors[$fieldName] = $field->errors;
                }
            }

            // on complète un tableau de données sanitized ou null
            if(empty($field->errors)) {
                $this->sanitizedDatas[$fieldName] = $field->input;
            }

            foreach ($field->errors as $msg) {
                $this->formValidatorErrors[] = [
                    'field' => $fieldName,
                    'valid' => false,
                    'msg' => $msg
                ];
            }

        }
        $this->validationIsOk = True;
        return $this->formValidatorErrors;
    }


    public function isValid($input)
    {
        if(!$this->validationIsOk) {
            throw new \Exception('validation not ok, call validate() first');
        }
        return count($this->errors) === 0;
    }


    public function validateEmail(Field $field): array
    {
        $msg = I18n::t('must be valid email');
        $valid = true;

        if (!is_string($field->input)) {
            $valid = false;
        }

        if ($valid) {
            $valid = filter_var($field->input, FILTER_VALIDATE_EMAIL);
        }

        return ['valid' => $valid,
            'msg' => $msg ?? ''];

    }

    public function validateBoolean(Field $field): array
    {
        $msg = I18n::t('doit être une valeur booléenne');

        // Vérifie si la valeur est un booléen strict
        if (is_bool($field->input)) {
            return [
                'valid' => true,
                'msg' => ''
            ];
        }

        return [
            'valid' => false,
            'msg' => $msg
        ];
    }

    public function validateNumericString(Field $field): array
    {
        $msg = I18n::t('must contain only numeric characters as string');

        // Vérifie si la valeur est une chaîne de caractères
        if (!is_string($field->input)) {
            return [
                'valid' => false,
                'msg' => $msg
            ];
        }

        // Vérifie si la chaîne contient uniquement des chiffres (0-9)
        $valid = preg_match('/^[0-9]+$/', $field->input) === 1;

        return [
            'valid' => $valid,
            'msg' => $valid ? '' : $msg
        ];
    }


    public function validateNotEmpty(Field $field): array
    {
        $msg = I18n::t('must not be empty');

        if (is_string($field->input)) {
            $field->input = trim($field->input);
        }

        $valid = !empty($field->input);

        return ['valid' => $valid,
            'msg' => $msg ?? ''];
    }

    public function validateAlpha(Field $field): array
    {
        $msg = I18n::t('must contain only alphanumeric characters');;

        // Vérifie si la valeur est une chaîne de caractères
        if (!is_string($field->input)) {
            return [
                'valid' => false,
                'msg' => I18n::t('must contain only alphanumeric characters')
            ];
        }

        // Vérifie si la chaîne contient uniquement des caractères alphanumériques
        $valid = preg_match('/^[a-zA-Z0-9]+$/', $field->input) === 1;

        return [
            'valid' => $valid,
            'msg' => $valid ? '' : $msg
        ];
    }


    public function validateRequired(Field $field): array
    {
        $msg = I18n::t('field is required');

        // Vérifie si la valeur est null ou undefined
        if ($field->input === null) {
            return ['valid' => false, 'msg' => $msg];
        }

        // Pour les chaînes de caractères
        if (is_string($field->input)) {
            return [
                'valid' => trim($field->input) !== '',
                'msg' => $msg
            ];
        }

        // Pour les tableaux
        if (is_array($field->input)) {
            return [
                'valid' => count($field->input) > 0,
                'msg' => $msg
            ];
        }

        // Pour les nombres
        if (is_numeric($field->input)) {
            return [
                'valid' => true,
                'msg' => $msg
            ];
        }

        // Pour les booléens
        if (is_bool($field->input)) {
            return [
                'valid' => true,
                'msg' => $msg
            ];
        }

        // Pour tous les autres types
        return [
            'valid' => !empty($field->input),
            'msg' => $msg
        ];

    }

    public function validateFk(Field $field): array
    {
        $pdo = Dbo::getPdo();
        $table = $field->fkTableName;
        // ne pas ecraser $field : on garde l'objet, la colonne va dans sa propre variable
        $column = $field->fkFieldName;
        $value = $field->input;

        // une fk vide n'existe pas en base, inutile d'interroger
        if ($value === null || $value === '') {
            return [
                'valid' => false,
                'msg' => I18n::t('must be a valid fk')
            ];
        }

        // la valeur est bindee par PDO. safeForLikeStrong() n'a pas sa place ici :
        // c'est un assainisseur de motif LIKE, il vide les valeurs non textuelles
        // et les chaines de moins de 2 caracteres, donc tous les id a un chiffre.
        $table = Sec::sqlIdent($table);
        $column = Sec::sqlIdent($column);
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM $table WHERE $column = :search");
        $stmt->execute(['search' => $value]);
        $entity = $stmt->fetch();
        if (empty($entity) || !isset($entity['count']) || $entity['count'] < 1) {
            $valid = false;
            $msg = I18n::t('must be a valid fk');
        } else {
            $valid = true;
            $msg = '';
        }
        return [
            'valid' => $valid,
            'msg' => $msg
        ];
    }

    public function ValidateLength(Field $field): array
    {
        $min = $field->minLength ?? null;
        $max = $field->maxLength ?? null;

        $msg = '';
        $valid = false;
        $inputLength = null;
        $minPass = false;
        $maxPass = false;

        // recherche de la taille
        if (is_string($field->input)) {
            $inputLength = (int)mb_strlen($field->input);
        }

        if (is_array($field->input)) {
            $inputLength = count($field->input);
        }

        if (is_object($field->input)) {
            $inputLength = count(get_object_vars($field->input));
        }

        if (is_int($field->input)) {
            $inputLength = mb_strlen((string)$field->input);
        }

        // validation
        if ($inputLength !== null) {

            // verification du min
            if ($min === null) {
                $minPass = true;
            } else {
                $minPass = $inputLength >= $min;
            }

            // verification du max
            if ($max === null) {
                $maxPass = true;
            } else {
                $maxPass = $inputLength <= $max;
            }

            $valid = $minPass && $maxPass;
        }

        if (!$valid) {
            if ($min && $max) {
                $msg = I18n::t('must be between {min} and {max} characters', ['min' => $min, 'max' => $max]);
            } elseif ($min) {
                $msg = I18n::t('must be at least {min} characters', ['min' => $min]);
            } else {
                $msg = I18n::t('must be at most {max} characters', ['max' => $max]);
            }
        }
        return ['valid' => $valid,
            'msg' => $msg ?? ''];

    }

    public function validateStringType(Field $field): array
    {
        $msg = I18n::t('must be a string');

        // Vérifie si la valeur est de type string
        $valid = is_string($field->input);

        return [
            'valid' => $valid,
            'msg' => $msg
        ];
    }


    public function validateNoHtml(Field $field): array
    {
        $msg = I18n::t('must not contain html');
        // strip_tags() et pas Sec::hNoHtml() : hNoHtml echappe aussi ' & et ",
        // qui ne sont pas du HTML. La comparaison rejetait donc des valeurs
        // metier parfaitement legitimes (A L'OEIL, DUPONT & FILS). L'echappement
        // de sortie reste le travail de Sec::h() au rendu, pas celui du validateur.
        if (is_array($field->input) || is_object($field->input)) {
            return [
                'valid' => false,
                'msg' => $msg
            ];
        }
        $input = (string) $field->input;
        $valid = strip_tags($input) === $input;
        return [
            'valid' => $valid,
            'msg' => $msg
        ];
    }

    public function validateUrl(Field $field): array
    {
        $msg = I18n::t('must be a valid URL');

        // Vérifie si la valeur est une chaîne de caractères
        if (!is_string($field->input)) {
            return [
                'valid' => false,
                'msg' => I18n::t('must be a string')
            ];
        }

        // Supprime les espaces avant et après
        $field->input = trim($field->input);

        // Utilise FILTER_VALIDATE_URL pour vérifier l'URL
        $valid = filter_var($field->input, FILTER_VALIDATE_URL) !== false;

        return [
            'valid' => $valid,
            'msg' => $valid ? '' : $msg
        ];
    }

    public function validateRegex(Field $field): array
    {
        // Vérifier si une regex est définie
        $regex = $field->regex ?? null;

        if (!$regex) {
            return [
                'valid' => true,
                'msg' => '' // Pas de regex fournie, donc aucune validation
            ];
        }

        // Message d'erreur par défaut
        $msg = I18n::t('must match the required format');

        // Vérifie si l'entrée est une chaîne de caractères
        if (!is_string($field->input)) {
            return [
                'valid' => false,
                'msg' => I18n::t('must be a string')
            ];
        }

        // Valide la valeur avec l'expression régulière
        $valid = preg_match($regex, $field->input) === 1;

        return [
            'valid' => $valid,
            'msg' => $valid ? '' : $msg
        ];
    }

    public function validatePhoneFr(Field $field): array
    {
        $msg = I18n::t('must be a valid French phone number');

        // Vérifie si l'entrée est une chaîne de caractères
        if (!is_string($field->input)) {
            return [
                'valid' => false,
                'msg' => I18n::t('must be a string')
            ];
        }

        // Supprime les espaces et les caractères non pertinents
        $field->input = preg_replace('/\s+/', '', $field->input);

        // Expression régulière pour les numéros français
        $regex = '/^(?:\+33|0033|0)[1-9]\d{8}$/';

        // Valide contre l'expression régulière
        $valid = preg_match($regex, $field->input) === 1;

        return [
            'valid' => $valid,
            'msg' => $valid ? '' : $msg
        ];
    }



    public function validatePrice(Field $field): array
    {
        // Définir les paramètres de précision et d'échelle (adaptés aux prix)
        $precision = 10; // Total de chiffres (par défaut : 10)
        $scale =  2;         // Nombre de chiffres après la virgule (par défaut : 2)

        // Messages d'erreur par défaut
        $msg = I18n::t('must be a valid price with max precision {precision} and scale {scale}', ['precision' => $precision, 'scale' => $scale]);

        // Vérifier si la valeur est numérique
        if (!is_numeric($field->input)) {
            return [
                'valid' => false,
                'msg' => I18n::t('must be a valid number or decimal')
            ];
        }

        // Vérifier si la valeur est positive (ou au minimum >= 0)
        if ($field->input < 0) {
            return [
                'valid' => false,
                'msg' => I18n::t('must be a positive number')
            ];
        }

        // Séparer la partie entière et la partie décimale
        $parts = explode('.', (string)$field->input);
        $integerPart = $parts[0];
        $decimalPart = $parts[1] ?? '';

        // Vérifier la longueur de la partie entière et decimale combinée (précision totale)
        if (strlen($integerPart) + strlen($decimalPart) > $precision) {
            return [
                'valid' => false,
                'msg' => I18n::t('must not exceed a total of {precision} digits including decimals', ['precision' => $precision])
            ];
        }

        // Vérifier la longueur de la partie décimale (échelle)
        if (strlen($decimalPart) > $scale) {
            return [
                'valid' => false,
                'msg' => I18n::t('must have a maximum of {scale} digits after the decimal point', ['scale' => $scale])
            ];
        }

        // Vérifier si une valeur minimale est définie
        if (isset($field->min) && $field->input < $field->min) {
            return [
                'valid' => false,
                'msg' => I18n::t('must be greater than or equal to {min}', ['min' => $field->min])
            ];
        }

        // Vérifier si une valeur maximale est définie
        if (isset($field->max) && $field->input > $field->max) {
            return [
                'valid' => false,
                'msg' => I18n::t('must be less than or equal to {max}', ['max' => $field->max])
            ];
        }

        // Si tout est correct
        return ['valid' => true, 'msg' => ''];
    }


    public function validateIntType(Field $field): array
    {
        $msg = I18n::t('must be a int');

        // Le validateur valide, il ne convertit pas : une chaine sur un champ
        // entier est une erreur de type. Le cast d'un formulaire ('0' => 0,
        // '' => null) est le travail de la couche de lecture du POST, voir
        // OsdAurox\BrutalCrud::readPost().
        $valid = is_int($field->input);

        return [
            'valid' => $valid,
            'msg' => $msg
        ];

    }

    public function validateFloatType(Field $field): array
    {
        $msg = I18n::t('must be a float');

        // Vérifie si la valeur est de type string
        $valid = is_float($field->input);

        return [
            'valid' => $valid,
            'msg' => $msg
        ];

    }

    public function validateMin(Field $field): array
    {
        $minimum = $field->min ?? null;
        // === et pas !$minimum : min => 0 est une regle courante et parfaitement
        // valide ("entier positif ou nul"), un test de verite la ferait sauter
        if($minimum === null){
            return [
                'valid' => true,
                'msg' => 'no min set'
            ];
        }
        $msg = I18n::t('must be greater than or equal to {min}', ['min' => $minimum]);

        // Convertir les chaînes numériques en nombres
        if (is_string($field->input) && is_numeric($field->input)) {
            if (str_contains($field->input, '.')) {
                $field->input = (float)$field->input;
            } else {
                $field->input = (int)$field->input;
            }
        }

        // Vérifier si c'est un nombre
        if (!is_numeric($field->input)) {
            return [
                'valid' => false,
                'msg' => I18n::t('must be a number')
            ];
        }

        return [
            'valid' => $field->input >= $minimum,
            'msg' => $msg
        ];

    }


    public function validateMax(Field $field): array
    {
        $maximum = $field->max ?? null;
        // idem validateMin() : max => 0 est valide
        if($maximum === null){
            return [
                'valid' => true,
                'msg' => 'no max set'
            ];
        }

        $msg = I18n::t('must be less than or equal to {max}', ['max' => $maximum]);

        // Convertir les chaînes numériques en nombres
        if (is_string($field->input) && is_numeric($field->input)) {
            if (str_contains($field->input, '.')) {
                $field->input = (float)$field->input;
            } else {
                $field->input = (int)$field->input;
            }
        }

        // Vérifier si c'est un nombre
        if (!is_numeric($field->input)) {
            return [
                'valid' => false,
                'msg' => I18n::t('must be a number')
            ];
        }

        return [
            'valid' => $field->input <= $maximum,
            'msg' => $msg
        ];

    }


    public function validateStartWith(Field $field): array
    {
        $prefix = $field->startWith;
        if(!$prefix){
           throw new \Exception('startWith is empty on Field');
        }
        $msg = I18n::t('must start with "{prefix}"', ['prefix' => $prefix]);

        $caseSensitive = $field->startWithCaseSensitive ?? false;

        if (!is_string($field->input)) {
            return [
                'valid' => false,
                'msg' => I18n::t('must be a string')
            ];
        }

        if ($caseSensitive) {
            $valid = str_starts_with($field->input, $prefix);
        } else {
            $valid = str_starts_with(strtolower($field->input), strtolower($prefix));
        }

        return [
            'valid' => $valid,
            'msg' => $msg
        ];

    }

    public function validatePositive(Field $field): array
    {
        $msg = '';

        // Convertir les chaînes numériques en nombres
        if (is_string($field->input) && is_numeric($field->input)) {
            if (str_contains($field->input, '.')) {
                $field->input = (float)$field->input;
            } else {
                $field->input = (int)$field->input;
            }
        }

        // Vérifier si c'est un nombre
        if (!is_numeric($field->input)) {
            return [
                'valid' => false,
                'msg' => I18n::t('must be a positive number')
            ];
        }

        return [
            'valid' => $field->input > 0,
            'msg' => I18n::t('must be a positive number')
        ];

    }

    public function validateDate(Field $field): array
    {
        $format = $field->dateFormat;
        $msg = '';

        if (!is_string($field->input)) {
            return [
                'valid' => false,
                'msg' => I18n::t('must be a string date')
            ];
        }

        // Essayer de créer un objet DateTime
        $dateTime = DateTime::createFromFormat($format, $field->input);

        // Vérifier si la date est valide et si les erreurs de parsing sont présentes
        if (!$dateTime) {
            $valid = false;
        } else {
            $valid = true;
        }
        // Vérifier si la date correspond exactement au format attendu
        if ($valid) {
            $valid = $dateTime->format($format) === $field->input;
        }

        return [
            'valid' => $valid,
            'msg' => I18n::t('must be a valid date in format: {format}', ['format' => $format])
        ];

    }

    public function validateDateTime(Field $field): array
    {
        $format = $field->dateTimeFormat;
        $msg = '';

        if (!is_string($field->input)) {
            return [
                'valid' => false,
                'msg' => $msg
            ];
        }

        // Essayer de créer un objet DateTime
        $dateTime = DateTime::createFromFormat($format, $field->input);

        // Vérifier si la date est valide et si les erreurs de parsing sont présentes
        if (!$dateTime) {
            $valid = false;
        } else {
            $valid = true;
        }

        // Vérifier si la date correspond exactement au format attendu
        if ($valid) {
            $valid = $dateTime->format($format) === $field->input;
        }

        return [
            'valid' => $valid,
            'msg' => I18n::t('must be a valid datetime in format: {format}', ['format' => $format])
        ];

    }


    /**
     * Vérifie si la valeur est présente dans un tableau donné
     *
     * @param array $allowedValues Tableau des valeurs autorisées
     * @param bool $strict Utiliser une comparaison stricte (===)
     * @return Validator
     */
    public function validateInArray(Field $field): array
    {
        $valid = in_array($field->input, $field->inArray);

        // Crée un message lisible avec les valeurs autorisées
        $valuesString = implode(', ', array_map(function ($value) {
            if (is_null($value)) return 'null';
            if (is_bool($value)) return $value ? 'true' : 'false';
            return (string)$value;
        }, $field->inArray));

        return [
            'valid' => $valid,
            'msg' => I18n::t("must be one of the following values : $valuesString")
        ];

    }


}