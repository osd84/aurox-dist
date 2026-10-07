<?php
if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) {
    die('Illegal request');
}

use OsdAurox\Flash;
use OsdAurox\I18n;
use OsdAurox\Sec;


$flashMessages = Flash::get(clear: true) ?? [];

?>
<div class="container pb-2" style="transform: none;">
    <div class="row"  id="flash-container">
        <div class="col-md-12"  >
                        <?php foreach ($flashMessages as $type => $messages) : ?>
                            <?php foreach ($messages as $message) : ?>
                                <div class="alert alert-<?= Sec::h($type) ?>">
                                    <h5 class="text-<?= Sec::h($type) ?>">
                                    <?php if ($type == 'success'): ?>
                                        <i class="icon fas fa-check"></i>
                                        <?= I18n::t('Success') ?>
                                    <?php elseif ($type == 'warning'): ?>
                                        <i class="icon fas fa-exclamation-triangle"></i>
                                        <?= I18n::t('Attention') ?>
                                    <?php elseif ($type == 'danger'): ?>
                                        <i class="icon fas fa-ban"></i>
                                        <?= I18n::t('Attention') ?>
                                    <?php else: ?>
                                        <i class="icon fas fa-times"></i>
                                        <?= I18n::t('Info') ?>
                                    <?php endif; ?>
                                    </h5>
                                    <?= Sec::h($message) ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
        </div>
    </div>
</div>
