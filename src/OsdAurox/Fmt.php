<?php


namespace OsdAurox;

use OsdAurox\I18n;

class Fmt
{
    // Formatters
    public static function bool($field)
    {
        return I18n::t( $field ? 'Yes' : 'No');
    }
}

