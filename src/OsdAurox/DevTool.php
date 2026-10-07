<?php

namespace OsdAurox;

class DevTool
{
    /**
     * Extrait les noms de colonnes d'une requête INSERT SQL.
     *
     * Récupère la partie entre parenthèses précédant le mot-clé VALUES
     * (ex: "INSERT INTO table (col1, col2) VALUES (...)") et retourne
     * chaque nom de colonne nettoyé des espaces.
     *
     * Ne fonctionne que pour les requêtes INSERT avec VALUES en majuscules.
     * Ne gère pas les valeurs contenant des virgules entre guillemets.
     *
     * @param string $sqlString Requête SQL de type INSERT
     * @return array Liste des noms de colonnes, dans l'ordre
     */
    public static function extractSqlParameterArray(string $sqlString): array
    {
        $columnsPart = explode('VALUES', $sqlString, 2)[0];
        $columnsPart = trim(strstr($columnsPart, '(') ?: '', "() \t\n\r");
        $columns = explode(',', $columnsPart);
        return array_map('trim', $columns);
    }

    /**
     * Retourne le nom de la colonne correspondant à un numéro de paramètre donné.
     *
     * @param string $sqlString Requête SQL de type INSERT
     * @param int $paramNumber Position du paramètre, 1-indexée
     * @return string Nom de la colonne
     */
    public static function foundSqlParameterName(string $sqlString, int $paramNumber): string
    {
        return self::extractSqlParameterArray($sqlString)[$paramNumber - 1];
    }
}