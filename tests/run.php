<?php

file_put_contents(__DIR__ . '/date.log', date('Y-m-d H:i') . "\n");

// Scan tous les fichiers *Test.php
foreach (glob(__DIR__ . '/*Test.php') as $file) {
    echo "\n================================\n";
    echo "Exécution de : $file\n";

    // Exécute chaque fichier comme un processus PHP indépendant
    passthru("php $file");

    // Une séparation pour différencier les résultats des tests
    echo "\n================================\n";
}
//  php run.php | grep -E 'fails|Exécution' ;  php run.php | grep -E 'fails' | grep -v "0 fails" > test_results.log