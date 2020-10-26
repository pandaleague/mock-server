#!/usr/bin/env php
<?php

$cwd = getcwd();
if (is_dir($cwd . '/vendor')) {
    echo "Using project autoloader based on current working directory\n";
    chdir($cwd);
    require 'vendor/autoload.php';
} elseif (file_exists($a = __DIR__ . '/../../../autoload.php')) {
    echo "Using project autoloader\n";
    require $a;
} elseif (file_exists($a = __DIR__ . '/../vendor/autoload.php')) {
    echo "Using project autoloader relative to bin directory\n";
    require $a;
} elseif (file_exists($a = __DIR__ . '/../autoload.php')) {
    echo "Using project autoloader relative to vendor directory\n";
    require $a;
} else {
    fwrite(STDERR, 'Cannot locate autoloader; please run "composer install"' . PHP_EOL);
    exit(1);
}

use PandaLeague\MockServer\Console\Command\StartCommand;
use Symfony\Component\Console\Application;

$application = new Application();

$application->add(new StartCommand());

$application->run();