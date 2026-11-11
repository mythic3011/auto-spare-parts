<?php

declare(strict_types=1);

use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;

require_once __DIR__ . '/../../vendor/autoload.php';

return DependencyFactory::fromEntityManager(
    new ConfigurationArray(require __DIR__ . '/migrations.php'),
    new ExistingEntityManager(require __DIR__ . '/bootstrap/doctrine.php'),
);
