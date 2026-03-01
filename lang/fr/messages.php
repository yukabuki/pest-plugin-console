<?php

declare(strict_types=1);

/**
 * Traductions françaises pour pest-plugin-console.
 *
 * Pour utiliser vos propres traductions, créez un fichier similaire et enregistrez-le :
 *
 *   TranslationManager::instance()->addResourcePath('/votre/lang/fr/messages.php', 'fr');
 */
return [
    // Titres de section
    'section.tests'    => 'TESTS',
    'section.fail'     => 'ÉCHEC',
    'section.report'   => 'RAPPORT',

    // Badges affichés à côté de chaque classe de test
    'badge.pass'       => 'PASS',
    'badge.fail'       => 'FAIL',
    'badge.warn'       => 'ATTN',

    // En-têtes du tableau récapitulatif
    'table.passed'     => 'Réussis',
    'table.failed'     => 'Échoués',
    'table.skipped'    => 'Ignorés',
    'table.total'      => 'Total',
    'table.assertions' => 'Assertions',
    'table.duration'   => 'Durée',
    'table.avg'        => 'Moy.',
];
