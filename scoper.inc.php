<?php

declare(strict_types=1);

// Polyfill stubs (Resources/stubs/*.php) declare classes like \Normalizer,
// \JsonException, \Attribute in the global namespace and bootstrap files
// (bootstrap*.php) declare global functions like \normalizer_normalize().
// Both have no `namespace` declaration, so php-scoper would otherwise add
// one. They must remain global because callers like
// Symfony\Component\String\AbstractUnicodeString reference them as
// \Normalizer / \normalizer_normalize() at runtime. exclude-namespaces
// does not catch these files since they declare no namespace, so list
// them explicitly.
$polyfillGlobalFiles = array_map(
    static fn (SplFileInfo $file): string => $file->getPathname(),
    iterator_to_array(
        new RegexIterator(
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(__DIR__.'/vendor/symfony', RecursiveDirectoryIterator::SKIP_DOTS),
            ),
            '#vendor/symfony/polyfill-[^/]+/(Resources/stubs/.+|bootstrap[^/]*)\.php$#',
        ),
        false,
    ),
);

return [
    'prefix' => '_PhpArkitect',

    'expose-global-constants' => true,
    'expose-global-classes' => true,
    'expose-global-functions' => true,

    'exclude-files' => $polyfillGlobalFiles,

    'exclude-namespaces' => [
        'Arkitect',
        'Composer',
        // The polyfill packages register their global functions
        // (normalizer_normalize, ...) via composer's files autoloader.
        // Scoping them moves the functions into _PhpArkitect\, which
        // breaks callers that invoke them through the global namespace.
        'Symfony\\Polyfill',
    ],
];
