<?php

namespace Ekino\Drupal\Debug\Configuration;

use Ekino\Drupal\Debug\Configuration\Model\ActionConfiguration;
use Monolog\Logger;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Ekino\Drupal\Debug\Configuration\Model\DefaultsConfiguration as DefaultsConfigurationModel;

trait CharsetConfigurationTrait
{
    private static function addCharsetConfigurationNode(NodeBuilder $nodeBuilder, ?string $defaultCharset): NodeBuilder
    {
        return $nodeBuilder
            ->scalarNode('charset')
                ->defaultValue($defaultCharset)
            ->end();
    }

    private static function addCharsetConfigurationNodeFromDefaultsConfiguration(NodeBuilder $nodeBuilder, DefaultsConfigurationModel $defaultsConfiguration): NodeBuilder
    {
        return self::addCharsetConfigurationNode($nodeBuilder, $defaultsConfiguration->getCharset());
    }

    private static function getConfiguredCharset(ActionConfiguration $actionConfiguration): ?Logger
    {
        $processedConfiguration = $actionConfiguration->getProcessedConfiguration();

        return $processedConfiguration['charset'];
    }
}
