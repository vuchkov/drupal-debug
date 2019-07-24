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

namespace Ekino\Drupal\Debug\Configuration\Model;

use Composer\Autoload\ClassLoader;

class SubstituteOriginalDrupalKernelConfiguration extends AbstractConfiguration
{
    /**
     * @var ClassLoader|null
     */
    private $classLoader;

    /**
     * @param array $processedConfiguration
     */
    public function __construct(array $processedConfiguration)
    {
        parent::__construct($processedConfiguration);

        $this->classLoader = null;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->processedConfiguration['enabled'];
    }

    /**
     * @return ClassLoader
     */
    public function getClassLoader(): ClassLoader
    {
        if (!$this->classLoader instanceof ClassLoader) {
            if (!$this->isEnabled()) {
                throw new \LogicException('The class loader getter should not be called if the original DrupalKernel substitution is disabled.');
            }

            $classLoader = require $this->processedConfiguration['composer_autoload_file_path'];
            if (!$classLoader instanceof ClassLoader) {
                throw new \RuntimeException(\sprintf('The composer autoload.php file did not return a "%s" instance.', ClassLoader::class));
            }

            $this->classLoader = $classLoader;
        }

        return $this->classLoader;
    }

    /**
     * @return string
     */
    public function getCacheDirectoryPath(): string
    {
        if (!$this->isEnabled()) {
            throw new \LogicException('The cache directory getter should not be called if the original DrupalKernel substitution is disabled.');
        }

        return $this->processedConfiguration['cache_directory_path'];
    }

    /**
     * {@inheritdoc}
     */
    public function serialize(): ?string
    {
        return \serialize(array(
            $this->processedConfiguration,
            null,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized): void
    {
        list($this->processedConfiguration, $this->classLoader) = \unserialize($serialized);
    }
}
