<?php

namespace  OsdAurox;

class I18n
{
    private array $translations = [];
    private string $locale;

    public function __construct($locale = 'en')
    {
        $this->locale = $locale;
        if (!in_array($this->locale, AppConfig::get('lang', safe: true) ?? ['en'])) {
            $this->locale = 'en';
        }
        $this->loadTranslations();
    }

    private function loadTranslations(): bool
    {
        $this->translations = [];
        $filePath = APP_ROOT . '/translations/' . $this->locale . '.php';
        if (file_exists($filePath)) {
            $this->translations = include $filePath;

            // on injecte les traductions de Aurox Core si existent
            $locale = $this->locale;
            if (isset(Translations::$$locale)) {
                $this->translations = array_merge(Translations::$$locale, $this->translations);
            }

            return true;
        }
        return false;

    }

    public function translate(string $key, array $placeholders = [], bool $safe = false)
    {
        $translation = $this->translations[$key] ?? $key;
        foreach ($placeholders as $placeholder => $value) {
            $translation = str_replace('{' . $placeholder . '}', $value, $translation);
        }
        if (!$safe) {
            $translation = htmlspecialchars($translation, ENT_QUOTES, 'UTF-8');
        }
        return $translation;
    }

    public function setLocale($locale): void
    {
        $this->locale = $locale;
        $this->loadTranslations();
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public static function t(string $key, array $placeholders = [], bool $safe = false): string|null
    {
        $translator = $GLOBALS['i18n'];
        if (!$translator) {
            throw new \LogicException('Out context; I18n not initialized');
        }
        return $translator->translate(key : $key, placeholders: $placeholders, safe : $safe);
    }

    /**
     * Génère un nom de champ localisé et sécurisé en ajoutant la locale courante.
     *
     * @param string $fieldName Le nom de base du champ. Par défaut 'name' si non fourni.
     * @return string Le nom du champ sécurisé avec la locale courante ajoutée.
     * @throws \LogicException Si le traducteur n'est pas initialisé dans la portée globale.
     */

    public static function getLocalizedFieldName(string $fieldName = 'name'): string
    {
        if (!$fieldName) {
            return '';
        }

        $translator = $GLOBALS['i18n'];
        if (!$translator) {
            throw new \LogicException('Out context; I18n not initialized');
        }

        return Sec::hNoHtml($fieldName . '_' . $translator->getLocale());
    }

    public static function entity(array $entity, ?string $default = null, string $fieldName = 'name', bool $safe = false): string
    {
        if (!$entity) {
            return '';
        }

        $translator = $GLOBALS['i18n'];
        if (!$translator) {
            throw new \LogicException('Out context; I18n not initialized');
        }

        $localizedKey = $fieldName . '_' . $translator->getLocale();

        // si la clef existe
        if (array_key_exists($localizedKey, $entity) && !empty($entity[$localizedKey])) {
            $out = $entity[$localizedKey];
        } elseif ($default !== null) {
            $out = $default;
        } elseif (array_key_exists($fieldName, $entity)) {
            $out = $entity[$fieldName];
        } else {
            $out = '';
        }

        if (!$safe) {
            return htmlspecialchars($out, ENT_QUOTES, 'UTF-8');
        }

        return $out;
    }

    public static function currentLocale(): ?string
    {
        $translator = $GLOBALS['i18n'];
        if (!$translator) {
            throw new \LogicException('Out context; I18n not initialized');
        }

        $out = $translator->getLocale();
        if (!in_array($out, AppConfig::get('lang', safe: true) ?? ['en'])) {
            return 'en';
        }
        return $out;
    }

    /**
     * Formate une date selon le format spécifié, avec adaptation au format français si la locale actuelle est française.
     * Sécurisé contre les XSS via Sec::hNoHtml
     *
     * @param string $date La date à formater (compatible avec strtotime)
     * @param string $format Le format de date souhaité (par défaut 'd/m/Y')
     * @return string La date formatée et sécurisée contre les injections HTML
     * @throws \LogicException Si le contexte I18n n'est pas initialisé
     */
    public static function date(string $date): string
    {
        // check if date
        if (strtotime($date) === false) {
            return '';
        }

        $locale = self::currentLocale();
        $format = 'Y-m-d';

        if($locale) {
            // on récupère le format dans le fichier de traduction
            // on peut écraser se format en modifiant /translations/locale.php
            $format = self::t('__date');
            if($format === '__date') {
                $format = 'Y-m-d'; // us par défaut
                error_log('__date not found in translations : /translations/' . Sec::hNoHtml($locale) . '.php add an entry __date => "Y-m-d"' );
            }
        }

        return Sec::hNoHtml(date($format, strtotime($date)));
    }

    /**
     * Formate une date-heure selon le format spécifié, avec adaptation au format français si la locale actuelle est française.
     * Sécurisé contre les XSS via Sec::hNoHtml
     *
     * @param string $date La date à formater (compatible avec strtotime)
     * @param string $format Le format de date-heure souhaité (par défaut 'd/m/Y H:i:s')
     * @return string La date-heure formatée et sécurisée contre les injections HTML
     * @throws \LogicException Si le contexte I18n n'est pas initialisé
     */
    public static function dateTime(string $date, bool $showSec = false): string
    {
        // check if date
        if (strtotime($date) === false) {
            return '';
        }

        $locale = self::currentLocale();
        $format = 'd/m/Y H:i:s';

        if ($locale) {
            $format = self::t('__dateTime');
            if($format === '__dateTime') {
                $format = 'd/m/Y H:i:s'; // us par défaut
                error_log('__dateTime not found in translations : /translations/' . Sec::hNoHtml($locale) . '.php add an entry __date => "d/m/Y H:i:s"' );
            }
        }

        if(!$showSec) {
            $format = str_replace(':s', '', $format);
        }

        return Sec::hNoHtml(date($format, strtotime($date)));
    }

    public static function frMonth(string $month): string
    {
        $trad = [
            'January'   => 'Janvier',
            'February'  => 'Février',
            'March'     => 'Mars',
            'April'     => 'Avril',
            'May'       => 'Mai',
            'June'      => 'Juin',
            'July'      => 'Juillet',
            'August'    => 'Août',
            'September' => 'Septembre',
            'October'   => 'Octobre',
            'November'  => 'Novembre',
            'December'  => 'Décembre',
        ];
        return $trad[$month] ?? $month;
    }

    /**
     * @param $date
     * @return string
     *
     * Retourne ex: "20 Février 2025"
     */
    public static function frDateDMY($date): string
    {
        $dt = new \DateTime($date);
        return $dt->format('d') . ' ' . self::frMonth($dt->format('F')) . ' ' . $dt->format('Y');
    }

}
