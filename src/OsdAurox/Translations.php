<?php

namespace OsdAurox;

Class Translations {

    public static array $fr = [
        // OsdAurox\Validator
        '__testOverwrite' => 'get From Core',
        '__testOverwrite2' => 'get From Core',
        '__date' => 'd/m/Y',
        '__dateTime' => 'd/m/Y H:i:s',

        'must be valid email' => 'doit être une email valide',
        'must not be empty' => 'doit être rempli',
        'must be at least {min} characters' => 'doit contenir minimum {min} caractères',
        'must be at most {max} characters' => 'doit contenir maximum {max} caractères',
        'must be between {min} and {max} characters' => 'doit contenir entre {min} et {max} caractères',
        'field is required' => 'champ obligatoire',
        'must be a float' => 'doit être un nombre',
        'must be a int' => 'doit être un nombre',
        'must be a number' => 'doit être un nombre',
        'must be a string' => 'doit être une chaine de caractères',
        'no data provided for validation' => 'aucune donnée fournie pour la validation',
        'must contain only alphanumeric characters' => 'ne doit contenir que des caractères alphanumériques',
        'must contain only numeric characters as string' => 'ne doit contenir que des caractères numériques',
        'must be greater than or equal to {min}' => 'doit être supérieur ou égal à {min}',
        'must be less than or equal to {max}' => 'doit être inférieur ou égal à {max}',
        'must be a valid fk' => 'référence invalide',
        // OsdAurox\BrutalCrud
        'already exists' => 'existe déjà',
        // OsdAurox\Modal
        'Close' => 'Fermer',
        'Submit' => 'Envoyer',
        'Cancel' => 'Annuler',
        'Accept' => 'Accepter',
        'Save' => 'Enregistrer',
        'Loading...' => 'Chargement...',
        'Please wait while the content is loading..' => 'Veuillez patienter pendant le chargement du contenu..',
        'Please complete the form below' => 'Veuillez compléter le formulaire ci-dessous',
        'Enter the required information :' => 'Veuillez entrer les informations requises :',
        'Enter the' => 'Entrez le',
        'character' => 'caractère',
    ];

    public static array $en = [
        // OsdAurox\Validator
        '__testOverwrite' => 'get From Core',
        '__testOverwrite2' => 'get From Core',
        '__date' => 'Y-m-d',
        '__dateTime' => 'Y-m-d H:i:s',
        'Entrez le' => 'Enter the',
        'character' => 'character',
    ];

}