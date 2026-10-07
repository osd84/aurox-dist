<?php

namespace OsdAurox;

use AllowDynamicProperties;
use OsdAurox\I18n;

class Modal
{

    public string $template = 'modal.php';

    public string $title;
    public string $msg;
    public string $type;
    public string $btnAccept;
    public string $btnCancel;
    public string $id;
    public string $class;
    public bool $showBtn = true;
    public bool $showClose = true;
    public bool $showInput = false;

    public function __construct($title, $msg, $type = 'info', $template = null,
                                $btnAccept = null, $btnCancel = null, $id = 'modal-default',
                                $class = 'modal fade',
                                $showClose = true, $showInput = false,
                                $showBtn = true)
    {
        if ($template) {
            $this->template = $template;
        }
        $this->title = $title;
        $this->msg = $msg;
        $this->type = in_array($type, ['info', 'warning', 'danger', 'success']) ? $type : 'info';

        $this->btnAccept = $btnAccept ?? I18n::t('Accept');
        $this->btnCancel = $btnCancel ?? I18n::t('Cancel');

        $this->showClose = $showClose;
        $this->showInput = $showInput;
        $this->showBtn = $showBtn;

        $this->id = $id;
        $this->class = $class;

    }

    public function render()
    {
        // Définir le chemin vers le template
        $templatePath = (APP_ROOT . '/templates/core/' . $this->template);

        // Vérifier si le fichier existe
        if (!file_exists($templatePath)) {
            throw new \RuntimeException("Template Not Found : " . Sec::hNoHtml($this->template));
        }

        // Rendre les variables disponibles pour le fichier template
        $modal = $this;

        ob_start();
        try {
            include $templatePath;
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }
        return ob_get_clean();
    }

    public static function newModal($title, $msg, $type = 'info', $template = null,
                                    $btnAccept = null, $btnCancel = null, $id = 'modal-default',
                                    $class = 'modal fade',
                                    $showClose = true, $showInput = false,
                                    $showBtn = true)
    {
        $modal = new Modal(title: $title, msg:  $msg, type:  $type, template:  $template,
            btnAccept: $btnAccept, btnCancel: $btnCancel, id:  $id, class:  $class, showClose: $showClose,
            showInput: $showInput, showBtn: $showBtn);
        return $modal->render();
    }

    public static function newLoader($title=null, $msg=null, $type = 'info', $template = null,
                                     $btnAccept = null, $btnCancel = null, $id = 'modal-loader',
                                     $class = 'modal fade',
                                     $showClose = false, $showInput = false,
                                     $showBtn = false)
    {
        if (!$title) {
            $title = I18n::t('Loading...');
        }
        if (!$msg) {
            $msg = I18n::t('Please wait while the content is loading..');
        }

        $modal = new Modal(title: $title, msg:  $msg, type:  $type, template:  $template,
            btnAccept: $btnAccept, btnCancel: $btnCancel, id:  $id, class:  $class, showClose: $showClose,
            showInput: $showInput, showBtn: $showBtn);
        $modal->showBtn = false;

        return $modal->render();
    }

    public static function newPrompt($title=null, $msg=null, $type = 'info', $template = null,
                                     $btnAccept = null, $btnCancel = null, $id = 'modal-prompt',
                                     $class = 'modal fade',
                                     $showClose = false, $showInput = true,
                                     $showBtn = true)
    {
        if (!$title) {
            $title = I18n::t('Please complete the form below');
        }
        if (!$msg) {
            $msg = I18n::t('Enter the required information :');
        }
        if($btnAccept === null) {
            $btnAccept = I18n::t('Save');
        }
        if($btnCancel === null) {
            $btnCancel = I18n::t('Cancel');
        }

        $modal = new Modal(title: $title, msg:  $msg, type:  $type, template:  $template,
            btnAccept: $btnAccept, btnCancel: $btnCancel, id:  $id, class:  $class, showClose: $showClose,
            showInput: $showInput, showBtn: $showBtn);

        return $modal->render();
    }



}