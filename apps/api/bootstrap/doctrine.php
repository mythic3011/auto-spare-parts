<?php

declare(strict_types=1);

use App\Api\Infrastructure\Doctrine\EntityManagerFactory;
use Doctrine\ORM\EntityManagerInterface;

$config = require __DIR__ . '/../config/doctrine.php';

/** @var EntityManagerInterface $entityManager */
$entityManager = EntityManagerFactory::create($config);

return $entityManager;
