<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Main config file
 */

// Global config definition
set_time_limit(0);
ini_set('date.timezone', 'Europe/London');
ini_set('session.use_cookies', 1);

// Default Locale
define('DEFAULT_LOCALE', 'en_GB');

// Set the email to receive all SQL erros and debug information in your email address
define('DATABASE_DEBUG_EMAIL', '');

// Route configuration will be also saved in the database.
define('DATABASE_ROUTING', 1);

// Activate social network plugin - URL will be refers to valid usersnames.
define('SOCIAL_NETWORK_EXTENSION', 0);

// Global templates for erros
define('TEMPLATE_ERROR', 'default/error.html');

// Global mail server configuration
define('MS_CONFIG_TYPE', 'phpmailer');
define('MS_CONFIG_HOST', '');
define('MS_CONFIG_PORT', 25);
define('MS_CONFIG_FROM', 'info@localhost');
define('MS_CONFIG_NAME', 'Info');
define('MS_CONFIG_USER', '');
define('MS_CONFIG_PASS', '');
define('MS_CONFIG_KEY', '');

// Login request email subject
define('EMAIL_RECOVERY_FILE', 'resources/texts/recovery.txt');
define('EMAIL_RECOVERY_SUBJECT', 'Login Reset Request');
define('EMAIL_REGISTRATION_FILE', 'resources/texts/registration.txt');
define('EMAIL_REGISTRATION_SUBJECT', 'Welcome to Bossanova');

// Facebook
// define('FACEBOOK_APPID', '');
// define('FACEBOOK_SECRET', '');

// Different configurations depending on environment
if (APPLICATION_ENV == 'production') {
    // Disable all reporting
    ini_set('error_reporting', E_ALL);
    ini_set('display_errors', 1);

    // Global database configuration
	define('DB_CONFIG_TYPE', 'pgsql');
	define('DB_CONFIG_HOST', 'localhost');
	define('DB_CONFIG_USER', 'postgres');
	define('DB_CONFIG_PASS', '');
	define('DB_CONFIG_NAME', 'bossanova');
} else {
    // Disable all reporting
    ini_set('error_reporting', E_ALL);
    ini_set('display_errors', 1);

    // Global database configuration
    define('DB_CONFIG_TYPE', 'pgsql');
    define('DB_CONFIG_HOST', 'localhost');
    define('DB_CONFIG_USER', 'postgres');
    define('DB_CONFIG_PASS', 'hodell11');
    define('DB_CONFIG_NAME', 'bossanova');
}

// Global routing definition, this information will be marged with any stored database definitions table routes when DATABASE_ROUTING is true
$route = array(
    '' => array(
        'template_path' => 'default/index.html',
        'template_area' => 'content',
        'template_recursive' => '1',
    ),
    'login' => array(
        'template_path' => 'default/login.html',
        'template_area' => 'content'
    ),
    'admin' => array(
        'template_path' => 'default/index.html',
        'template_area' => 'content',
        'template_recursive' => '1'
    )
);

// global access restriction. When a route is defined here you must use the database to give the user permissions
$restriction = array(
    'admin' => array(
        'title' => '^^[Admin Portal]^^'
    )
);

// Fix templates elements
$persistent_elements = array();