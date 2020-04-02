<?php
require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;
use Yurun\NetTest\Http\HttpCommand;

$application = new Application();
$application->setName('压测工具');
$application->setVersion('1.0.0');

$application->addCommands([
    new HttpCommand,
]);

$application->run();
