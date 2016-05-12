<?php

/*
 * Standardized utilities that XenForo Addons use.
 *
 * This file should be almost never updated to permit versioning of installer code to exist side-by-side
 *
 * https://github.com/Xon/XenForo-Utils
 * Original code copyright 2015 Xon
 * Released under the MIT license.
 */

abstract class SV_Utils_Helper
{
    static $latestVersion = null;

    public static function install()
    {
        if (self::$latestVersion !== null)
        {
            return;
        }
        // dynamically find the latest version
        $versions = scandir(__DIR__ . "/Versioning", SCANDIR_SORT_ASCENDING);
        self::$latestVersion = end($versions);

        // register an autoload so we can inject the class as it is requested
        spl_autoload_register(array(__CLASS__, 'autoload'), true, true);
    }

    public static function getVersionedClass($class)
    {
        if (preg_match('/^SV_Utils_(.*)$/', $class, $matches) && strpos($class, 'SV_Utils_Versioning_') !== 0)
        {
            self::install();
            $class = 'SV_Utils_Versioning_'.self::$latestVersion.'_'.$matches[1];
        }
        return $class;
    }

    public static function autoload($class)
    {
		if (class_exists($class, false) || interface_exists($class, false))
		{
			return true;
		}

        if (preg_match('/^SV_Utils_(.*)$/', $class, $matches) && strpos($class, 'SV_Utils_Versioning_') !== 0)
        {
            self::install();
            eval('class SV_Utils_'.$matches[1].' extends SV_Utils_Versioning_'.self::$latestVersion.'_'.$matches[1].' {}');
            return true;
        }
        return false;
    }
}