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

namespace Ekino\Drupal\Debug\Action\ThrowErrorsAsExceptions;

use Ekino\Drupal\Debug\Configuration\Model\DefaultsConfiguration;
use Ekino\Drupal\Debug\Option\OptionsConfigurationInterface;
use Ekino\Drupal\Debug\Option\OptionsInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class ThrowErrorsAsExceptionsOptionsConfiguration implements OptionsConfigurationInterface
{
    public static function getConfiguration(DefaultsConfiguration $defaultsConfiguration): ArrayNodeDefinition
    {

    }

    public static function getOptions(string $appRoot, array $processedConfiguration): OptionsInterface
    {
        return new ThrowErrorsAsExceptionsOptions($processedConfiguration['levels'], $processedConfiguration['logger']);
    }
}
