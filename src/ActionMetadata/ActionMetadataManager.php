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
use Ekino\Drupal\Debug\Action\DisableDynamicPageCache\DisableDynamicPageCacheAction;
use Ekino\Drupal\Debug\Action\DisableInternalPageCache\DisableInternalPageCacheAction;
use Ekino\Drupal\Debug\Action\DisableJSAggregation\DisableJSAggregationAction;
use Ekino\Drupal\Debug\Action\DisableRenderCache\DisableRenderCacheAction;
use Ekino\Drupal\Debug\Action\DisableTwigCache\DisableTwigCacheAction;
use Ekino\Drupal\Debug\Action\DisplayDumpLocation\DisplayDumpLocationAction;
use Ekino\Drupal\Debug\Action\DisplayPrettyExceptions\DisplayPrettyExceptionsAction;
use Ekino\Drupal\Debug\Action\DisplayPrettyExceptions\DisplayPrettyExceptionsOptions;
use Ekino\Drupal\Debug\Action\DisplayPrettyExceptionsASAP\DisplayPrettyExceptionsASAPAction;
use Ekino\Drupal\Debug\Action\DisplayPrettyExceptionsASAP\DisplayPrettyExceptionsASAPOptions;
use Ekino\Drupal\Debug\Action\EnableDebugClassLoader\EnableDebugClassLoaderAction;
use Ekino\Drupal\Debug\Action\EnableTwigDebug\EnableTwigDebugAction;
use Ekino\Drupal\Debug\Action\EnableTwigStrictVariables\EnableTwigStrictVariablesAction;
use Ekino\Drupal\Debug\Action\ThrowErrorsAsExceptions\ThrowErrorsAsExceptionsAction;
use Ekino\Drupal\Debug\Action\ThrowErrorsAsExceptions\ThrowErrorsAsExceptionsOptions;
use Ekino\Drupal\Debug\Action\WatchContainerDefinitions\WatchContainerDefinitionsAction;
use Ekino\Drupal\Debug\Action\WatchContainerDefinitions\WatchContainerDefinitionsOptions;
use Ekino\Drupal\Debug\Action\WatchModulesHooksImplementations\WatchModulesHooksImplementationsAction;
use Ekino\Drupal\Debug\Action\WatchModulesHooksImplementations\WatchModulesHooksImplementationsOptions;
use Ekino\Drupal\Debug\Action\WatchRoutingDefinitions\WatchRoutingDefinitionsAction;
use Ekino\Drupal\Debug\Action\WatchRoutingDefinitions\WatchRoutingDefinitionsOptions;
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
        'disable_dynamic_page_cache' => [
            ActionMetadata::class,
            DisableDynamicPageCacheAction::class,
            [],
        ],
        'disable_internal_page_cache' => [
            ActionMetadata::class,
            DisableInternalPageCacheAction::class,
            [],
        ],
        'disable_js_aggregation' => [
            ActionMetadata::class,
            DisableJSAggregationAction::class,
            [],
        ],
        'disable_render_cache' => [
            ActionMetadata::class,
            DisableRenderCacheAction::class,
            [],
        ],
        'disable_twig_cache' => [
            ActionMetadata::class,
            DisableTwigCacheAction::class,
            [],
        ],
        'display_dump_location' => [
            ActionMetadata::class,
            DisplayDumpLocationAction::class,
            [],
        ],
        'display_pretty_exceptions' => [
            ActionWithOptionsMetadata::class,
            DisplayPrettyExceptionsAction::class,
            [
                DisplayPrettyExceptionsOptions::class,
            ]
        ],
        'display_pretty_exceptions_asap' => [
            ActionWithOptionsMetadata::class,
            DisplayPrettyExceptionsASAPAction::class,
            [
                DisplayPrettyExceptionsASAPOptions::class,
            ]
        ],
        'enable_debug_class_loader' => [
            ActionMetadata::class,
            EnableDebugClassLoaderAction::class,
            [],
        ],
        'enable_twig_debug' => [
            ActionMetadata::class,
            EnableTwigDebugAction::class,
            []
        ],
        'enable_twig_strict_variables' => [
            ActionMetadata::class,
            EnableTwigStrictVariablesAction::class,
            []
        ],
        'throw_errors_as_exceptions' => [
            ActionWithOptionsMetadata::class,
            ThrowErrorsAsExceptionsAction::class,
            [
                ThrowErrorsAsExceptionsOptions::class,
            ],
        ],
        'watch_container_definitions' => [
            ActionWithOptionsMetadata::class,
            WatchContainerDefinitionsAction::class,
            [
                WatchContainerDefinitionsOptions::class,
            ]
        ],
        'watch_modules_hooks_implementations' => [
            ActionWithOptionsMetadata::class,
            WatchModulesHooksImplementationsAction::class,
            [
                WatchModulesHooksImplementationsOptions::class,
            ]
        ],
        'watch_routing_definitions' => [
            ActionWithOptionsMetadata::class,
            WatchRoutingDefinitionsAction::class,
            [
                WatchRoutingDefinitionsOptions::class,
            ]
        ],
    ];

    /**
     * @var ActionMetadata[]
     */
    private $actionsMetadata;

    private static $instance;

    /**
     * @internal
     */
    public function __construct()
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
