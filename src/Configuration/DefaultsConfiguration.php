<?php

declare(strict_types=1);

/*
 * This file is part of the ekino Drupal Debug project.
 *
 * (c) ekino
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ekino\Drupal\Debug\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class DefaultsConfiguration implements ConfigurationInterface
{
    /**
     * @var string
     */
    public const ROOT_KEY = 'defaults';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->root(self::ROOT_KEY);

        $rootNode
            ->info('The defaults values are common values that are reused by different actions.')
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('cache_directory_path')
                    ->cannotBeEmpty()
                    ->defaultValue('cache')
                ->end()
                ->arrayNode('logger')
                    ->canBeDisabled()
                    ->children()
                        ->scalarNode('channel')
                            ->cannotBeEmpty()
                            ->defaultValue('drupal-debug')
                        ->end()
                        ->scalarNode('file_path')
                            ->cannotBeEmpty()
                            ->defaultValue('logs/drupal-debug.log')
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('charset')
                    ->defaultNull()
                ->end()
                ->scalarNode('file_link_format')
                    ->defaultNull()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
