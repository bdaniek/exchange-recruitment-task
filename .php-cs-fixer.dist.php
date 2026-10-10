<?php

declare(strict_types=1);

$finder = new PhpCsFixer\Finder()
    ->in(__DIR__)
    ->exclude('var')
    ->notPath([
        'config/bundles.php',
        'config/reference.php',
    ]);

return new PhpCsFixer\Config()
    ->setRiskyAllowed(true)
    ->setUsingCache(true)
    ->setRules([
        '@Symfony' => true,
        'declare_strict_types' => true,
        'global_namespace_import' => ['import_classes' => true, 'import_constants' => true, 'import_functions' => true],
        'php_unit_method_casing' => true,
    ])
    ->setFinder($finder);
