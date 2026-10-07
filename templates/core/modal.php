<?php

use OsdAurox\Modal;
use OsdAurox\Sec;

$modal = $modal ?? null;
if(!$modal instanceof Modal)
{
    throw new Exception('Modal not defined');
}

?>
<!-- Modal -->
<div class="<?= Sec::hNoHtml($modal->class) ?>" id="<?= Sec::hNoHtml($modal->id) ?>" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
     aria-labelledby="<?= Sec::hNoHtml($modal->id) ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="<?= Sec::hNoHtml($modal->id) ?>Title"><?= Sec::hNoHtml($modal->title) ?></h5>
                <?php if ($modal->showClose): ?>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <?php endif; ?>
            </div>
            <div class="modal-body">
                <?= Sec::hNoHtml($modal->msg) ?>
                <?php if ($modal->showInput): ?>
                 <form>
                     <input type="text" name="modal-input" class="form-control" id="<?= Sec::hNoHtml($modal->id) ?>val">
                 </form>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <?php if ($modal->showBtn): ?>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= Sec::hNoHtml($modal->btnCancel) ?></button>
                    <button type="button" class="btn btn-primary"><?= Sec::hNoHtml($modal->btnAccept) ?></button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>