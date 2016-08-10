<?php

// Base folder outside public
chdir('..');

// Define application environment
if (!defined('APPLICATION_ENV')) {
    $env = (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'dev');
    define('APPLICATION_ENV', $env);
}

include 'autoload.php';
include 'config.inc.php';

use Bossanova\Translate\Translate;
use Bossanova\Render\Render;

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
