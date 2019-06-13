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

namespace Ekino\Drupal\Debug\Tests\Unit\Kernel\Event;

use Ekino\Drupal\Debug\Kernel\Event\AbstractWithContainerAndEnabledExtensionsEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AbstractWithContainerAndEnabledExtensionsEventTest extends TestCase
{
    /**
     * @var ContainerInterface|MockObject
     */
    private $container;

    /**
     * @var TestAbstractWithContainerAndEnabledExtensionsEvent
     */
    private $abstractWithContainerAndEnabledExtensionsEvent;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);

        $this->abstractWithContainerAndEnabledExtensionsEvent = new TestAbstractWithContainerAndEnabledExtensionsEvent(false, $this->container, array(), array());
    }

    public function testGetContainer(): void
    {
        $this->assertSame($this->container, $this->abstractWithContainerAndEnabledExtensionsEvent->getContainer());
    }
}

class TestAbstractWithContainerAndEnabledExtensionsEvent extends AbstractWithContainerAndEnabledExtensionsEvent
{
}
