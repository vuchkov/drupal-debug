<?php

namespace Ekino\Drupal\Debug\Logger;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

final class LoggerStack
{
    /**
     * @var Logger[]
     */
    private static $instances = [];

    public static function get(string $channel, string $filePath): Logger
    {
        if (!isset(self::$instances[$key = $channel.$filePath])) {
            self::$instances[$key] = new Logger($channel, array(
                new StreamHandler($filePath),
            ));
        }

        return self::$instances[$key];
    }
}
