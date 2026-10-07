<?php

if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) {
    die('Illegal request');
}

use App\AppUrls;
use OsdAurox\Sec;

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= Sec::hNoHtml($title ?? "Aurox - BDD") ?></title>
    <?php if ($canonical ?? null): ?>
        <link rel="canonical" href="<?= Sec::hNoHtml($canonical) ?>">
    <?php endif; ?>

    <!-- Jquery et oui ! encore et toujours -->
    <script src="/js/jquery-3.7.1.min.js"></script>

    <!-- Boostrap -->
    <link rel="stylesheet" href="/plugin/bootstrap-5.0.2-dist/css/bootstrap.min.css">
    <!-- Boostrap Js -->
    <script src="/plugin//bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>

    <!-- OSD_Aurox scripts -->
    <script src="/js/osd-modal-loader.js"></script>
    <script src="/js/osd-modal-alert.js"></script>
    <script src="/js/osd-modal-confirm.js"></script>
    <script src="/js/osd-bp.js"></script>
    <script src="/js/osd_utils.js"></script>

    <!-- Select2 -->
    <link href="/plugin/select2/css/select2.min.css" rel="stylesheet" />
    <script src="/plugin/select2/js/select2.min.js"></script>

    <!-- Ajoutez vos scripts -->


    <!-- Ajoutez vos feuilles de style -->
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<div class="container">

    <header class="py-3">
        <h1><?= Sec::hNoHtml($headerTitle ?? "Bienvenue sur mon site"); ?></h1>
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="<?= Sec::hNoHtml(AppUrls::HOME) ?>">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= Sec::hNoHtml(AppUrls::PAGE_FORMS) ?>">Forms</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= Sec::hNoHtml(AppUrls::PAGE_MODALS) ?>">Modals</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= Sec::hNoHtml(AppUrls::NOT_FOUND) ?>">404</a></li>
                </ul>
            </div>
        </nav>
    </header>
    <hr>
    <?php require_once APP_ROOT . '/templates/helpers/flash.php'; ?>