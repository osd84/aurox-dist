<?php

namespace OsdAurox;

use Imagick;

class Image
{
    public static function resize(string $sourcePath, int $maxWidth, int $maxHeight): string
    {

        if (!file_exists($sourcePath)) {
            throw new \Exception("Le fichier source n'existe pas : $sourcePath");
        }

        if ($maxWidth <= 0 || $maxHeight <= 0) {
            throw new \Exception("Les dimensions maximales doivent être supérieures à 0.");
        }


        // Charger l'image source
        $imagick = new Imagick($sourcePath);
        $imagick->setImageFormat('png');


        // Redimensionner l'image tout en conservant les proportions
        $imagick->resizeImage($maxWidth, $maxHeight, Imagick::FILTER_LANCZOS, 1, true);

        $ext = pathinfo($sourcePath, PATHINFO_EXTENSION);

        // Sauvegarder l'image redimensionnée
        $destinationPath = str_replace('.' . $ext, '_resized.' . $ext, $sourcePath);

        $imagick->writeImage($destinationPath);

        // Libérer la mémoire
        $imagick->clear();

        return $destinationPath;
    }

    public static function reduceToMaxSize(string $sourcePath, float $maxSize = 2): string
    {
        // Convertir la taille maximale (en Mo) en octets
        $maxSizeInBytes = $maxSize * 1024 * 1024;

        // Charger l'image source
        $imagick = new Imagick($sourcePath);
        $imagick->setImageFormat('png');


        // Réduire la qualité progressivement et vérifier la taille
        $quality = 90; // Départ d'une qualité de 90
        while ($imagick->getImageLength() > $maxSizeInBytes && $quality > 10) {
            $imagick->setImageCompressionQuality($quality);
            $imagick->stripImage(); // Supprime les métadonnées pour réduire la taille
            $quality -= 5; // Diminue la qualité à chaque itération
        }

        // Sauvegarder l'image compressée dans un nouveau fichier
        $ext = pathinfo($sourcePath, PATHINFO_EXTENSION);
        $destinationPath = str_replace('.' . $ext, '_compressed.' . $ext, $sourcePath);
        $imagick->writeImage($destinationPath);

        // Libérer la mémoire
        $imagick->clear();

        // Vérifier que l'image compressée a bien été réduite à la taille spécifiée
        if (filesize($destinationPath) > $maxSizeInBytes) {
            throw new \Exception('Impossible de réduire la taille de l’image à moins de ' . $maxSize . ' Mo.');
        }

        return $destinationPath;
    }

    public static function resizeAndReduce(string $sourcePath, int $maxWidth, int $maxHeight, float $maxSize): string
    {
        // Étape 1 : Redimensionner l'image
        $resizedPath = Image::resize($sourcePath, $maxWidth, $maxHeight);

        // Étape 2 : Vérifier la taille de l'image redimensionnée
        if (filesize($resizedPath) / (1024 * 1024) > $maxSize) {
            // Réduire la taille si elle dépasse la taille maximale autorisée
            return Image::reduceToMaxSize($resizedPath, $maxSize);
        }

        // Sinon, retourner simplement le chemin redimensionné
        return $resizedPath;
    }



}