<?php


use OsdAurox\Base;
use OsdAurox\Captcha;
use OsdAurox\Flash;
use OsdAurox\Modal;
use OsdAurox\Sec;

$title = 'Aurox';

require_once '../aurox.php';


if (Sec::isPost()) {
    $captcha = Sec::getParam('captcha');

    if(Captcha::verify($captcha)) {
        Flash::success('Captcha valide ! Vous avez entré la bonne lettre.');
    } else {
        Flash::error('Captcha invalide ! La lettre entrée est incorrecte.');
        Captcha::generateCode();
    }

} else {
    Captcha::generateCode();
}
?>
<?php require('../templates/header.php'); ?>
<main class="py-4">
    <h1>Captcha</h1>

    <form method="POST"  >
        <div class="mb-3">
            <?= Captcha::captchaHtml() ?>
        </div>
        <button type="submit" class="btn btn-primary">Vérifier</button>

    </form>

</main>
<?php require('../templates/footer.php'); ?>
