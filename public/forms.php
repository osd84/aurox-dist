<?php


use App\AppUrls;
use OsdAurox\Csrf;
use OsdAurox\Flash;
use OsdAurox\FormValidator;
use OsdAurox\Forms;
use OsdAurox\I18n;
use OsdAurox\Sec;

$title = 'Aurox';

require_once '../aurox.php';

$csrf = Csrf::protect(forcePerRequest: true); // Le jeton CSRF va changer à chaque requête Opération sensibles, impossible à "rejouer"


// Entity au format Array
$entity = [
        'id' => 1,
        'email' => 'test@example.fr',
        'description' => 'test',
        'category_id' => 1,
        'category_name' => 'CAT 1'
];

// Les règles de validation de l'entity
$rules = [
    'email' => ['type' => 'mail', 'required' => true],
    'description' => ['type' => 'varchar', 'optional' => true],
    'category_id' => ['type' => 'integer', 'required' => true, 'positive' => true],
];

// Instance du validator de formulaire
$formValidator = new FormValidator();

// Si le formulaire est soumis, on valide les infos
if(Sec::isPost()) {

    $entity['email'] = Sec::hNoHtml($_POST['email'] ?? '');
    $entity['category_id'] = (int) Sec::hNoHtml($_POST['category_id'] ?? 0);
    $entity['description'] = Sec::hNoHtml($_POST['description'] ?? '');

    $formValidator->validate($entity, $rules);
    if ($formValidator->isValid()) {
        Flash::success(I18n::t('Your profile has been updated'));
    }
}

?>
<?php require('../templates/header.php'); ?>
<main class="py-4">
    <h1>Outil - Formulaires</h1>



    <h3>Gestionnaire minimaliste de Modal en PHP</h3>

    <div class="content-wrapper">
        <div class="content pt-5">
            <div class="container-fluid col-md-6">
                <?php
                $form = new Forms(AppUrls::PAGE_FORMS,
                    validator: $formValidator,
                    entity: $entity)
                ?>
                <?= $form->formStart(autocomplete: false) ?>

                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fa fa-vcard"></i>
                            <?= I18n::t('My profile') ?>
                        </h3>
                    </div>

                    <div class="card-body">


                                <!-- input text classique -->

                                <?= $form->input('email', label: I18n::t('Email'), type: 'email', required: true, row: false, div_class: '') ?>

                                <!-- protection Csrf -->
                                <?= Csrf::inputHtml(); ?>


                                <!-- select2Ajax via Forms -->

                                <?= $form->select2Ajax(
                                    ajax_url: AppUrls::CATEGORY_SELECT2,
                                    name : 'category_id',
                                    id : 'category_id',
                                    label: I18n::t('Category'),
                                    selected: $entity['category_id'] ?? '',
                                    selectedLabel: $entity['category_name'] ?? '',
                                    minimumInputLength: 0,
                                    required: true,
                                    div_class : 'mb-3 mt-2'
                                ) ?>
                                <!-- Version plus classique d'un formulaire, sans utiliser Forms -->
                                <div class="form-group">
                                    <label for="description"><?= I18n::t('Description') ?>
                                    </label>
                                    <input type="text" class="form-control" id="description" name="description"
                                        <?= Forms::valueAttrOrBlank($entity, 'description') ?>
                                    >
                                    <?= $form->errorDiv('description') ?>
                                </div>


                        <div class='row mt-3'>
                            <div class='col-md-12 text-center'>
                                <button type="submit" class="btn btn-primary"><?= I18n::t('Save') ?></button>
                            </div>
                        </div>

                    </div>
                </div>

                <?= $form->formEnd() ?>
                <?php if (isset($entity)): ?>
                    <?= $form->ajaxSubmit() ?>
                <?php endif; ?>
            </div>
            <!-- /.card -->
        </div>
    </div>


</main>
<?php require('../templates/footer.php'); ?>
