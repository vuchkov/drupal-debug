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

namespace Ekino\Drupal\Debug\Kernel\Event;

abstract class AbstractWithEnabledExtensionsEvent extends AbstractBaseEvent
{
    /**
     * @var array
     */
    private $enabledModules;

    /**
     * @var array
     */
    private $enabledThemes;

    /**
     * @param array $enabledModules
     * @param array $enabledThemes
     */
    public function __construct(bool $configurationChanged, array $enabledModules, array $enabledThemes)
    {
        parent::__construct($configurationChanged);

        $this->enabledModules = $enabledModules;
        $this->enabledThemes = $enabledThemes;
    }

    /**
     * @return array
     */
    public function getEnabledModules(): array
    {
        return $this->enabledModules;
    }

    /**
     * @return array
     */
    public function getEnabledThemes(): array
    {
        return $this->enabledThemes;
    }
}
