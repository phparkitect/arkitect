<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('vendor/')
    ->notPath('tests/E2E/_fixtures/parse_error/Services/CartService.php');


return (new PhpCsFixer\Config())
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHP71Migration:risky' => true,
        '@PSR2' => true,
        '@DoctrineAnnotation' => true,
        'array_syntax' => ['syntax' => 'short'],
        'fully_qualified_strict_types' => true, // Transforms imported FQCN parameters and return types in function arguments to short version.
        'dir_constant' => true, // Replaces dirname(__FILE__) expression with equivalent __DIR__ constant.
        'heredoc_to_nowdoc' => true,
        'linebreak_after_opening_tag' => true, // Ensure there is no code on the same line as the PHP open tag.
        'blank_line_after_opening_tag' => false,
        'modernize_types_casting' => true, // Replaces intval, floatval, doubleval, strval and boolval function calls with according type casting operator.
        'multiline_whitespace_before_semicolons' => true, // Forbid multi-line whitespace before the closing semicolon or move the semicolon to the new line for chained calls.
        'no_unreachable_default_argument_value' => true, // In function arguments there must not be arguments with default values before non-default ones.
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_class_elements' => true, // Orders the elements of classes/interfaces/traits.
        'ordered_imports' => true,
        'phpdoc_add_missing_param_annotation' => ['only_untyped' => false], // PHPDoc should contain @param for all params (for untyped parameters only).
        'phpdoc_order' => true, // Annotations in PHPDoc should be ordered so that @param annotations come first, then @throws annotations, then @return annotations.
        'declare_strict_types' => true,
        'psr_autoloading' => true, // Class names should match the file name.
        'no_php4_constructor' => true, // Convert PHP4-style constructors to __construct.
        'semicolon_after_instruction' => true,
        'align_multiline_comment' => true,
        'general_phpdoc_annotation_remove' => ['annotations' => ['author', 'package']],
        'list_syntax' => ['syntax' => 'short'],
        'phpdoc_to_comment' => false,
        'php_unit_method_casing' => ['case' => 'snake_case'],
        'function_to_constant' => false
    ]);
