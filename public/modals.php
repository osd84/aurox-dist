<?php


use OsdAurox\Base;
use OsdAurox\Modal;

$title = 'Aurox';

require_once '../aurox.php';


?>
<?php require('../templates/header.php'); ?>
<main class="py-4">
    <h1>Outils - Modales</h1>

    <h3>Gestionnaire minimaliste de Modal en PHP</h3>
    <?= Modal::newModal('Ma petite Modal', 'Contenu de la modal', 'info') ?>
    <div class="row">
        <div class="col-12">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-default">
                Modale classique #1
            </button>
        </div>
    </div>

    <?= Modal::newLoader(showClose: True) ?>
    <div class="row mt-2">
        <div class="col-12">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-loader">
                Modale de chargement #2
            </button>
        </div>
    </div>

    <?= Modal::newPrompt(showClose: True) ?>
    <div class="row mt-2">
        <div class="col-12">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-prompt">
                Modale de saisie #3
            </button>
        </div>
    </div>

    <h3 class="mt-2">Gestionnaire minimaliste de Modal en JS</h3>
    <div class="row mt-2">
        <div class="col-12">
            <p>
                osd-modal-loader.js -> Affiche une modale bloquante pour indiquer que le site est en cours de chargement.
            </p>
        </div>
    </div>
    <div class="row mt-2">
        <div class="col-12">
            <button type="button" class="btn btn-secondary" onclick="osdModalLoader.show('Chargement', 'Veuillez patienter, cliquez sur la croix pour fermer', true);">Affiche le loader</button>
        </div>
    </div>
    <div class="row mt-2">
        <div class="col-12">
            <button type="button" class="btn btn-secondary" onclick="showAndHideAlert();">Masquer le Loader</button>
        </div>
        <script>
            async function showAndHideAlert() {
                osdModalLoader.show('Chargement #2', 'Veuillez encore patienter', true);
                osdModalLoader.updateTitle('Veuillez patienter #2')
                osdModalLoader.updateMsg('Masquage auto dans 2 secondes')
                setTimeout(() => {
                    osdModalLoader.hide();
                }, 1000)
            }
        </script>
    </div>

    <h3 class="mt-2">Gestionnaire minimaliste de Modal en JS</h3>
    <div class="row mt-2">
        <div class="col-12">
            <p>
                osd-modal-alert.js -> équivalent de alert()
            </p>
        </div>
    </div>
    <div class="row mt-2">
        <div class="col-12">
            <button type="button" class="btn btn-secondary" onclick="osdModalAlert.alert('Titre alerte', 'Contenu alerte');">Affiche une alerte</button>
        </div>
    </div>
    <div class="row mt-2">
        <div class="col-12">
            <p>
                osd-modal-confirm.js -> équivalent de confirm()
            </p>
        </div>
    </div>
    <div class="row mt-2">
        <div class="col-12">
            <button type="button" class="btn btn-success" onclick="showConfirm();">Affiche une confirm</button>
            <button type="button" class="btn btn-danger" onclick="showConfirmDanger();">Affiche une confirm danger</button>
        </div>
    </div>
    <script>

        async function showConfirm() {
            const isConfirmed = await osdModalConfirm.confirm(
                'Confirmation',
                'Voulez vous confirmer ?',
                {
                    btnClass: 'btn-success',
                    confirmText: 'OK',
                }
            );
            console.log('isConfirmed vaut ', isConfirmed);
        }

        async function showConfirmDanger() {
            const isConfirmed = await osdModalConfirm.confirm(
                'Confirmation',
                'Voulez vous confirmer ?',
                {
                    btnClass: 'btn-danger',
                    confirmText: 'OK',
                }
            );
            console.log('isConfirmed vaut ', isConfirmed);
        }
    </script>


</main>
<?php require('../templates/footer.php'); ?>
