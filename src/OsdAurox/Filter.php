<?php

namespace OsdAurox;



class Filter
{

    /**
     * Affiche une valeur pour du HTML, avec fallback visuel si vide.
     *
     * Retourne un tiret cadratin grisé (`—`) si $value est null ou une chaîne
     * vide (après trim), sinon la valeur échappée via Sec::hNoHtml().
     *
     * @param int|float|string|null $value Valeur brute à afficher
     * @return string HTML prêt à l'affichage, déjà échappé
     */
    public static function display(int|float|string|null $value): string {
        if ($value === null || trim((string) $value) === '') {
            return '<span class="text-muted">—</span>';
        }
        return Sec::hNoHtml((string) $value);
    }

    public static function truncate($text, $length = 100, $ending = '...')
    {
        $text = (string) $text;

        if (mb_strlen($text, 'UTF-8') <= $length) {
            return $text;
        }

        $keep = $length - mb_strlen($ending, 'UTF-8');
        if ($keep < 1) {
            return mb_substr($text, 0, $length, 'UTF-8');
        }

        return rtrim(mb_substr($text, 0, $keep, 'UTF-8')) . $ending;
    }

    public static function dateFr($date)
    {
        if ($date === null || $date === '') return '';
        return Sec::h(date('d/m/Y', strtotime($date)));
    }

    public static function dateMonthFr($date)
    {
        if ($date === null || $date === '') return '';
        $months = [
            '01' => 'Janvier',
            '02' => 'Février',
            '03' => 'Mars',
            '04' => 'Avril',
            '05' => 'Mai',
            '06' => 'Juin',
            '07' => 'Juillet',
            '08' => 'Août',
            '09' => 'Septembre',
            '10' => 'Octobre',
            '11' => 'Novembre',
            '12' => 'Décembre'
        ];
        $month = date('m', strtotime($date));
        $year = date('Y', strtotime($date));
        return Sec::h($months[$month] . ' ' . $year);
    }

    public static function dateUs($date)
    {
        if ($date === null || $date === '') return '';
        return Sec::h(date('Y-m-d', strtotime($date)));
    }

    public static function toDayDateUs()
    {
        return Sec::h(date('Y-m-d'));
    }
}