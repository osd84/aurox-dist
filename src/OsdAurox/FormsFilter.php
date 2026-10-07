<?php

namespace OsdAurox;

use DateTime;
use OsdAurox\I18n;

class FormsFilter
{

    public array $btns = [];
    public array $html_elem = [];
    public array $filter_active = [];
    public string $base_url = '';

    public function __construct($filter_active = [])
    {
        $this->filter_active = $filter_active;
    }

    public function buildBaseUrlFormActiveFilters($current_field)
    {
        $url = '?';
        $to_remove = [];
        $current_filter = $this->filter_active;
        foreach ($current_filter as $field => $elem) {
            foreach ($elem as $action => $value) {
                if($action === 'remove' || $field === $current_field) {
                    $to_remove[] = $field;
                }
            }
        }
        // on supprime les filtres remove
        foreach ($to_remove as $field) {
            unset($current_filter[$field]);
        }
        foreach ($current_filter as $key => $field) {
            foreach ($field as $action => $value) {
                $url .= "f_{$key}__{$action}=$value&";
            }
        }

        $this->base_url = $url;
        return $this->base_url;
    }

    public function removeALlFilterBtn()
    {
        return "<a class='btn btn-secondary' href='?'><span class='fa fa-times'></span> ".I18n::t('Remove all filters')."</a>";
    }
    public function headerBtn()
    {
        $btn_name = I18n::t('Actions');

        return <<<HTML
           <div class="card">
               <div class="card-header">
                    <h3 class="card-title">$btn_name</h3>
                </div>
                <div class="card-body">
        HTML;
    }

    public function footerBtn()
    {
        return <<<HTML
                </div>
            </div>
        HTML;
    }

    public function headerFilter()
    {
        $filter = I18n::t('Filter');

        $html_remove_filter_btn = '';
        if(!empty($this->filter_active)) {
            $html_remove_filter_btn = $this->removeALlFilterBtn();
        } else {
            $html_remove_filter_btn = '<p>'.I18n::t('No filter applied').'</p>';
        }

        return <<<HTML
           <div class="card">
               <div class="card-header">
                    <h3 class="card-title">$filter</h3>
                </div>
                <div class="card-body">
                    $html_remove_filter_btn
        HTML;
    }

    public function footerFilter()
    {
        return <<<HTML
                </div>
            </div>
        HTML;
    }

    public function listFilter($field, $list)
    {
        $field = Sec::h($field);
        $field_name = I18n::t($field);

        $html = <<<HTML
            <details data-filter-title="$field" open="">
            <summary>
                $field_name
            </summary>
            <ul style="list-style: none;">
        HTML;

        // all
        $all_name = I18n::t('All');
        $base_url = $this->buildBaseUrlFormActiveFilters($field);
        $html .= "<li class='selected'><a href='{$base_url}f_{$field}__=remove'>$all_name</a></li>";

        foreach ($list as $key => $value) {
            $value = Sec::h($value);
            $value_name = I18n::t($value);
            $html .= "<li><a href='{$base_url}f_{$field}__exact=$key'>$value_name</a></li>";
        }

        $html .= "</ul></details>";

        $this->html_elem[] = $html;
    }

    public function inputTextLikeFilter($field)
    {
        $field = Sec::h($field);
        $field_name = I18n::t($field);
        $action_name = I18n::t('Search');
        $filter = I18n::t('contains');
        $base_url = $this->buildBaseUrlFormActiveFilters($field);

        $html = <<<HTML
            <details data-filter-title="$field" open="">
            <summary>
                $field_name {$filter}
            </summary>
            <ul style="list-style: none;">
                <li class="selected">
                    <a href="{$base_url}f_{$field}__=remove">Tous</a></li>
                <li>
                <form method="get">
                    <div class="row form-group">
                        <div class="col-9 m-0 p-0">
                                <input class="form-control" type="text" name="f_{$field}__contains" placeholder="{$action_name}">
                        </div>
                        <div class="col-3 m-0 p-0">
                                <button class="btn btn-primary form-control" type="submit"><span class="fa fa-search"></span></button>
                        </div>
                    </div>
                </form>
                </li>
            </ul>
        HTML;

        $this->html_elem[] = $html;
    }

    public function inputTextStartFilter($field)
    {
        $field = Sec::h($field);
        $field_name = I18n::t($field);
        $action_name = I18n::t('Search');
        $filter = I18n::t('start');
        $base_url = $this->buildBaseUrlFormActiveFilters($field);

        $html = <<<HTML
            <details data-filter-title="$field" open="">
            <summary>
                $field_name {$filter}
            </summary>
            <ul style="list-style: none;">
                <li class="selected">
                    <a href="{$base_url}f_{$field}__=remove">Tous</a></li>
                <li>
                <form method="get">
                    <div class="row form-group">
                        <div class="col-9 m-0 p-0">
                                <input class="form-control" type="text" name="f_{$field}__start" placeholder="{$action_name}">
                        </div>
                        <div class="col-3 m-0 p-0">
                                <button class="btn btn-primary form-control" type="submit"><span class="fa fa-search"></span></button>
                        </div>
                    </div>
                </form>
                </li>
            </ul>
        HTML;

        $this->html_elem[] = $html;
    }

    public function addButton($url, $text, $type='')
    {
        $url = Sec::h($url);
        $text = I18n::t($text);

        $html = '';
        $html .= "<a href='$url' class='btn btn-primary'";
        $html .= ">";
        $html .= match ($type) {
            'add' => "<span class='fa fa-plus'></span> ",
            default => "",
        };
        $html .= "$text</a>";

        $this->btns[] = $html;
    }

    public function boolFilter($field)
    {
        $field = Sec::h($field);
        $field_name = I18n::t($field);

        $yes = I18n::t('Yes');
        $no = I18n::t('No');

        $base_url = $this->buildBaseUrlFormActiveFilters($field);


        $html = <<<HTML
            <details data-filter-title="$field" open="">
            <summary>
                $field_name
            </summary>
            <ul style="list-style: none;">
                <li class="selected">
                    <a href="{$base_url}f_{$field}__=remove">Tous</a></li>
                <li>
                    <a href="{$base_url}f_{$field}__exact=1">$yes</a></li>
                <li>
                    <a href="{$base_url}f_{$field}__exact=0">$no</a></li>
            </ul>
        </details>
        HTML;

        $this->html_elem[] = $html;
    }

    public function dateFilter($field)
    {
        $field = Sec::h($field);
        $field_name = I18n::t($field);

        $base_url = $this->buildBaseUrlFormActiveFilters($field);


        $all_date = I18n::t('All date');
        $all_date_href = "{$base_url}f_{$field}__=remove";

        $today = I18n::t('Today');
        $date = new DateTime('now');
        $start = $date->format('Y-m-d+00:00:00');
        $end = $date->format('Y-m-d+23:59:59');
        $today_href = "{$base_url}f_{$field}__gte=$start&f_{$field}__lte=$end";


        $yesterday = I18n::t('Last 7 days');
        $date = new DateTime('now');
        $date->modify('-1 day');
        $start = $date->format('Y-m-d+00:00:00');
        $end = $date->format('Y-m-d+23:59:59');
        $yesterday_href = "{$base_url}f_{$field}__gte=$start&f_{$field}__lte=$end";


        $last_30_days = I18n::t('This month');
        $date = new DateTime('now');
        $date->modify('-1 month');
        $start = $date->format('Y-m-d+00:00:00');
        $end = (new DateTime('now'))->format('Y-m-d+23:59:59');
        $last_30_days_href = "{$base_url}f_{$field}__gte=$start&f_{$field}__lte=$end";

        $last_30_days = I18n::t('This Year');
        $date = new DateTime('now');
        $date->modify('-1 year');
        $start = $date->format('Y-m-d+00:00:00');
        $end = (new DateTime('now'))->format('Y-m-d+23:59:59');
        $last_30_days_href = "{$base_url}f_{$field}__gte=$start&f_{$field}__lte=$end";

        $html = <<<HTML
            <details data-filter-title="$field" open="">
            <summary>
                $field_name
            </summary>
            <ul style="list-style: none;">
                <li class="selected">
                    <a href="$all_date_href">$all_date</a></li>
                <li>
                    <a href="$today_href">$today</a></li>
                <li>
                    <a href="$yesterday_href">$yesterday</a></li>
                <li>
                    <a href="$last_30_days_href">$last_30_days</a></li>
            </ul>
        HTML;

        $this->html_elem[] = $html;
    }

    public function render()
    {
        $html = [];

        if(!empty($this->btns)) {
            $html[] = $this->headerBtn();
            foreach ($this->btns as $btn) {
                $html[] = $btn;
            }
            $html[] = $this->footerBtn();
        }

        if(!empty($this->html_elem)) {
            $html[] = $this->headerFilter();
            foreach ($this->html_elem as $elem) {
                $html[] = $elem;
            }
            $html[] = $this->footerFilter();
        }

        return implode('', $html);
    }


}
