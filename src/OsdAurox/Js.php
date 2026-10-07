<?php

namespace OsdAurox;


use OsdAurox\MobileDetect;

class Js
{

    /**
     * Affiche un message dans la console JavaScript du navigateur en s'assurant de sa sécurisation.
     *
     * @param string $msg Message à afficher dans la console.
     *
     * @return void
     */
    public static function consoleLog(mixed $msg, bool $safe = False): void
    {
        if (!$safe) {
            $msg = Sec::hNoHtml($msg);
        }

        $jsonMessage = json_encode($msg);


        if ($jsonMessage === False) {
            $jsonMessage = json_encode(['error' => 'Message non encodable']);
        }

        echo '<script>console.log(' . $jsonMessage . ');</script>';
    }

}