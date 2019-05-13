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

namespace Ekino\Drupal\Debug\ActionMetadata;

use Ekino\Drupal\Debug\Action\DisableCSSAggregation\DisableCSSAggregationAction;
use Ekino\Drupal\Debug\Action\ThrowErrorsAsExceptions\ThrowErrorsAsExceptionsAction;
use Ekino\Drupal\Debug\Action\ThrowErrorsAsExceptions\ThrowErrorsAsExceptionsOptions;
use Ekino\Drupal\Debug\ActionMetadata\Model\ActionMetadata;
use Ekino\Drupal\Debug\ActionMetadata\Model\ActionWithOptionsMetadata;

class ActionMetadataManager
{
    private const CORE_ACTIONS = [
        'disable_css_aggregation' => [
            ActionMetadata::class,
            DisableCSSAggregationAction::class,
            [],
        ],
        'throw_errors_as_exceptions' => [
            ActionWithOptionsMetadata::class,
            ThrowErrorsAsExceptionsAction::class,
            [
                ThrowErrorsAsExceptionsOptions::class,
            ],
        ],
    ];

    /**
     * @var ActionMetadata[]
     */
    private $actionsMetadata;

    private static $instance;

    private function __construct()
    {
        foreach (self::CORE_ACTIONS as $shortName => list($actionMetadataClass, $actionClass, $args)) {
            $this->add(new $actionMetadataClass(new \ReflectionClass($actionClass), $shortName, ...$args));
        }
    }

    public static function getInstance(): self
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @return ActionMetadata[]
     */
    public function all(): array
    {
        return $this->actionsMetadata;
    }

    public function add(ActionMetadata $actionMetadata): self
    {
        if (isset($this->actionsMetadata[$shortName = $actionMetadata->getShortName()])) {
            throw new \RuntimeException();
        }

        $this->actionsMetadata[$shortName] = $actionMetadata;

        return $this;
    }

    public function isCoreAction(string $shortName): bool
    {
        return isset(self::CORE_ACTIONS[$shortName]);
    }
}
