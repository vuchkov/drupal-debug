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

use Ekino\Drupal\Debug\ActionMetadata\Model\ActionMetadata;
use Ekino\Drupal\Debug\ActionMetadata\Model\ActionWithOptionsMetadata;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Ekino\Drupal\Debug\Configuration\Model\DefaultsConfiguration as DefaultsConfigurationModel;

class ActionsConfiguration implements ConfigurationInterface
{
    /**
     * @var string
     */
    public const ROOT_KEY = 'actions';

    /**
     * @var ActionMetadata[]
     */
    private $actionsMetadata;

    /**
     * @var DefaultsConfiguration
     */
    private $defaultsConfiguration;

    /**
     * @param ActionMetadata[] $actionsMetadata
     * @param DefaultsConfigurationModel $defaultsConfiguration
     */
    public function __construct(array $actionsMetadata, DefaultsConfigurationModel $defaultsConfiguration)
    {
        $this->actionsMetadata = $actionsMetadata;
        $this->defaultsConfiguration = $defaultsConfiguration;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->root(self::ROOT_KEY);
        $rootNode
            ->addDefaultsIfNotSet()
            ->children();

        foreach ($this->actionsMetadata as $actionMetadata) {
            $sub = $rootNode
                ->arrayNode($actionMetadata->getShortName())
                    ->canBeDisabled()
                    ->children();

            if ($actionMetadata instanceof ActionWithOptionsMetadata) {
                $sub
                    ->append($actionMetadata->getOptionsClass()::getConfiguration($this->defaultsConfiguration));
            }

            $sub
                ->end();
        }

        $rootNode
            ->end();

        return $treeBuilder;
    }
}
