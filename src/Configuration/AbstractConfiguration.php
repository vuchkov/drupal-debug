<?php

namespace Ekino\Drupal\Debug\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

abstract class AbstractConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public final function getConfigTreeBuilder(): TreeBuilder
    {
        $this->getArrayNodeDefinition($treeBuilder = new TreeBuilder());

        return $treeBuilder;
    }

    abstract public function getArrayNodeDefinition(TreeBuilder $treeBuilder);
}
