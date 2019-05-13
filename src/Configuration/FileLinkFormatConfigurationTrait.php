<?php

namespace Ekino\Drupal\Debug\Configuration;

use Ekino\Drupal\Debug\Configuration\Model\ActionConfiguration;
use Monolog\Logger;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Ekino\Drupal\Debug\Configuration\Model\DefaultsConfiguration as DefaultsConfigurationModel;

trait FileLinkFormatConfigurationTrait
{
    private static function addFileLinkFormatConfigurationNode(NodeBuilder $nodeBuilder, ?string $defaultFileLinkFormat): NodeBuilder
    {
        return $nodeBuilder
            ->scalarNode('file_link_format')
                ->defaultValue($defaultFileLinkFormat)
            ->end();
    }

    private static function addFileLinkFormatConfigurationNodeFromDefaultsConfiguration(NodeBuilder $nodeBuilder, DefaultsConfigurationModel $defaultsConfiguration): NodeBuilder
    {
        return self::addFileLinkFormatConfigurationNode($nodeBuilder, $defaultsConfiguration->getFileLinkFormat());
    }

    private static function getConfiguredFileLinkFormat(ActionConfiguration $actionConfiguration): ?Logger
    {
        $processedConfiguration = $actionConfiguration->getProcessedConfiguration();

        return $processedConfiguration['file_link_format'];
    }
}
