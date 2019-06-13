<?php

namespace Ekino\Drupal\Debug\Tests\Unit\Action\test_classes;

use Ekino\Drupal\Debug\Action\EventSubscriberActionInterface;

final class TestEventSubscriberAction implements EventSubscriberActionInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
    }
}
