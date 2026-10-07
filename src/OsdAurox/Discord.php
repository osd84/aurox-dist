<?php

namespace OsdAurox;

use OsdAurox\AppConfig;

class Discord
{

    public static function send($message)
    {
        $webhook = AppConfig::get('discordWebhook');
        if (!$webhook) {
            Log::error('Discord webhook not set, cannot send message');
            return false;
        }
        $data = [
            'content' => $message
        ];
        $ch = curl_init($webhook);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            return false;
        }

        return true;

    }

}