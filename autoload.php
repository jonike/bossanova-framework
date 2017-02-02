<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * PHP version 5
 *
 * @category PHP
 * @package  bossanova-framework
 * @author   Paul Hodel <paul.hodel@gmail.com>
 * @license  The MIT License (MIT)
 * @link     http://www.bossanova-framework.com
 *
 * Bossanova PHP Framework Autoload
 */

if (file_exists('vendor/autoload.php')) {
    include 'vendor/autoload.php';
}

spl_autoload_register('autoloader');

/**
 * Autoloader
 *
 * @param string $class_name Classname
 *
 * @return void
 */
function autoloader($class_name)
{
    $filename = str_replace('\\', '/', $class_name) . '.class.php';

    if (file_exists($filename)) {
        include $filename;
    } else {
        $filename = str_replace('\\', '/', $class_name) . '.class.php';
        if (file_exists($filename)) {
            include $filename;
        } else if (file_exists('vendor/' . $filename)) {
            include 'vendor/' .$filename;
        } else {
            $filename = str_replace('\\', '/', $class_name) . '.php';
            if (file_exists($filename)) {
                include $filename;
            } else if (file_exists('vendor/' . $filename)) {
                include 'vendor/' .$filename;
            }
        }
    }
}