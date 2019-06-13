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

namespace Ekino\Drupal\Debug\Tests\Unit\Kernel;

use Composer\Autoload\ClassLoader;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\OriginalDrupalKernel;
use Ekino\Drupal\Debug\Action\ActionRegistrar;
use Ekino\Drupal\Debug\ActionMetadata\ActionMetadataManager;
use Ekino\Drupal\Debug\Configuration\ConfigurationManager;
use Ekino\Drupal\Debug\Kernel\DebugKernel;
use Ekino\Drupal\Debug\Kernel\Event\AfterAttachSyntheticEvent;
use Ekino\Drupal\Debug\Kernel\Event\AfterContainerInitializationEvent;
use Ekino\Drupal\Debug\Kernel\Event\AfterRequestPreHandleEvent;
use Ekino\Drupal\Debug\Kernel\Event\AfterSettingsInitializationEvent;
use Ekino\Drupal\Debug\Option\OptionsStack;
use Ekino\Drupal\Debug\Tests\Unit\Kernel\test_classes\TestDebugKernel;
use Ekino\Drupal\Debug\Tests\Unit\Kernel\test_classes\TestDebugKernelInstantiation;
use Ekino\Drupal\Debug\Tests\Unit\Kernel\test_classes\TestOriginalDrupalKernel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

class DebugKernelTest extends TestCase
{
    /**
     * @var string
     */
    private const TEST_ORIGINAL_DRUPAL_KERNEL_CLASS_FILE_PATH = __DIR__.'/test_classes/DebugKernelTest_TestOriginalDrupalKernel.php';

    /**
     * @var string
     */
    private const TEST_DEBUG_KERNEL_INSTANTIATION_FILE_PATH = __DIR__.'/test_classes/DebugKernelTest_TestDebugKernelInstantiation.php';

    /**
     * @var string
     */
    private const TEST_DEBUG_KERNEL_FILE_PATH = __DIR__.'/test_classes/DebugKernelTest_TestDebugKernel.php';

    /**
     * @var bool
     */
    protected $runTestInSeparateProcess = true;

    /**
     * @var EventDispatcher|MockObject
     */
    private $eventDispatcher;

    /**
     * @var ActionRegistrar|MockObject
     */
    private $actionRegistrar;

    /**
     * @var TestDebugKernel
     */
    private $debugKernel;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        require self::TEST_ORIGINAL_DRUPAL_KERNEL_CLASS_FILE_PATH;

        \class_alias(TestOriginalDrupalKernel::class, OriginalDrupalKernel::class);

        require self::TEST_DEBUG_KERNEL_INSTANTIATION_FILE_PATH;

        require self::TEST_DEBUG_KERNEL_FILE_PATH;

        TestDebugKernelInstantiation::reset();

        $this->debugKernel = $this->getDebugKernel();
    }

    /**
     * @dataProvider instantiationProvider
     */
    public function testInstantiationSSS(?string $appRoot, ?OptionsStack $optionsStack = null): void
    {
        new TestDebugKernelInstantiation('test', $this->createMock(ClassLoader::class), true, $appRoot, $optionsStack);

        $this->assertEquals(array(
            \is_string($appRoot) ? $appRoot : '/foo',
            ActionMetadataManager::getInstance(),
            ConfigurationManager::getInstance(),
            $optionsStack instanceof OptionsStack ? $optionsStack : OptionsStack::create(),
            'addEventSubscriberActionsToEventDispatcher',
            'dispatch.ekino.drupal.debug.debug_kernel.on_kernel_instantiation',
            'bootEnvironment',
            'dispatch.ekino.drupal.debug.debug_kernel.after_environment_boot',
        ), TestDebugKernelInstantiation::$stack);
    }

    public function instantiationProvider(): array
    {
        return array(
            array(null, null),
            array(null, OptionsStack::create()),
            array('/bar', null),
            array('/bar', OptionsStack::create()),
        );
    }

    public function testBootWhenTheSettingsWereNotInitializedWithTheDedicatedDrupalKernelMethod(): void
    {
        $this->assertAfterSettingsInitialization(array(
            'boot',
        ));

        $this->assertAttributeSame(true, 'booted', $this->debugKernel);
    }

    public function testBoot(): void
    {
        $this->callProtectedMethod('initializeSettings', array($this->createMock(Request::class)));
        $this->callProtectedMethod('boot');

        $this->assertAttributeSame(true, 'booted', $this->debugKernel);
    }

    public function testPreHandle(): void
    {
        $this->setUpEnabledExtensions();

        $this->eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch')
            ->with('ekino.drupal.debug.debug_kernel.after_request_pre_handle', new AfterRequestPreHandleEvent(false, new Container(), array('foo'), array('bar')));

        $this->debugKernel->preHandle($this->createMock(Request::class));
    }

    public function testGetKernelParameters(): void
    {
        $kernelParameters = $this->callProtectedMethod('getKernelParameters');

        $this->assertArrayHasKey('kernel.debug', $kernelParameters);
        $this->assertTrue($kernelParameters['kernel.debug']);
    }

    public function testInitializeContainer(): void
    {
        $this->setUpEnabledExtensions();

        $this->eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch')
            ->with('ekino.drupal.debug.debug_kernel.after_container_initialization', new AfterContainerInitializationEvent(false, new Container(), array('foo'), array('bar')));

        $this->callProtectedMethod('initializeContainer');
    }

    public function testInitializeSettings(): void
    {
        $this->setUpEnabledExtensions();

        $this->assertAfterSettingsInitialization(array(
            'initializeSettings',
            array($this->createMock(Request::class)),
        ));

        $this->assertAttributeSame(true, 'settingsInitialized', $this->debugKernel);

        $this->assertAttributeSame(true, 'settingsWereInitializedWithTheDedicatedDrupalKernelMethod', $this->debugKernel);
    }

    public function testAttachSynthetic(): void
    {
        $this->setUpEnabledExtensions();

        $container = $this->createMock(ContainerInterface::class);

        $this->eventDispatcher
          ->expects($this->atLeastOnce())
          ->method('dispatch')
          ->with('ekino.drupal.debug.debug_kernel.after_attach_synthetic', new AfterAttachSyntheticEvent(false, $container, array('foo'), array('bar')));

        $this->assertSame($container, $this->callProtectedMethod('attachSynthetic', array($container)));
    }

    public function testGetContainerBuilder(): void
    {
        $containerBuilder = new ContainerBuilder();

        $this->actionRegistrar
            ->expects($this->atLeastOnce())
            ->method('addCompilerPassActionsToContainerBuilder')
            ->with($containerBuilder);

        $this->assertEquals($containerBuilder, $this->callProtectedMethod('getContainerBuilder'));
    }

    /**
     * @return TestDebugKernel
     */
    private function getDebugKernel(): TestDebugKernel
    {
        $debugKernel = new TestDebugKernel('test', $this->createMock(ClassLoader::class));

        $propertiesToMock = array(
            'eventDispatcher' => $this->createMock(EventDispatcher::class),
            'actionRegistrar' => $this->createMock(ActionRegistrar::class),
        );

        foreach ($propertiesToMock as $property => $mock) {
            $this->{$property} = $mock;

            $refl = new \ReflectionProperty(DebugKernel::class, $property);
            $refl->setAccessible(true);
            $refl->setValue($debugKernel, $mock);
        }

        return $debugKernel;
    }

    private function setUpEnabledExtensions(): void
    {
        $properties = array(
            'enabledModules' => array('foo'),
            'enabledThemes' => array('bar'),
        );

        foreach ($properties as $property => $value) {
            $refl = new \ReflectionProperty(DebugKernel::class, $property);
            $refl->setAccessible(true);
            $refl->setValue($this->debugKernel, $value);
        }
    }

    /**
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    private function callProtectedMethod(string $method, array $arguments = array())
    {
        $refl = new \ReflectionMethod($this->debugKernel, $method);
        $refl->setAccessible(true);

        return $refl->invokeArgs($this->debugKernel, $arguments);
    }

    /**
     * @param array $arguments
     */
    private function assertAfterSettingsInitialization(array $arguments): void
    {
        $this->eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch')
            ->with('ekino.drupal.debug.debug_kernel.after_settings_initialization', new AfterSettingsInitializationEvent(false, array('fcy'), array('ccc')));

        \call_user_func_array(array($this, 'callProtectedMethod'), $arguments);

        $this->assertAttributeSame(array('fcy'), 'enabledModules', $this->debugKernel);
        $this->assertAttributeSame(array('ccc'), 'enabledThemes', $this->debugKernel);
    }
}
