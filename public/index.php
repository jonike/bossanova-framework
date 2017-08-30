<?php

// Base folder
chdir(__DIR__);
chdir('..');

// Define application environment
if (! defined('APPLICATION_ENV')) {
    // If not definition get default
    $env = (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'dev');
    // If action is from a command-line
    if (isset($_SERVER['argv']) && $_SERVER['argv'][2]) {
        $env = $_SERVER['argv'][2];
    }
    // Define environment
    define('APPLICATION_ENV', $env);
}

include 'autoload.php';
include 'config.inc.php';

use bossanova\Translate\Translate;
use bossanova\Render\Render;

// Init session if is not already started
session_start();

// Start Tracking Texts Before Everything, except for CLI calls
if (! isset($_SERVER['argv'])) {
    $translate = new Translate();

    if (isset($_SESSION['locale']) && $_SESSION['locale']) {
        $translate->load($_SESSION['locale']);
    } else {
        $translate->load(DEFAULT_LOCALE);
    }
}

// Create application
$application = new Render();
$application->run();
