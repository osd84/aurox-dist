<?php

namespace OsdAurox;

use Exception;

class Csrf
{
    public static function generate($length = 32)
    {
        // Vérifie que les longueurs sont valides
        if ($length < 1) {
            throw new Exception('La longueur doit être supérieure à 0');
        }
        // Utilise bin2hex et random_bytes pour une génération sécurisée
        return bin2hex(random_bytes($length));
    }

    public static function verify($key)
    {
        // IF GET
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return true;
        }
        // IF POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Vérifie que la clé CSRF existe
            if (!isset($key)) {
                return false;
            }
            if(!isset($_POST['_csrf'])) {
                return false;
            }
            // Vérifie que la clé CSRF est égale à la clé fournie
            if (hash_equals($key , Sec::h($_POST['_csrf']))) {
                return true;
            }
            return false;
        }
        return false;

    }

    public static function protect(bool $forcePerRequest=false)
    {
        if (!isset($_SESSION['csrf'])) {
            $_SESSION['csrf'] = Csrf::generate();
        }
        $key = Sec::h($_SESSION['csrf']) ?? Csrf::generate();
        if (!Csrf::verify($key)) {
            Base::dieOrThrow('CSRF token error');
            exit;
        }
        if( $forcePerRequest) {
            return $_SESSION['csrf'] = Csrf::generate();
        }
        return $_SESSION['csrf'];
    }

    public static function inputHtml()
    {
        return '<input type="hidden" name="_csrf" value="' . Sec::hNoHtml($_SESSION["csrf"]) . '">';
    }

    public static function getToken()
    {
        return Sec::hNoHtml($_SESSION["csrf"]);
    }


}