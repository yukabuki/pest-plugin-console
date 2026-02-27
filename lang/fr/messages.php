<?php

declare(strict_types=1);

/**
 * Traductions françaises pour pest-plugin-console.
 *
 * Pour utiliser vos propres traductions, créez un fichier similaire et enregistrez-le :
 *
 *   $manager->addResourcePath('/votre/lang/fr/messages.php', 'fr');
 */
return [
    // Général
    'tests.passed'  => 'Tests réussis !',
    'tests.failed'  => 'Des tests ont échoué.',
    'tests.summary' => ':passed réussi(s), :failed échoué(s), :skipped ignoré(s) (:total au total)',

    // Infos d'exécution
    'run.start'    => 'Lancement de la suite de tests...',
    'run.duration' => 'Terminé en :time secondes.',
];
