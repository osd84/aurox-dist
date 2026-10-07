<?php

namespace OsdAurox;

use OsdAurox\AppConfig;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    public ?PHPMailer $mail = null;

    public function __construct() {
        $mail = new PHPMailer(DEBUG);
        $mail->isSMTP();
        $mail->Host = AppConfig::get('mailHost');
        $mail->SMTPAuth = true;
        $mail->Username = AppConfig::get('mailUser');
        $mail->Password = AppConfig::get('mailPass');
        $mail->SMTPSecure = AppConfig::get('mailSsl') ? 'ssl' : 'tls';
        $mail->Port = AppConfig::get('mailPort');
        $mail->setFrom(AppConfig::get('mailFrom'), AppConfig::get('appName'));
        $mail->isHTML();
        $mail->CharSet = 'UTF-8';

        $this->mail = $mail;
    }
    public static function send($to, $subject, $content, $cc = false) {
        $mail = new Mailer();
        $mail->mail->addAddress($to);
        if ($cc) {
            $mail->mail->addCC($cc);
        }
        $mail->mail->Subject = $subject;
        $mail->mail->Body = $content;

        return $mail->mail->send();
    }


}