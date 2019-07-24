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

namespace Ekino\Drupal\Debug\Tests\Unit\Option;

use Ekino\Drupal\Debug\Configuration\Model\ActionConfiguration;
use Ekino\Drupal\Debug\Configuration\Model\DefaultsConfiguration;
use Ekino\Drupal\Debug\Option\OptionsInterface;
use Ekino\Drupal\Debug\Option\OptionsStack;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

class OptionsStackTest extends TestCase
{
    /**
     * @var TestOptions
     */
    private $options;

    /**
     * @var OptionsStack
     */
    private $optionsStack;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->options = new TestOptions();
        $this->optionsStack = OptionsStack::create();
    }

    public function testCreateWithoutOptions(): void
    {
        $this->assertAttributeSame(array(), 'optionsStack', $this->optionsStack);
    }

    public function testCreateWithOptions(): void
    {
        $optionsStack = OptionsStack::create(array(
            $this->options,
        ));

        $this->assertAttributeSame(array(
            TestOptions::class => $this->options,
        ), 'optionsStack', $optionsStack);
    }

    public function testGetWithAnUnknownClass(): void
    {
        $this->assertNull($this->optionsStack->get('A\Foo\Options'));
    }

    public function testGetWithAKnownClass(): void
    {
        $optionsStack = OptionsStack::create(array(
            $this->options,
            $this->createMock(OptionsInterface::class),
        ));

        $this->assertSame($this->options, $optionsStack->get(TestOptions::class));
    }

    public function testSet(): void
    {
        $this->optionsStack->set($this->options);

        $this->assertAttributeSame(array(
          TestOptions::class => $this->options,
        ), 'optionsStack', $this->optionsStack);
    }

    public function testSetWithTheSameOptionsClassTwice(): void
    {
        $this->optionsStack->set($this->options);

        $otherOptionsOfSameClass = new TestOptions();
        $this->optionsStack->set($otherOptionsOfSameClass);

        $this->assertAttributeSame(array(
            \get_class($otherOptionsOfSameClass) => $otherOptionsOfSameClass,
        ), 'optionsStack', $this->optionsStack);
    }
}

class TestOptions implements OptionsInterface
{
    /**
     * {@inheritdoc}
     */
    public static function addConfiguration(NodeBuilder $nodeBuilder, DefaultsConfiguration $defaultsConfiguration): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public static function getOptions(string $appRoot, ActionConfiguration $actionConfiguration): OptionsInterface
    {
    }
}
