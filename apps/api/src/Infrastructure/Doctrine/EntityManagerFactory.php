<?php

declare(strict_types=1);

namespace App\Api\Infrastructure\Doctrine;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Proxy\ProxyFactory;
use RuntimeException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;

final class EntityManagerFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public static function create(array $config): EntityManagerInterface
    {
        $entityPaths = $config['entity_paths'] ?? [__DIR__ . '/../../Domain'];
        $isDevMode = (bool) ($config['is_dev_mode'] ?? false);

        $metadataConfig = ORMSetup::createAttributeMetadataConfiguration(
            paths: $entityPaths,
            isDevMode: $isDevMode,
            proxyDir: $config['proxy_dir'] ?? null,
            cache: self::metadataCache($config, $isDevMode),
        );

        // During coexistence deployments, generate each missing proxy once.
        $metadataConfig->setAutoGenerateProxyClasses(ProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);

        $connection = DriverManager::getConnection($config['connection'] ?? [], $metadataConfig);

        return new EntityManager($connection, $metadataConfig);
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function metadataCache(array $config, bool $isDevMode): ArrayAdapter|PhpFilesAdapter
    {
        $cacheDir = $config['cache_dir'] ?? null;
        if ($isDevMode || !is_string($cacheDir) || $cacheDir === '') {
            return new ArrayAdapter();
        }

        if (!is_dir($cacheDir) && !mkdir($cacheDir, 0775, true) && !is_dir($cacheDir)) {
            throw new RuntimeException('Unable to create Doctrine cache directory');
        }

        return new PhpFilesAdapter('doctrine_metadata', 0, $cacheDir);
    }
}
