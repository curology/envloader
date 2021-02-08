<?php

require __DIR__.'/vendor/autoload.php';

return (new PhpCsFixer\Config())
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__)
            ->exclude('vendor')
    )
    ->setRules([
        '@PhpCsFixer' => true,
        'php_unit_test_class_requires_covers' => false,
        'declare_strict_types' => true,
    ])
    ->setRiskyAllowed(true);
