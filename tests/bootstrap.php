<?php

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;

$autoloader = require __DIR__ . '/../vendor/autoload.php';

$kernel = DrupalKernel::createFromRequest(
    Symfony\Component\HttpFoundation\Request::createFromGlobals(),
    $autoloader,
    'prod'
);

$kernel->boot();

$container = $kernel->getContainer();
$container->get('entity_type.manager');