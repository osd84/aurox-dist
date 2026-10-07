<?php


use App\AppUrls;
use OsdAurox\Flash;
use OsdAurox\FormValidator;
use OsdAurox\Base;
use OsdAurox\Forms;
use OsdAurox\I18n;
use OsdAurox\Modal;
use OsdAurox\Sec;
use OsdAurox\Validator;

$title = 'OSD_Aurox™ - Mobile';


require_once '../aurox.php';

?>
<?php require('../templates/header.php'); ?>
<main class="py-4">
    <h1>Base::isMobile()</h1>

    <h3>Detection des petits écran Base::isMobile()</h3>

    <?php if (Base::isMobile()): ?>
        <p>C'est un petit écran</p>
    <?php else: ?>
        <p>C'est un grand écran</p>
    <?php endif; ?>


</main>
<?php require('../templates/footer.php'); ?>
