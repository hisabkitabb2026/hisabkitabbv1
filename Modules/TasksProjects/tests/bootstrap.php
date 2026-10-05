<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

if (! function_exists('module_path')) {
    function module_path(string $name, string $path = ''): string
    {
        return dirname(__DIR__).($path === '' ? '' : '/'.$path);
    }
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'Modules\\TasksProjects\\';
    if (str_starts_with($class, $prefix)) {
        require __DIR__.'/../app/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
    }
});
