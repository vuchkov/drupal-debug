<?php

namespace Ekino\Drupal\Debug\Configuration;

use Ekino\Drupal\Debug\Configuration\Model\ActionConfiguration;
use Monolog\Logger;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Ekino\Drupal\Debug\Configuration\Model\DefaultsConfiguration as DefaultsConfigurationModel;

trait CacheDirectoryPathConfigurationTrait
{
    private static function addCacheDirectoryPathConfigurationNode(NodeBuilder $nodeBuilder, ?string $defaultCacheDirectoryPath): NodeBuilder
    {
        return $nodeBuilder
            ->scalarNode('cache_directory_path')
                ->cannotBeEmpty()
                ->defaultValue($defaultCacheDirectoryPath)
            ->end();
    }

    private static function addCacheDirectoryPathConfigurationNodeFromDefaultsConfiguration(NodeBuilder $nodeBuilder, DefaultsConfigurationModel $defaultsConfiguration): NodeBuilder
    {
        return self::addCacheDirectoryPathConfigurationNode($nodeBuilder, $defaultsConfiguration->getCacheDirectoryPath());
    }

    private static function getConfiguredCacheDirectoryPath(ActionConfiguration $actionConfiguration): ?Logger
    {
        $processedConfiguration = $actionConfiguration->getProcessedConfiguration();

        return $processedConfiguration['cache_directory_path'];
    }
}
