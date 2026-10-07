<?php

namespace OsdAurox;


class Dict
{
    public static function get($array, $key, $default=null){

        if (array_key_exists($key, $array)) {
            return $array[$key];
        }
        return $default;
    }

    /**
     * Checks if a given key in an array is relevant based on its existence and value.
     *
     * @param array $array The array to check within.
     * @param string $key The key to validate within the array.
     * @return bool Returns true if the key exists in the array and its value is not null, undefined, empty, or any of the specified invalid values; otherwise, returns false.
     */
    public static function isInArrayAndRevelant(array $array, string $key): bool
    {
        $exists = array_key_exists($key, $array);
        if(!$exists) {
            return false;
        }
        $value = $array[$key];
        if (!$value || in_array($value, ['null', 'undefined', 'None', '', '0', ' '])) {
            return false;
        }
        return true;
    }
}