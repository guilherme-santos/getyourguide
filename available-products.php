#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;
use GetYourGuide\Command\AvailableProductsCommand;

$command = new AvailableProductsCommand();

$app = new Application('AvailableProducts Console Application', '1.0.0');
$app->add($command);
$app->setDefaultCommand($command->getName(), true);
$app->run();
