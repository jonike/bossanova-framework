<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Autoload
 */

if (file_exists('vendor/autoload.php')) {
    include 'vendor/autoload.php';
}

spl_autoload_register('autoloader');

function autoloader($class_name)
{
    $filename = str_replace('\\', '/', $class_name) . '.class.php';

    if (file_exists($filename)) {
        require $filename;
    } else {
        $filename = str_replace('Bossanova/', 'vendor/bossanova/', $filename);
        if (file_exists($filename)) {
            require $filename;
        } else {
            $class_name = 'vendor\\' . $class_name;
            $filename = str_replace('\\', '/', $class_name) . '.class.php';

            if (file_exists($filename)) {
                require $filename;
            }
        }
    }
}