<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests');

return (new PhpCsFixer\Config())
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS2.0' => true,
        '@PER-CS2.0:risky' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHP83Migration' => true,
        'array_syntax' => ['syntax' => 'short'],
        'declare_strict_types' => true,
        'fully_qualified_strict_types' => true,
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],
        'ordered_class_elements' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'php_unit_method_casing' => ['case' => 'snake_case'],
        'php_unit_test_case_static_method_calls' => ['call_type' => 'self'],
        'phpdoc_align' => false,
        'yoda_style' => false,
    ])
    ->setCacheFile(__DIR__.'/.php-cs-fixer.cache');
