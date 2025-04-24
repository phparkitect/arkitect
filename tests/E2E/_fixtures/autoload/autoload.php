<?php
declare(strict_types=1);

spl_autoload_register(function ($class) {
    $classmap = [
        'Autoload\Services\UserService' => __DIR__.'/src/Service/UserService.php',
        'Autoload\Model\User' => __DIR__.'/src/Model/User.php',
        'Autoload\Model\UserInterface' => __DIR__.'/src/Model/UserInterface.php',
    ];

    $path = $classmap[$class] ?? null;

    if (null === $path) {
        return;
    }

    if (!file_exists($path)) {
        return;
    }

    return require $path;
});
