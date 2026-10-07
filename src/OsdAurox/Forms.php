<?php

namespace OsdAurox;

use OsdAurox\I18n;

class Forms
{
    public string $unique_id;
    public ?FormValidator $validator;
    public mixed $entity;
    public bool $ajax = false;
    public string $action = '';

    public function __construct($action, ?FormValidator $validator = null, mixed $entity = null, ?bool $ajax = false, ?string $unique_id = null)
    {
        $this->action = $action;
        $this->unique_id = Sec::h($unique_id ?? uniqid('Form'));
        $this->validator = $validator;
        $this->entity = $entity;
        $this->ajax = $ajax;
    }

    /**
     * Lit une valeur de l'entity sans warning "Undefined array key".
     * Retourne null si pas d'entity ou clef absente.
     */
    private function entityValue(string $name): mixed
    {
        if (!$this->entity) {
            return null;
        }
        if (is_array($this->entity)) {
            return $this->entity[$name] ?? null;
        }
        if ($this->entity instanceof \ArrayAccess) {
            return $this->entity->offsetExists($name) ? $this->entity[$name] : null;
        }
        return $this->entity->$name ?? null;
    }

    private function entityHas(string $name): bool
    {
        if (!$this->entity) {
            return false;
        }
        if (is_array($this->entity)) {
            return array_key_exists($name, $this->entity);
        }
        if ($this->entity instanceof \ArrayAccess) {
            return $this->entity->offsetExists($name);
        }
        return property_exists($this->entity, $name);
    }

    // Formatters
    public static function action(array $elem, $edit = true, $detail = true, $delete = true, $delete_confirm = true): string
    {
        $id = Sec::hNoHtml($elem['id'] ?? '');
        $html = '';
        if ($edit) {
            $html .= '<a href="?action=edit&id=' . $id . '" class="btn btn-primary mr-1"><span class="fa fa-pencil"></span></a>';
        }
        if ($detail) {
            $html .= '<a href="?action=detail&id=' . $id . '" class="btn btn-info mr-1"><span class="fa fa-eye"></span></a>';
        }
        if ($delete) {
            $html .= '<form action="?action=delete" method="post" style="display:inline">';
            $html .= '<input type="hidden" name="id" value="' . $id . '">';
            $html .= Csrf::inputHtml();
            $html .= '<button type="submit"';
            if ($delete_confirm) {
                // I18n echappe deja pour le HTML
                $html .= ' onclick="return confirm(\'' . I18n::t('Are you sure you want to delete this item?') . '\')"';
            }
            $html .= ' class="btn btn-danger">';
            $html .= '<span class="fa fa-trash"></span>';
            $html .= '</button>';
            $html .= '</form>';
        }
        return $html;
    }

    /**
     * @param array $l_object Liste du select ['id' =>, 'name' => ]
     * @param string $name Nom du champ
     * @param string|null $id
     * @param string $value_field
     * @param string $name_field
     * @param string $label
     * @param string $class_select
     * @param string $class_option
     * @param bool $div
     * @param bool $show_label
     * @param string $div_class
     * @param string|null $selected
     * @param bool $required
     * @param string|null $link_edit lien vers la fiche d'édition du Select (échappé, schémas javascript:/data: refusés)
     * @return string
     */
    public function select(
        array   $l_object, string $name,
        ?string $id = null,
        string  $value_field = 'id',
        string  $name_field = 'name',
        string  $label = '',
        string  $class_select = 'form-control',
        string  $class_option = '',
        bool    $div = true,
        bool    $show_label = true,
        string  $div_class = 'mb-3',
        ?string $selected = null,
        bool    $required = false,
        ?string  $link_edit = null,
    ): string {
        $id = Sec::h($id);
        if (!$label) {
            $label = $name;
        }
        $label = ucfirst(I18n::t($label));
        $value_field = Sec::h($value_field);
        if ($selected === null) {
            $selected = Sec::h($this->entityValue($name));
        }
        $name_field = Sec::h($name_field);
        $class_select = Sec::h($class_select);
        $class_option = Sec::h($class_option);

        if (!$id) {
            $id = $name;
        }

        $html = '';
        if ($div) {
            $html = '<div class="form-group ' . $div_class . '">';
        }
        if ($show_label) {
            $html .= '<label for="' . $id . '" class="form-label">' . $label;
            if ($required) {
                $html .= ' <span class="text-danger">*</span>';
            }
            // pas de schéma dangereux (javascript:, data:, vbscript:...) : seulement http(s) ou lien relatif.
            // Les navigateurs ignorent espaces/tabs/retours ligne dans le schéma ("java\tscript:"), on les retire pour tester.
            $link_scheme = preg_replace('/[\x00-\x20]+/', '', (string)$link_edit);
            if ($link_edit && (!preg_match('#^[a-z][a-z0-9+.\-]*:#i', $link_scheme) || preg_match('#^https?:#i', $link_scheme))) {
                $html .=  '<a target="_blank" rel="noopener noreferrer" href="' . Sec::h($link_edit) . '" class="text-gray"> <small class="pl-2 fa fa-arrow-up-right-from-square"></small></a>';
            }
            $html .= '</label>';
        }
        $html .= "<select id=\"{$id}\" name=\"{$name}\" class=\"{$class_select}\"";
        if ($required) {
            $html .= ' required';
        }
        $html .= '>';
        foreach ($l_object as $object) {
            if (is_array($object)) {
                $options_values = Sec::h($object[$value_field]);
                $options_names = I18n::t($object[$name_field]);
            } else {
                $options_values = Sec::h($object->$value_field);
                $options_names = I18n::t($object->$name_field);
            }
            $html .= "<option value=\"{$options_values}\" class=\"{$class_option}\" ";
            if ((string)$selected === (string)$options_values) {
                $html .= ' selected ';
            }
            $html .= ">{$options_names}</option>";
        }
        $html .= "</select>";
        if ($div) {
            $html .= '</div>';
        }
        $html .= $this->validator_div($name);
        return $html;
    }

    public function hidden(
        string  $name,
        mixed   $value = '',
        ?string $id = null
    ): string {
        $field = $name;
        $name = Sec::h($name);
        if ($value === '' || $value === null) {
            $value = $this->entityValue($field);
        }
        $value = Sec::hNoHtml($value);

        $html = '<input type="hidden" name="' . $name . '"';
        if ($id) {
            $html .= ' id="' . Sec::hNoHtml($id) . '"';
        }
        $html .= ' value="' . $value . '">';

        return $html;
    }

    public function select2Ajax(
        string  $ajax_url,
        string  $name,
        ?string $id = null,
        string  $value_field = 'id',
        string  $name_field = 'name',
        string  $label = '',
        string  $class_select = 'form-control',
        string  $class_option = '',
        bool    $div = true,
        bool    $show_label = true,
        string  $div_class = 'mb-3',
        ?string $selected = null,
        ?string $selectedLabel = null,
        int     $minimumInputLength = 1,
        bool    $required = false,
    ) {
        // Échapper les valeurs pour empêcher les injections XSS
        $id = Sec::h($id);
        if (!$label) {
            $label = $name;
        }
        $label = ucfirst(I18n::t($label));
        $value_field = Sec::h($value_field);
        if ($selected === null) {
            $selected = Sec::h($this->entityValue($name));
        }
        $name_field = Sec::h($name_field);
        $class_select = Sec::h($class_select);
        $class_option = Sec::h($class_option);

        if (!$id) {
            $id = $name;
        }

        // Début de la construction du HTML
        $html = '';
        if ($div) {
            $html = '<div class="form-group ' . $div_class . '">';
        }

        if ($show_label) {
            $html .= '<label for="' . $id . '" class="form-label" >' . $label;
            if ($required) {
                $html .= ' <span class="text-danger">*</span> ';
            }
            $html .= '</label>';
        }

        // Construction de la balise <select>
        $html .= '<select id="' . Sec::hNoHtml($id) . '" name="' .  Sec::hNoHtml($name)  . '" class="' . $class_select . '" ';
        if ($required) {
            $html .= ' required ';
        }
        $html .= '>';

        // Option pré-sélectionnée (valeur brute, sans accolades)
        if ($selected !== null && $selected !== '') {
            $html .= '<option value="' . Sec::hNoHtml($selected) . '" selected>'
                . Sec::hNoHtml($selectedLabel ?? $selected) . '</option>';
        }

        // Fermeture de la balise <select>
        $html .= "</select>";

        if ($div) {
            $html .= '</div>';
        }

        // Génération du JS pour initialiser le Select2 avec AJAX
        // Dans un <script> les entites HTML ne sont pas decodees : json_encode,
        // qui produit un litteral JS valide et neutralise aussi '</script>'.
        $js_id = json_encode('#' . $id);
        $js_url = json_encode($ajax_url);
        $html .= '<script nonce="' . Sec::noneCsp() . '">
        $(document).ready(function() {
            $(' . $js_id . ').select2({
                ajax: {
                    url: ' . $js_url . ',
                    dataType: "json",
                    delay: 250
                },
                minimumInputLength: ' . (int)$minimumInputLength . '
            })
        });
    </script>';

        // Ajouter la validation si nécessaire
        $html .= $this->validator_div($name);

        return $html;
    }

    public function select2(
        array   $l_object, // Liste des objets à afficher dans le select
        string  $name,
        ?string $id = null,
        string  $value_field = 'id',
        string  $name_field = 'name',
        string  $label = '',
        string  $class_select = 'form-control',
        string  $class_option = '',
        bool    $div = true,
        bool    $show_label = true,
        string  $div_class = 'mb-3',
        ?string $selected = null
    ) {
        // Échapper les valeurs & sécuriser
        $id = Sec::h($id);
        if (!$label) {
            $label = $name;
        }
        $label = ucfirst(I18n::t($label));
        $value_field = Sec::h($value_field);
        if ($selected === null) {
            $selected = Sec::h($this->entityValue($name));
        }
        $name_field = Sec::h($name_field);
        $class_select = Sec::h($class_select);
        $class_option = Sec::h($class_option);

        if (!$id) {
            $id = $name;
        }

        // Début de la construction HTML
        $html = '';
        if ($div) {
            $html = '<div class="form-group ' . $div_class . '">';
        }

        // Ajout du label (si requis)
        if ($show_label) {
            $html .= '<label for="' . $id . '" class="form-label">' . $label . '</label>';
        }

        // Création de la balise <select>
        $html .= "<select id=\"{$id}\" name=\"{$name}\" class=\"{$class_select}\">";

        // Parcours de la liste pour générer les options
        foreach ($l_object as $object) {
            if (is_array($object)) {
                $options_values = Sec::h($object[$value_field]);
                $options_names = I18n::t($object[$name_field]);
            } else {
                $options_values = Sec::h($object->$value_field);
                $options_names = I18n::t($object->$name_field);
            }

            // Ajout des options
            $html .= "<option value=\"{$options_values}\" class=\"{$class_option}\" ";
            if ((string)$selected === (string)$options_values) {
                $html .= ' selected ';
            }
            $html .= ">{$options_names}</option>";
        }

        $html .= "</select>";

        // Fermeture de la div
        if ($div) {
            $html .= '</div>';
        }

        // Inclusion de la validation (si nécessaire)
        $html .= $this->validator_div($name);

        // Script JS pour initialiser Select2
        $html .= '<script nonce="' . Sec::noneCsp() . '">
        $(document).ready(function() {
            $(' . json_encode('#' . $id) . ').select2();
        });
    </script>';

        return $html;
    }

    /**
     * Construit le HTML commun a input() et date().
     *
     * Regle de grille Bootstrap respectee ici :
     * une classe col-* porte flex-basis, max-width et un padding de 15px.
     * Elle ne doit jamais etre posee sur un .form-control ni sur un
     * .input-group, qui portent deja display:block/flex et width:100%.
     * La colonne est donc toujours un div conteneur.
     *
     * Le mode horizontal utilise col-12 col-lg-* : en dessous de 992px
     * le label et le champ s'empilent, ce qui evite la coupure de mot
     * quand le form-group est place dans une colonne etroite.
     */
    private function buildField(
        string  $type,
        string  $name,
        string  $label,
        ?string $id,
        string  $placeholder,
        string  $class,
        mixed   $value,
        bool    $required,
        bool    $autocomplete,
        bool    $div,
        bool    $show_label,
        bool    $row,
        int     $label_width,
        int     $input_width,
        string  $div_class,
        string  $fa_icon
    ): string {
        $id = Sec::h($id);
        if (!$label) {
            $label = $name;
        }
        $label = ucfirst(I18n::t($label));
        $field = $name;
        $name = Sec::h($name);
        $type = Sec::h($type);
        $placeholder = Sec::h($placeholder);
        $class = Sec::h($class);
        if ($value === '' || $value === null) {
            $value = $this->entityValue($field);
        }
        $value = Sec::h($value);
        $div_class = Sec::h($div_class);

        if (!$id) {
            $id = $name;
        }

        // grille horizontale seulement si elle a du sens
        $use_grid = $row && $label_width > 0 && $label_width < 12;

        $html = '';
        if ($div) {
            $html .= '<div class="form-group ' . $div_class;
            if ($use_grid) {
                // .row porte des marges negatives de 15px : ne jamais les
                // ecraser avec un m-* sinon les colonnes se decalent
                $html .= ' row';
            }
            $html .= '">';
        }

        if ($show_label) {
            $label_class = $use_grid
                ? 'col-12 col-lg-' . $label_width . ' col-form-label'
                : 'form-label';
            $html .= '<label for="' . $id . '" class="' . $label_class . '">' . $label;
            if ($required) {
                $html .= ' <span class="text-danger">*</span>';
            }
            $html .= '</label>';
        }

        // la colonne est un conteneur, jamais le controle lui meme
        if ($use_grid) {
            $html .= '<div class="col-12 col-lg-' . $input_width . '">';
        }

        if ($fa_icon) {
            $html .= '<div class="input-group">';
            $html .= '<div class="input-group-prepend">';
            $html .= '<span class="input-group-text"><i class="fa ' . Sec::h($fa_icon) . '"></i></span>';
            $html .= '</div>';
        }

        $html .= '<input type="' . $type . '" id="' . $id . '" name="' . $name . '"';
        $html .= ' class="' . $class . '"';
        if ($placeholder) {
            $html .= ' placeholder="' . $placeholder . '"';
        }
        if ($required) {
            $html .= ' required';
        }
        if (!$autocomplete) {
            if ($type === 'password') {
                $html .= ' autocomplete="new-password"';
            } else {
                $html .= ' autocomplete="off"';
            }
        }
        $html .= ' value="' . $value . '"';
        $html .= '>';

        if ($fa_icon) {
            $html .= '</div>';
        }
        if ($use_grid) {
            $html .= '</div>';
        }
        if ($div) {
            $html .= '</div>';
        }

        $html .= $this->validator_div($field);

        return $html;
    }

    /**
     * Genere un input HTML avec label, classes, placeholder et wrapper optionnels.
     *
     * @param string $name Nom du champ, sert aussi d'id si aucun id n'est fourni.
     * @param string $label Libelle associe. Par defaut le nom du champ.
     * @param string|null $id Id personnalise.
     * @param string $type Type de l'input (text, number, password...).
     * @param string $placeholder Placeholder.
     * @param string $class Classe du controle. Laisser form-control seul.
     * @param mixed|string $value Valeur. Lue dans l'entity si vide.
     * @param bool $required Ajoute l'attribut required.
     * @param bool $autocomplete false ajoute autocomplete=off.
     * @param bool $div Enveloppe dans un form-group.
     * @param bool $show_label Affiche le label.
     * @param bool $row Active le mode horizontal label a gauche.
     * @param int $label_width Largeur lg du label en mode horizontal.
     * @param int $input_width Largeur lg du champ en mode horizontal.
     * @param string $div_class Classes du form-group.
     * @param string $fa_icon Icone Font Awesome prefixee dans un input-group.
     * @param string $layout 'inline' garde le mode horizontal, 'break' empile.
     *
     * @return string
     * @throws \Exception Si le type est checkbox, utiliser checkbox().
     */
    public function input(
        string  $name,
        string  $label = '',
        ?string $id = null,
        string  $type = 'text',
        string  $placeholder = '',
        string  $class = 'form-control',
        mixed   $value = '',
        bool    $required = false,
        bool    $autocomplete = false,
        bool    $div = true,
        bool    $show_label = true,
        bool    $row = true,
        int     $label_width = 4,
        int     $input_width = 8,
        string  $div_class = 'mb-3',
        string  $fa_icon = '',
        string  $layout = 'inline'
    ): string {
        if ($type === 'checkbox') {
            throw new \Exception('Use checkbox method for checkbox type');
        }

        // 'break' coupe vraiment la grille au lieu de laisser un row 12/12
        if ($layout === 'break') {
            $row = false;
        }

        return $this->buildField(
            $type,
            $name,
            $label,
            $id,
            $placeholder,
            $class,
            $value,
            $required,
            $autocomplete,
            $div,
            $show_label,
            $row,
            $label_width,
            $input_width,
            $div_class,
            $fa_icon
        );
    }

    public function date(
        string  $name,
        string  $label = '',
        ?string $id = null,
        string  $placeholder = '',
        string  $class = 'form-control',
                $value = '',
        bool    $required = false,
        bool    $autocomplete = false,
        bool    $div = true,
        bool    $show_label = true,
        bool    $row = true,
        int     $label_width = 4,
        int     $input_width = 8,
        string  $div_class = 'mb-3',
        string  $fa_icon = '',
        string  $layout = 'inline'
    ): string {
        if ($layout === 'break') {
            $row = false;
        }

        return $this->buildField(
            'date',
            $name,
            $label,
            $id,
            $placeholder,
            $class,
            $value,
            $required,
            $autocomplete,
            $div,
            $show_label,
            $row,
            $label_width,
            $input_width,
            $div_class,
            $fa_icon
        );
    }

    public function checkbox(
        string  $name,
        string  $label = '',
        ?string $id = null,
        string  $class = 'form-check-input',
        bool    $checked = false,
        bool    $required = false,
        bool    $div = true,
        string  $div_class = 'mb-3',
        bool    $show_label = true,
    ): string {
        // Échappement des valeurs
        $id = Sec::h($id);
        $field = $name;
        $name = Sec::h($name);
        $class = Sec::h($class);
        $div_class = Sec::h($div_class);

        // L'entity ne pilote la case que si la clef existe reellement
        if ($this->entityHas($field)) {
            $checked = (bool)$this->entityValue($field);
        }

        // Gestion du label (si vide, prend le nom par défaut)
        if (!$label) {
            $label = $field;
        }
        $label = ucfirst(I18n::t($label));

        // Si l'ID n'est pas défini, on utilise le nom comme ID
        if (!$id) {
            $id = $name;
        }

        // Génération de l'HTML
        $html = '';
        if ($div) {
            $html .= '<div class="form-check ' . $div_class . '">';
        }

        // Ajout du champ checkbox
        $html .= '<input type="checkbox" id="' . $id . '" name="' . $name . '" class="' . $class . '" value="1"';
        if ($checked) {
            $html .= ' checked';
        }
        if ($required) {
            $html .= ' required';
        }
        $html .= '>';

        // Ajout d'un label si demandé
        if ($show_label) {
            $html .= '<label class="form-check-label" for="' . $id . '">' . $label . '</label>';
        }

        if ($div) {
            $html .= '</div>';
        }

        // Gestion des erreurs via le validator
        $html .= $this->validator_div($field);

        return $html;
    }

    public function textarea(
        string  $name,
        string  $label = '',
        ?string $id = null,
        string  $placeholder = '',
        string  $class = 'form-control',
                $value = '',
        bool    $required = false,
        bool    $autocomplete = false,
        bool    $div = true,
        bool    $show_label = true,
        int     $rows = 5,
        int     $cols = 50,
        string  $div_class = 'mb-3'
    ): string {
        $id = Sec::h($id);
        if (!$label) {
            $label = $name;
        }
        $label = ucfirst(I18n::t($label));
        $field = $name;
        $name = Sec::h($name);
        $placeholder = Sec::h($placeholder);
        $class = Sec::h($class);
        if ($value === '' || $value === null) {
            $value = $this->entityValue($field);
        }
        $value = Sec::h($value);
        $div_class = Sec::h($div_class);

        if (!$id) {
            $id = $name;
        }

        $html = '';
        if ($div) {
            $html .= '<div class="form-group ' . $div_class . '">';
        }

        if ($show_label) {
            $html .= '<label for="' . $id . '" class="form-label">' . $label;
            if ($required) {
                $html .= ' <span class="text-danger">*</span>';
            }
            $html .= '</label>';
        }

        $html .= '<textarea id="' . $id . '" name="' . $name . '" class="' . $class . '"';
        if ($placeholder) {
            $html .= ' placeholder="' . $placeholder . '"';
        }
        if ($required) {
            $html .= ' required';
        }
        if (!$autocomplete) {
            $html .= ' autocomplete="off"';
        }
        $html .= ' rows="' . $rows . '" cols="' . $cols . '">';
        $html .= $value;
        $html .='</textarea>';

        if ($div) {
            $html .= '</div>';
        }
        $html .= $this->validator_div($field);

        return $html;
    }

    /**
     * .form-control sur un <button> force width:100% et une hauteur d'input.
     * Pour un bouton pleine largeur utiliser btn-block, pas form-control.
     */
    public function submit(
        string $value,
        string $class = 'btn btn-success',
        bool $div = true,
        string $div_class = ''
    ): string
    {
        $value = I18n::t($value);
        $class = Sec::h($class);
        $div_class = Sec::h($div_class);

        $html = '';
        if ($div) {
            $html .= '<div class="' . $div_class . '">';
        }

        if($this->ajax){
            $html .= '<a href="javascript:void(0)" onclick="submitAjax' . $this->unique_id .'()" class="ajax_submit ' . $class . '">' . $value . '</a>';
        } else {
            $html .= '<button type="submit" class="' . $class . '">💾 ' . $value . '</button>';
        }

        if ($div) {
            $html .= '</div>';
        }

        return $html;
    }


    private function validator_div(string $name): string
    {
        if (!$this->validator) {
            return '';
        }
        $html = '';
        if ($this->validator->hasError($name)) {
            $html .= '<div class="text-danger p-0 m-0">';
            foreach ($this->validator->popError($name) as $error) {
                $html .= '<p>' . I18n::t($error) . '</p>';
            }
            $html .= '</div>';
        }
        return $html;
    }

    public function formStart(
        string $method = 'post',
        bool $multipart = false,
        bool $autocomplete=true,
        bool $error_summary = true,
        bool $csrf = true,
    ): string
    {
        $action = Sec::h($this->action);
        $method = Sec::h($method);

        $html = '';
        $html .= '<form action="' . $action . '" method="' . $method . '"';
        $html .= ' id="' . $this->unique_id . '"';
        if($this->ajax){
            $html .= ' data-ajax="true"';
        }
        if ($multipart) {
            $html .= ' enctype="multipart/form-data"';
        }
        if (!$autocomplete) {
            $html .= ' autocomplete="off"';
        }
        $html .= '>';
        if ($csrf) {
            $html .= Csrf::inputHtml();
        }
        if ($error_summary) {
            $html .= $this->errorSummary();
        }

        // si c'est un model et qu'il a un attr id on l'ajoute automatiquement
        $entity_id = $this->entityValue('id');
        if ($entity_id) {
            $html .= '<input type="hidden" name="id" value="' . Sec::h($entity_id) . '">';
        }

        return $html;
    }


    public function formEnd(bool $div = false, bool $error_summary = true): string
    {
        $html = '';
        if ($error_summary) {
            $html .= $this->errorSummary();
        }
        $html .= '</form>';

        if ($div) {
            $html .= '</div>';
        }

        return $html;
    }

    public function ajaxSubmit($fn_succcess_name = null, $fn_error_name = null): string
    {
        $parts = [];
        $parts[] = 'loaderMessage: ' . json_encode(I18n::t('Processing...'));
        if ($fn_succcess_name) {
            $parts[] = 'success: ' . $fn_succcess_name;
        }
        if ($fn_error_name) {
            $parts[] = 'error: ' . $fn_error_name;
        }

        $html = '<script nonce="' . Sec::noneCsp() . '">'. "\r";
        $html .= 'function submitAjax' . $this->unique_id . '() {' . "\r";
        $html .= 'let form = document.getElementById("' . $this->unique_id . '");'. "\r";
        $html .= 'let formData = new FormData(form);'. "\r";
        $html .= 'let formDataDict = Object.fromEntries(formData.entries());'. "\r";
        $html .= 'api.post('. json_encode($this->action) .', formDataDict, {' . "\r";
        $html .= implode(',' . "\r", $parts) . "\r";
        $html .= '})'  . "\r";
        $html .= '}'  . "\r";
        $html .= '</script>'  . "\r";
        return $html;
    }

    public function errorSummary(): string
    {
        $html = '';
        if ($this->validator && $this->validator->getErrors()) {
            $html .= '<div class="text-danger">';
            $html .= '<p>' . I18n::t('Please correct the following errors:') . '</p>';
            $html .= '<ul>';
            foreach ($this->validator->getErrors() as $field => $errors) {
                foreach ($errors as $error) {
                    $html .= '<li>' . I18n::t($error) . '</li>';
                }
            }
            $html .= '</ul>';
            $html .= '</div>';
        }
        return $html;
    }

    public static function searchFormRow($searchText = null): string
    {
        // Mode DataTables : la recherche se fait cote navigateur, sur les lignes
        // deja chargees. Un formulaire POST ferait doublon, on ne rend rien.
        if (Paginator::isDatatableMode()) {
            return '';
        }

        $placeholder = I18n::t('Search');
        $searchText = Sec::h($searchText ?? '');
        $active = '';
        if($searchText) {
            $active = 'is-valid';
        }
        // input-group plutot que deux colonnes : le bouton reste colle au champ
        // et ne porte pas form-control, qui casserait sa hauteur
        return  <<<HTML
                <div class="input-group">
                    <input type="text" class="form-control $active" name="search_text"
                           id="inputSuccess" placeholder="$placeholder"
                                value="$searchText"
                    >
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-default" name="search"
                                value="search"
                        >
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                HTML;


    }

    /**
     * Utilitaire pour générer l'attribut value d'un input HTML
     * Si la clef n'existe pas dans le tableau, retourne value=''
     * Si la clef existe mais est null ou '' retourne value=''
     * Pour les types supportés (int, float, string, bool) retourne value='Sec::hNoHtml($value)'
     *
     * Sécurisé contre XSS
     *
     * @param array $entity
     * @param string $key
     * @param bool $safe Si true, la valeur est considérée comme déjà sécurisée
     * @return string
     * @throws \Exception Si le type n'est pas supporté
     */
    public static function valueAttrOrBlank(?array $entity, string $key, bool $safe = false): string
    {
        if(empty($entity) || !is_array($entity)) {
            return '';
        }
        if (!array_key_exists($key, $entity)) {
            return '';
        }

        $value = $entity[$key];

        if ($value === null || $value === '') {
            return "value=''";
        }

        if (!is_scalar($value)) {
            throw new \Exception('This type of var is not supported by valueAttrOrBlank, use scalar');
        }
        if ($safe) {
            return "value='" . $value . "'";
        }

        return "value='" . Sec::hNoHtml($value) . "'";
    }

    public function errorDiv(string $fieldName): string
    {
        if (!$this->validator) {
            return '';
        }
        $errors = $this->validator->getError($fieldName);
        if (empty($errors)) {
            return '';
        }

        $html = '';
        foreach ($errors as $err) {
            $html .= '<div class="text-danger">* ' . I18n::t($err) . '</div>';
        }
        return $html;
    }
}