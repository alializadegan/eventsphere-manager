<?php
namespace WPEMCLI;

if ( ! defined('ABSPATH') ) exit;

final class Autoloader {
    public static function init(): void {
        spl_autoload_register([__CLASS__, 'autoload']);
    }

    public static function autoload(string $class): void {
        if (strpos($class, __NAMESPACE__ . '\\') !== 0) return;

        $relative = substr($class, strlen(__NAMESPACE__) + 1);
        $relative = str_replace('\\', DIRECTORY_SEPARATOR, $relative);
        $file = WPEMCLI_PATH . 'includes/' . $relative . '.php';

        if (is_readable($file)) {
            require_once $file;
        }
    }
}