<?php

namespace OsdAurox;

class Flash
{

    public static function error($message)
    {
        self::add('danger', $message);
    }

    public static function success($message)
    {
        self::add('success', $message);
    }

    public static function info($message)
    {
        self::add('info', $message);
    }

    public static function warning($message)
    {
        self::add('warning', $message);
    }

    public static function add($type, $message)
    {
        $_SESSION['messages'][$type][] = $message;
    }

    public static function get($clear = false)
    {
        if(!isset($_SESSION['messages'])) {
            $_SESSION['messages'] = [];
        }
        $messages = $_SESSION['messages'];
        if ($clear) {
            $_SESSION['messages'] = [];
        }
        return $messages;
    }
}