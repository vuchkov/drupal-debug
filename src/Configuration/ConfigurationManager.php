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

use Ekino\Drupal\Debug\ActionMetadata\ActionMetadataFactory;
use Ekino\Drupal\Debug\ActionMetadata\ActionMetadataManager;
use Ekino\Drupal\Debug\Cache\FileCache;
use Ekino\Drupal\Debug\Configuration\Model\ActionConfiguration;
use Ekino\Drupal\Debug\Configuration\Model\DefaultsConfiguration as DefaultsConfigurationModel;
use Ekino\Drupal\Debug\Configuration\Model\SubstituteOriginalDrupalKernelConfiguration as SubstituteOriginalDrupalKernelConfigurationModel;
use Ekino\Drupal\Debug\Resource\Model\ResourcesCollection;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Resource\FileExistenceResource;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Yaml\Parser;

class ConfigurationManager
{
    /**
     * @var string
     */
    public const CONFIGURATION_FILE_PATH_ENVIRONMENT_VARIABLE_NAME = 'DRUPAL_DEBUG_CONFIGURATION_FILE_PATH';

    /**
     * @var string
     */
    public const CONFIGURATION_CACHE_DIRECTORY_ENVIRONMENT_VARIABLE_NAME = 'DRUPAL_DEBUG_CONFIGURATION_CACHE_DIRECTORY_PATH';

    /**
     * @var string
     */
    private const DEFAULT_CONFIGURATION_FILE_NAME = 'drupal-debug.yml.dist';

    /**
     * @var string
     */
    private const ROOT_KEY = 'drupal-debug';

    /**
     * @var self|null
     */
    private static $instance = null;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @var string
     */
    private $configurationFilePath;

    /**
     * @var bool
     */
    private $configurationFilePathExists;

    /**
     * @var string
     */
    private $configurationFilePathDirectory;

    /**
     * @var DefaultsConfigurationModel
     */
    private $defaultsConfiguration;

    /**
     * @var SubstituteOriginalDrupalKernelConfigurationModel
     */
    private $substituteOriginalDrupalKernelConfiguration;

    /**
     * @var ActionConfiguration[]
     */
    private $actionsConfigurations;

    private function __construct()
    {
        $configurationCacheDirectory = \getenv(self::CONFIGURATION_CACHE_DIRECTORY_ENVIRONMENT_VARIABLE_NAME);
        if (false === $configurationCacheDirectory) {
            $configurationCacheDirectory = \sys_get_temp_dir();
        }

        $this->filesystem = new Filesystem();

        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();

        $this->setConfigurationFilePathInfo();

        $fileCache = new FileCache(\sprintf('%s/drupal_debug_configuration.php', $configurationCacheDirectory), new ResourcesCollection(array(
            $this->configurationFilePathExists ? new FileResource($this->configurationFilePath) : new FileExistenceResource($this->configurationFilePath),
            new FileResource(\sprintf('%s/Configuration.php', __DIR__)),
        )));
        if ($fileCache->isFresh() && !empty($data = $fileCache->getData())) {
            list(
                'defaults' => $this->defaultsConfiguration,
                'substitute_original_drupal_kernel' => $this->substituteOriginalDrupalKernelConfiguration,
                'actions' => $this->actionsConfigurations,
            ) = \array_map(function ($serializedConfiguration) {
                return \unserialize($serializedConfiguration);
            }, $data);
        } else {
            $configurationFileContent = $this->getConfigurationFileContent();

            $this->setDefaultsConfiguration($configurationFileContent);
            $this->setSubstituteOriginalDrupalKernelConfiguration($configurationFileContent);
            $this->setActionsConfigurations($configurationFileContent, $this->getDefaultsConfiguration());

            $fileCache->invalidate();
            $fileCache->write(array(
                'defaults' => \serialize($this->defaultsConfiguration),
                'substitute_original_drupal_kernel' => \serialize($this->substituteOriginalDrupalKernelConfiguration),
                'actions' => \serialize($this->actionsConfigurations),
            ));
        }
    }

    public static function get(): self
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getDefaultsConfiguration(): DefaultsConfigurationModel
    {
        return $this->defaultsConfiguration;
    }

    private function setDefaultsConfiguration(array $configurationFileContent): void
    {
        $this->defaultsConfiguration = new DefaultsConfigurationModel(
            $this->makeRelativePathsAbsolutes(
                $this->getProcessedDefaultsConfiguration($configurationFileContent),
                array_map(function (array $elements) {
                    return sprintf('[%s][%s]', DefaultsConfiguration::ROOT_KEY, implode('][', $elements));
                }, [
                    [
                        'cache_directory_path',
                    ],
                    [
                        'logger',
                        'file_path',
                    ],
                ])
            )
        );
    }

    public function getSubstituteOriginalDrupalKernelConfiguration(): SubstituteOriginalDrupalKernelConfigurationModel
    {
        return $this->substituteOriginalDrupalKernelConfiguration;
    }

    private function setSubstituteOriginalDrupalKernelConfiguration(array $configurationFileContent): void
    {
        $this->substituteOriginalDrupalKernelConfiguration = new SubstituteOriginalDrupalKernelConfigurationModel(
            $this->makeRelativePathsAbsolutes(
                $this->getProcessedSubstituteOriginalDrupalKernelConfiguration($configurationFileContent),
                array_map(function (array $elements) {
                    return sprintf('[%s][%s]', SubstituteOriginalDrupalKernelConfiguration::ROOT_KEY, implode('][', $elements));
                }, [
                    [
                        'composer_autoload_file_path',
                    ],
                    [
                        'cache_directory_path',
                    ],
                ])
            )
        );
    }

    public function getActionConfiguration(string $class): ActionConfiguration
    {
        return $this->actionsConfigurations[$class];
    }

    private function setActionsConfigurations(array $configurationFileContent, DefaultsConfigurationModel $defaultsConfiguration): void
    {
        $this->actionsConfigurations = array();

        $actionMetadataManager = ActionMetadataManager::getInstance();
        $actionMetadataFactory = new ActionMetadataFactory();

        foreach ($configurationFileContent[ActionsConfiguration::ROOT_KEY] ?? [] as $shortName => $config) {
            $actionMetadataManager->add($actionMetadataFactory->create($shortName));
        }

        $processedActionsConfiguration = $this->getProcessedActionsConfiguration($configurationFileContent, $actionMetadataManager->all(), $defaultsConfiguration);
        $propertyPaths = [];

        $buildPropertyPathsRecursively = function (array $array, array $previous = []) use (&$buildPropertyPathsRecursively, &$propertyPaths): void {
            foreach ($array as $key => $row) {
                if (is_string($row) && 1 === preg_match('/_path$/i', $row)) {
                    $propertyPaths[] = sprintf('[%s][%s]', ActionsConfiguration::ROOT_KEY, implode('][', $previous));
                } elseif (is_array($row)) {
                    $buildPropertyPathsRecursively($row, $previous[] = $key);
                }
            }
        };

        foreach ($processedActionsConfiguration[ActionsConfiguration::ROOT_KEY] as $shortName => $processedActionConfiguration) {
            if ($actionMetadataManager->isCoreAction($shortName)) {
                continue;
            }

            $buildPropertyPathsRecursively($processedActionsConfiguration);
        }

        foreach (
            $this->makeRelativePathsAbsolutes(
                $processedActionsConfiguration,
                $propertyPaths
            ) as $shortName => $processedActionConfiguration
        ) {
            $this->actionsConfigurations[$shortName] = new ActionConfiguration($processedActionConfiguration);
        }
    }

    private function setConfigurationFilePathInfo(): void
    {
        $possibleConfigurationFilePath = \getenv(self::CONFIGURATION_FILE_PATH_ENVIRONMENT_VARIABLE_NAME);
        if (false === $possibleConfigurationFilePath) {
            // The default configuration file location is the same than the vendor directory.
            $possibleAutoloadPaths = array(
                // Vendor of a project : Configuration\src\drupal-debug\ekino\autoload.php
                \sprintf('%s/../../../../autoload.php', __DIR__),
                // Directly this project : Configuration\src\/vendor/autoload.php
                \sprintf('%s/../../vendor/autoload.php', __DIR__),
                // For other cases (if they exist), please use the dedicated environment variable.
            );

            foreach ($possibleAutoloadPaths as $possibleAutoloadPath) {
                if (\is_file($possibleAutoloadPath)) {
                    $possibleConfigurationFilePath = \sprintf('%s/../%s', \dirname($possibleAutoloadPath), self::DEFAULT_CONFIGURATION_FILE_NAME);

                    break;
                }
            }

            if (false === $possibleConfigurationFilePath) {
                throw new \RuntimeException('The composer autoload.php file could not be found.');
            }
        }

        $possibleConfigurationFilePaths = array(
            $possibleConfigurationFilePath,
            \rtrim($possibleConfigurationFilePath, '.dist'),
        );

        $exists = false;
        foreach ($possibleConfigurationFilePaths as $possibleConfigurationFilePath) {
            if (\is_file($possibleConfigurationFilePath)) {
                $exists = true;

                break;
            }
        }

        $this->configurationFilePath = $possibleConfigurationFilePath;
        $this->configurationFilePathExists = $exists;
        $this->configurationFilePathDirectory = \dirname($this->configurationFilePath);
    }

    private function getConfigurationFileContent(): array
    {
        if (!$this->configurationFilePathExists) {
            return array();
        }

        $parser = new Parser();
        $content = $parser->parseFile($this->configurationFilePath);
        if (!\is_array($content)) {
            throw new InvalidConfigurationException('The content of the drupal-debug configuration file should be an array.');
        }

        return $content;
    }

    private function getProcessedDefaultsConfiguration(array $configurationFileContent): array
    {
        return $this->getProcessedConfiguration(
            $configurationFileContent,
            new DefaultsConfiguration()
        );
    }

    private function getProcessedSubstituteOriginalDrupalKernelConfiguration(array $configurationFileContent): array
    {
        return $this->getProcessedConfiguration(
            $configurationFileContent,
            new SubstituteOriginalDrupalKernelConfiguration()
        );
    }

    private function getProcessedActionsConfiguration(array $configurationFileContent, array $actionMetadata, DefaultsConfigurationModel $defaultsConfiguration): array
    {
        return $this->getProcessedConfiguration(
            $configurationFileContent,
            new ActionsConfiguration($actionMetadata, $defaultsConfiguration)
        );
    }

    private function getProcessedConfiguration(array $configurationFileContent, ConfigurationInterface $configuration): array
    {
        return (new Processor())->process(
            $configuration
                ->getConfigTreeBuilder()
                ->buildTree(),
            $configurationFileContent
        );
    }

    private function makeRelativePathsAbsolutes(array $processedConfiguration, array $propertyPaths): array
    {
        foreach ($propertyPaths as $propertyPath) {
            if (!$this->propertyAccessor->isReadable($processedConfiguration, $propertyPath)) {
                continue;
            }

            $path = $this->propertyAccessor->getValue($processedConfiguration, $propertyPath);
            if (null === $path || '' === $path) {
                continue;
            }

            if (!$this->filesystem->isAbsolutePath($path)) {
                $this->propertyAccessor->setValue($processedConfiguration, $propertyPath, \sprintf('%s/%s', $this->configurationFilePathDirectory, $path));
            }
        }

        return $processedConfiguration;
    }
}
