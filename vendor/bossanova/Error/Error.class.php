<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Error Handler
 */
namespace Bossanova\Error;

class Error
{
    // Singleton needs
    private function __construct()
    {

    }

    // Cannot be clonned
    private function __clone()
    {

    }

    /**
     * Error Handling
     */
    public static function handler($description, $e)
    {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $description = strip_tags($description);
            $e = strip_tags($e);
            $data['message'] = $description;
            echo json_encode($data);
        } else {
            echo "<h1>Bossanova Framework</h1>";
            echo "<p>{$description}</p>";
        }

        exit();
    }
}
