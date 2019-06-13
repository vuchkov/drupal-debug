<?php

namespace Ekino\Drupal\Debug\Tests\Unit\Action\test_classes;

use Ekino\Drupal\Debug\Action\CompilerPassActionInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class TestCompilerPassAction implements CompilerPassActionInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
    }
}
