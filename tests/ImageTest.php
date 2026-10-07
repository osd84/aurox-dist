<?php

require_once '../aurox.php';

use OsdAurox\Image;
use osd84\BrutalTestRunner\BrutalTestRunner;

$tester = new BrutalTestRunner();
$tester->header(__FILE__);

// Initialiser des données de test
if (!file_exists('test_images')) {
    mkdir('test_images');
}

$tester->header('Test de resize()');

// Création d'une image de test
$imagePath = 'test_images/sample_image.jpg';
$image = imagecreatetruecolor(800, 600);
$backgroundColor = imagecolorallocate($image, 255, 255, 255); // Blanc
imagefill($image, 0, 0, $backgroundColor);
imagejpeg($image, $imagePath);

// Tests
try {
    // Test : Redimensionnement dans des dimensions plus petites
    $resizedPath = Image::resize($imagePath, 400, 300);
    $tester->assertEqual(file_exists($resizedPath), true, 'Le fichier redimensionné existe');
    list($width, $height) = getimagesize($resizedPath);
    $tester->assertEqual($width, 400, 'La largeur est correcte');
    $tester->assertEqual($height, 300, 'La hauteur est correcte');

    // Test : Redimensionnement tout en gardant les proportions
    $resizedPathProportional = Image::resize($imagePath, 500, 500);
    list($width, $height) = getimagesize($resizedPathProportional);
    $tester->assertEqual($width, 500, 'La largeur est correcte pour proportions maintenues');
    $tester->assertEqual($height, 375, 'La hauteur est correcte pour proportions maintenues');

    // Test : Dimensions plus grandes que l'original
    $resizedPathTooLarge = Image::resize($imagePath, 1000, 800);
    list($width, $height) = getimagesize($resizedPathTooLarge);
    $tester->assertEqual($width, 1000, 'La largeur est plus grande aux dimensions d’origine');
    $tester->assertEqual($height, 750, 'La hauteur est plus grande que les dimensions d’origine');

    // Test : Fichier invalide ou inexistant
    try {
        Image::resize('non_existent_file.jpg', 300, 200);
        $tester->assertEqual(false, true, 'Exception non levée pour un fichier inexistant');
    } catch (Exception $e) {
        $tester->assertEqual(true, true, 'Exception levée pour un fichier inexistant');
    }

    // Test : Largeur ou hauteur invalides
    try {
        $r = Image::resize($imagePath, -100, 100);
        $tester->assertEqual(false, true, 'Exception non levée pour une largeur invalide');
    } catch (Exception $e) {
        $tester->assertEqual(true, true, 'Exception levée pour une largeur invalide');
    }

} finally {
    // Nettoyage
    array_map('unlink', glob("test_images/*"));
    rmdir('test_images');
}

$tester->header('Test de reduceToMaxSize()');

// Initialiser des données de test
if (!file_exists('test_images')) {
    mkdir('test_images');
}

// Création d'une image de grande taille (8 Mo simulée)
$imagePath = 'test_images/large_image.jpg';
$image = imagecreatetruecolor(3000, 3000);
$backgroundColor = imagecolorallocate($image, 255, 255, 255); // Blanc
imagefill($image, 0, 0, $backgroundColor);
imagejpeg($image, $imagePath, 100); // Compression minimale pour une grande taille

// Tests
try {
    // Test 1 : Réduction de la taille pour atteindre au maximum 2 Mo
    $reducedPath = Image::reduceToMaxSize($imagePath, 2);
    $tester->assertEqual(file_exists($reducedPath), true, 'Le fichier réduit existe');
    $sizeInBytes = filesize($reducedPath);
    $tester->assertEqual($sizeInBytes <= 2 * 1024 * 1024, true, 'La taille est inférieure ou égale à 2 Mo');

    // Test 2 : Validation qu’un fichier déjà plus petit que 2 Mo ne change pas trop
    // Création d'une image de petite taille (500 Ko simulée)
    $smallImagePath = 'test_images/small_image.jpg';
    $image = imagecreatetruecolor(800, 800);
    imagejpeg($image, $smallImagePath, 50); // Compression modérée

    $reducedSmallImage = Image::reduceToMaxSize($smallImagePath, 2);
    $tester->assertEqual(file_exists($reducedSmallImage), true, 'Le fichier réduit existe pour une petite image');
    $tester->assertEqual(filesize($reducedSmallImage) <= 500 * 1024, true, 'La taille de l’image reste petite (500 Ko environ)');

    // Test 3 : Fichier inexistant
    try {
        Image::reduceToMaxSize('test_images/non_existent.jpg', 2);
        $tester->assertEqual(false, true, 'Exception non levée pour un fichier inexistant');
    } catch (Exception $e) {
        $tester->assertEqual(true, true, 'Exception levée pour un fichier inexistant');
    }

    // Test 4 : Impossible réduire sous la taille max
    // Création d'une image extrêmement complexe simulant un fichier non compressible
    $complexImagePath = 'test_images/complex_image.jpg';
    $image = imagecreatetruecolor(4000, 4000);
    $complexColor = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));
    imagefill($image, 0, 0, $complexColor);
    imagejpeg($image, $complexImagePath, 100); // Très peu compressé


} finally {
    // Nettoyage des fichiers et dossiers créés pour le test
    array_map('unlink', glob("test_images/*"));
    rmdir('test_images');
}

$tester->header('Test de resizeAndReduce()');

// Initialiser des données de test
if (!file_exists('test_images')) {
    mkdir('test_images');
}

// Création d'une image de grande taille
$imagePath = 'test_images/large_sample_image.jpg';
$image = imagecreatetruecolor(3000, 3000); // Image de 3000x3000 px
$backgroundColor = imagecolorallocate($image, 200, 200, 200); // Gris clair
imagefill($image, 0, 0, $backgroundColor);
imagejpeg($image, $imagePath, 100); // Compression minimale pour un poids élevé

// Tests
try {
    // Test 1 : Redimensionner et réduire pour un fichier plus petit que 2 Mo
    $maxWidth = 800;
    $maxHeight = 600;
    $maxSize = 2; // 2 Mo

    $resultPath = Image::resizeAndReduce($imagePath, $maxWidth, $maxHeight, $maxSize);

    // Vérification 1 : Le fichier redimensionné/réduit existe
    $tester->assertEqual(file_exists($resultPath), true, 'Le fichier redimensionné/réduit existe.');

    // Vérification 2 : Dimensions correctes
    [$width, $height] = getimagesize($resultPath);
    $tester->assertEqual($width <= $maxWidth, true, 'La largeur est correcte.');
    $tester->assertEqual($height <= $maxHeight, true, 'La hauteur est correcte.');

    // Vérification 3 : Taille du fichier
    $fileSizeInMB = filesize($resultPath) / (1024 * 1024);
    $tester->assertEqual($fileSizeInMB <= $maxSize, true, 'La taille du fichier est inférieure ou égale à la limite.');

    // Test 2 : Vérification qu'un fichier déjà petit ne change pas beaucoup
    $smallImage = 'test_images/small_image.jpg';
    $image = imagecreatetruecolor(500, 500);
    imagejpeg($image, $smallImage, 75); // Fichier compressé

    $resultPathSmall = Image::resizeAndReduce($smallImage, 300, 300, 2);
    $tester->assertEqual(file_exists($resultPathSmall), true, 'Le fichier réduit existe pour une petite image.');
    $tester->assertEqual(filesize($resultPathSmall) <= filesize($smallImage), true, 'La taille n’a pas beaucoup changé pour une petite image.');

    // Test 3 : Gestion des fichiers inexistants
    try {
        Image::resizeAndReduce('test_images/non_existent.jpg', 300, 300, 2);
        $tester->assertEqual(false, true, 'Exception non levée pour un fichier inexistant.');
    } catch (Exception $e) {
        $tester->assertEqual(true, true, 'Exception levée pour un fichier inexistant.');
    }

} finally {
    // Nettoyage des fichiers et dossiers créés pour le test
    array_map('unlink', glob("test_images/*"));
    rmdir('test_images');
}


$tester->footer(exit: false);
