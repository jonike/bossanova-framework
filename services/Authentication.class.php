<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Services
 */
namespace services;

use bossanova\Services\Services;
use bossanova\Render\Render;
use bossanova\Mail\Mail;

use services\Permissions;
use services\Users;

class Authentication extends Services
{
    /**
     * Login actions (login and password recovery)
     *
     * @return void
     */
    public function login()
    {
        if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id']) {
            if (isset(Render::$urlParam[1]) && Render::$urlParam[1] == 'login') {
                $data = [
                    'message' => "^^[User already logged in]^^",
                    'success' => 1,
                    'url' => Render::getLink(Render::$urlParam[0])
                ];
            }
        } else {
            // Check any login information been posted
            if (isset($_POST['username'])) {
                if (isset($_POST['recovery']) && $_POST['recovery']) {
                    $data = $this->loginRecovery();
                } else {
                    $data = $this->loginRegister();
                }
            } else {
                // Check for any recovery acess
                if (isset($_GET['h']) && $_GET['h']) {
                    $data = $this->loginHash();
                }
            }
        }

        return $data;
    }

    /**
     * Execute the logout actions
     *
     * @return void
     */
    public function logout()
    {
        // Force logout
        if ($user_id = $this->getUser()) {
            $user = new \models\Users;
            $user->get($user_id);
            $user->user_hash = '';
            $user->save();
        }

        // Removing session
        $_SESSION = [];

        // Destroy session
        session_destroy();

        // Removing cookie
        $this->destroySession();

        // Redirect to the main page
        $url = Render::$urlParam[0];
        if ($url != 'login') {
            $url .= '/login';
        }

        header("Location: /$url");
        exit();
    }

    /**
     * Get the registered user_id
     *
     * @return integer $user_id
     */
    public function getUser()
    {
        return (isset($_SESSION['user_id'])) ? $_SESSION['user_id'] : 0;
    }

    /**
     * Helper to get the identification from the user, if is not identified redirec to the login page
     *
     * @return integer $user_id
     */
    public function getIdent()
    {
        // Check if the cookie for this user is registered and try to recover the userid
        if ($access_token = $this->initSession()) {
            if (! $this->getUser()) {
                // Cookie information
                $cookie = json_decode(base64_decode($_COOKIE['bossanova']));

                // User stored in the cookies
                $user_id = isset($cookie->user_id) ? $cookie->user_id : 0;

                // User Identification
                if ($user_id) {
                    // Load user information
                    $user = new \models\Users();
                    $row = $user->getUserByIdent($access_token);

                    if (isset($row['user_id']) && $row['user_id'] == $user_id && $row['user_hash'] == $access_token) {
                        // Update cookie
                        $this->access_token = $row['user_hash'];

                        // keep the logs for that transaction
                        $data['message'] = "Recovery session from cookie {$row['user_hash']}";

                        // Authenticate user
                        $this->authenticate($row, $data['message']);
                    }
                }
            }
        }

        // After all process check if the user is logged
        if (! $this->getUser()) {
            $param = isset(Render::$urlParam[1]) ? Render::$urlParam[1] : '';

            // Redirect the user to the login page
            if ($param != 'login') {
                // Keep the reference to redirect to this page after the login
                $_SESSION['HTTP_REFERER'] = '/' . implode("/", Render::$urlParam);

                // Redirect
                $data = [
                    'error' => '1',
                    'message' => '^^[User not authenticated]^^',
                    'url' => Render::getLink(Render::$urlParam[0] . '/login'),
                ];
            }
        }

        return $this->getUser();
    }

    /**
     * Perform authentication
     *
     * @param array $row
     * @param string $message
     */
    private function authenticate($row, $message = '')
    {
        // Load permission services
        $permissions = new Permissions();

        // Registering permissions
        $_SESSION['permission'] = $permissions->getPermissionsById($row['permission_id']);

        // Check if the user is a superuser
        $_SESSION['superuser'] = $permissions->isPermissionsSuperUser($row['permission_id']);

        // User session
        $_SESSION['user_id'] = $row['user_id'];

        // Permission
        $_SESSION['permission_id'] = $row['permission_id'];

        // keep the logs for that transaction
        $_SESSION['user_access_id'] = $this->accessLog($row['user_id'], $message, 1);

        // Locale registration
        $this->setLocale($row['user_locale']);
    }

    /**
     * Load the access token from the session stored in the cookies
     *
     * @return string $token
     */
    private function initSession()
    {
        // If the cookie is already defined
        if (isset($_COOKIE['bossanova'])) {
            // Extract the access token from the cookie
            $cookie = json_decode(base64_decode($_COOKIE['bossanova']));

            // Define the access token for this session
            $this->access_token = isset($cookie->id) ? $cookie->id : 0;
        } else {
            // No cookie defined
            $this->access_token = 0;
        }

        return $this->access_token;
    }

    /**
     * Load all permissions for a user_id based on his permission_id
     *
     * @param  integer $userId
     * @param  boolean $keepAlive
     * @return string  $token
     */
    private function setSession($userId, $keepAlive)
    {
        try {
            // Generate hash
            $this->access_token = md5(uniqid(mt_rand(), true));

            if ($keepAlive) {
                // Save cookie
                $data = json_encode([
                    'domain' => Render::getDomain(),
                    'user_id' => $userId,
                    'id' => $this->access_token,
                    'date' => time()
                ]);
                $cookie_value = base64_encode($data);
                $_COOKIE['bossanova'] = $cookie_value;

                // Check headers
                if (headers_sent()) {
                    throw Exception("Http already sent.");
                }

                // Default for 7 days
                $expire = time() + 86400 * 7;
                setcookie('bossanova', $cookie_value, $expire);
            }
        } catch (Exception $e) {
            if (class_exists("Error")) {
                Error::handler("Http already sent.", $e);
            } else {
                echo "Http already sent.";
            }
        }

        return $this->access_token;
    }

    /**
     * Destroy the session and make sure destroy cookies
     *
     * @return void
     */
    private function destroySession()
    {
        if (isset($_COOKIE['bossanova'])) {
            $expire = time();
            $base_domain = Render::getDomain();
            setcookie('bossanova', null, -1);
            $_COOKIE['bossanova'] = '';
        }
    }

    /**
     * Send an email with the password recovery instructions
     * @return $data
     */
    private function loginRecovery()
    {
        $username = $_POST['username'];

        // Load user information
        $user = new \models\Users();
        $row = $user->getUserByIdent($username);

        if (! isset($row['user_id'])) {
            // Check if the user is found
            $data['message'] = "^^[User not found]^^";
        } else {
            // Check the user status
            if (! $row['user_status']) {
                $data['message'] = "^^[User is disabled]^^";
            } else {
                // Hash
                $row['user_hash'] = md5(uniqid(mt_rand(), true));

                // Full Url
                $row['url'] = Render::getLink(Render::$urlParam[0] . '/login');

                // Save hash in the user table, is is a one time code to access the system
                $user->user_hash = $row['user_hash'];
                $user->user_recovery = 1;
                $user->user_recovery_date = 'NOW()';
                $user->save();

                // Destroy any existing cookie
                $this->destroySession();

                // Send email with instructions
                $filename = defined('EMAIL_RECOVERY_FILE') && file_exists(EMAIL_RECOVERY_FILE) ? EMAIL_RECOVERY_FILE : 'resources/texts/recover.txt';

                try {
                    // Prepare the content
                    $content = file_get_contents($filename);
                    $content = $this->mail->replaceMacros($content, $row);
                    $content = $this->mail->translate($content);

                    // Send email to the user
                    $t = array(array($row['user_email'], $row['user_name']));
                    $f = array(MS_CONFIG_FROM, MS_CONFIG_NAME);

                    // Send
                    $this->mail->sendmail($t, EMAIL_RECOVERY_SUBJECT, $content, $f);

                    // Return message
                    $data = [
                        'url' => $row['url'],
                        'message' => "^^[The instructions to recovery your password was sent to your email]^^",
                        'success' => 1,
                    ];
                } catch (Exception $e) {
                    $data = [
                        'error' => 1,
                        'message' => "It was not possible to open $filename",
                    ];
                }
            }
        }

        return $data;
    }

    /**
     * Register the login
     * @return $data
     */
    private function loginRegister()
    {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Module
        $module = Render::$urlParam[0];

        // Load user information
        $user = new \models\Users();
        $row = $user->getUserByIdent($username);

        if (! isset($row['user_id']) || ! $row['user_id'] || ! $row['user_status']) {
            $data = [
                'error' => 1,
                'message' => '^^[Invalid Username]^^',
            ];

            // keep the logs for that transaction
            $this->accessLog(null, "^^[Invalid Username]^^: $username", 0);
        } else {
            // New passwords
            $pass1 = hash('sha512', $password . $row['user_salt']);

            // Old passwords, keep compatibility
            $pass2 = hash('md5', $password);

            // Check to see if password matches
            if (($pass1 == $row['user_password']) || ($pass2 == $row['user_password'])) {
                // User active
                if ($row['user_status'] == 1) {
                    // Keep session alive by the use of cookies
                    $keepAlive = (isset($_POST['remember'])) ? 1 : 0;

                    // Register token
                    $access_token = $this->setSession($row['user_id'], $keepAlive);

                    // Update hash
                    $user->user_hash = $access_token;
                    $user->save();

                    // Redirection to the referer
                    if (isset($_SESSION['HTTP_REFERER'])) {
                        $url = $_SESSION['HTTP_REFERER'];
                        unset($_SESSION['HTTP_REFERER']);
                    } else {
                        $url = Render::getLink($module);
                    }

                    $data = [
                        'success' => 1,
                        'message' => "^^[Login successfully]^^",
                        'token' => $access_token,
                        'url' => $url,
                    ];

                    $this->authenticate($row, $data['message']);

                    // This is the first access, user need to change the password
                } else if ($row['user_status'] == 2) {
                    $url = Render::getLink($module . '/login?h=' . $row['user_hash']);

                    $data = [
                        'success' => 1,
                        'message' => '^^[This is your first access and need to choose a new password]^^',
                        'url' => $url,
                    ];
                }
            } else {
                $data = [
                    'error' => 1,
                    'message' => "^^[Incorrect Password]^^",
                ];

                // keep the logs for that transaction
                $this->accessLog($row['user_id'], $data['message'], 0);
            }
        }

        return $data;
    }

    /**
     * This method handle user register confirmation, password recovery or hash login
     */
    private function loginHash()
    {
        $hash = $_GET['h'];

        // Load user information
        $user = new \models\Users();
        $row = $user->getUserByIdent($hash);

        // Module
        $module = Render::$urlParam[0];

        // Found
        if (isset($row['user_id'])) {
            // Action depends on the current user status
            if ($row['user_status'] == 2) {
                // User activation
                if (isset($_POST['password'])) {
                    // Update user password
                    $salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
                    $pass = hash('sha512', $_POST['password'] . $salt);

                    $user->user_salt = $salt;
                    $user->user_password = $pass;
                    $user->user_hash = '';
                    $user->user_status = 1;
                    $user->save();

                    $data = [
                        'url' => Render::getLink($module . '/login'),
                        'message' => "^^[Password updated]^^",
                        'success' => 1,
                    ];
                }
            } else if ($row['user_status'] == 1) {
                // This block handle password recovery
                if ($row['user_recovery'] == 1) {
                    if (isset($_POST['password'])) {
                        // Update user password
                        $salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
                        $pass = hash('sha512', $_POST['password'] . $salt);

                        $user->user_salt = $salt;
                        $user->user_password = $pass;
                        $user->user_hash = '';
                        $user->user_recovery = '';
                        $user->user_recovery_date = '';
                        $user->save();

                        $data = [
                            'url' => Render::getLink($module . '/login'),
                            'message' => "^^[Password updated]^^",
                            'success' => 1,
                        ];
                    } else {
                        $data = [
                            'success' => 1,
                            'message' => "^^[Please choose a new password]^^",
                        ];
                    }
                } else if ($row['user_recovery'] == 2) {
                    // Reset hash
                    $user->user_hash = '';
                    $user->user_recovery = '';
                    $user->user_recovery_date = '';
                    $user->save();

                    $data = [
                        'success' => 1,
                        'message' => "^^[Hash updated]^^",
                        'url' => Render::getLink($module),
                    ];

                    $this->authenticate($row, $data['message']);
                } else {
                    // No hash found
                    $data = [
                        'error' => 1,
                        'url' => Render::getLink($module . '/login'),
                    ];
                }
            } else {
                // No user found
                $data = [
                    'error' => 1,
                    'url' => Render::getLink($module . '/login'),
                ];
            }
        } else {
            // No user found
            $data = [
                'error' => 1,
                'url' => Render::getLink($module . '/login'),
            ];
        }

        return $data;
    }

    /**
     * Save the user access log
     *
     * @param  integer $user_id
     * @param  string  $message
     * @param  integer $status
     * @return integer $id
     */
    private function accessLog($user_id, $message, $status)
    {
        $column = [
            "user_id" => $user_id,
            "access_message" => $message,
            "access_browser" => $_SERVER['HTTP_USER_AGENT'],
            "access_json" => json_encode($_SERVER),
            "access_status" => $status
        ];

        $user = new \models\Users();
        $accessId = $user->setLog($column);

        return $accessId;
    }

    /**
     * Set the user initial locale
     *
     * @param  string $locale Locale file, must be available at resources/locale/[string].csv
     * @return void
     */
    public function setLocale($locale)
    {
        if (file_exists("resources/locales/$locale.csv")) {
            // Update the session language reference
            $_SESSION['locale'] = $locale;

            // Exclude the current dictionary words
            unset($_SESSION['dictionary']);
        }
    }
}
