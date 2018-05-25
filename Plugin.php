<?php
/*
  Plugin Name: Menu Caching
  Plugin URI: http://crumbls.com
  Description: Very simple menu caching plugin for WordPress.  Written for PHP7.
  Author: Chase C. Miller
  Version: 0.0.1
 */

namespace Crumbls\WordPress\MenuCache;

defined('ABSPATH') || exit(1);

/**
 * Class FrontEnd
 * @package Crumbls\WordPress\MenuCache
 * @description bring front end caching to WordPress menus.
 */
class FrontEnd
{
    protected static $instance = false;

    /**
     * Singleton generator
     * @return FrontEnd
     */
    public static function getInstance()
    {
        if (!isset(static::$instance) || !static::$instance) {
            static::$instance = new FrontEnd();
            static::_init();
        }
        return static::$instance;
    }

    /**
     * Initialzier for static class.
     */
    protected static function _init()
    {
        $s = get_called_class();
        add_filter('pre_wp_nav_menu', $s . '::preNavMenu', -1, 2);
        add_filter('wp_nav_menu', $s . '::navMenu', PHP_INT_MAX, 2);
    }

    /**
     * Store any menu as a transient.
     * @param $menu
     * @param $args
     * @return mixed
     */
    public static function navMenu($menu, $args)
    {
        if (static::isCacheable($args)) {
            set_transient(static::getKey($args), $menu,
                // This filter controls how long to cache a menu for.  Default is 5 minutes.
                apply_filters('menu_cache_expiration', 300, $args)
            );
        }

        return $menu;
    }

    /**
     * If a menu exists in the cache, return it.
     * @param $menu
     * @param $args
     * @return mixed
     */
    public static function preNavMenu($menu, $args)
    {
        if (static::isCacheable($args)) {
            $ret = get_transient(static::getKey($args));
            if ($ret !== false) {
                return $ret;
            }
        }
        return $menu;
    }

    /**
     * Returns a unique key.
     * @return string.
     */
    protected static function getKey(&$args)
    {
        // Set key if needed
        if (
            !property_exists($args, 'menu_key')
            ||
            !$args->menu_key
        ) {
            $args->menu_key = md5(json_encode($args));
        }
        return $args->menu_key;
    }

    /**
     * Checks if a menu should be cached.
     *
     * @param object $args Menu args.
     *
     * @return bool Whether or not the menu should be cached.
     */
    protected static function isCacheable($args)
    {
        // A simple filter to allow a menu to be excluded.
        return (bool)apply_filters('menu_cache_enable', true, $args);
    }
}

// If you are using composer to load this, then remove this line.
FrontEnd::getInstance();
