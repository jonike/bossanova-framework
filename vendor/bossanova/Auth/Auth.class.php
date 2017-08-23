<?php
/**
 * (c) 2013 Bossanova PHP Framework 2.4.0
 * http://www.bossanova-framework.com
 *
 * @category PHP
 * @package  BossanovaFramework
 * @author   Paul Hodel <paul.hodel@gmail.com>
 * @license  The MIT License (MIT)
 * @link     http://www.bossanova-framework.com
 *
 * Authentication Library
 */
namespace bossanova\Auth;

use bossanova\Database\Database;
use bossanova\Translate\Translate;
use bossanova\Render\Render;
use bossanova\Mail\Mail;

class Auth
{
    /**
     * Access control for modules
     *
     * @var integer $accessToken
     */
    protected $accessToken = 0;

    /**
     * Database instance
     *
     * @var $database
     */
    protected $database;

    /**
     * Database instance with the authentication tables
     *
     * @return void
     */
    public function __construct($query)
    {
        try {
            if (! $query) {
                throw new \Exception('No database instance found');
            }
            $this->database = $query;
        } catch (\Exception $e) {
            \bossanova\Error\Error::handler("It was not possible to connect to the database", $e);
        }
    }

    /**
     * Login actions (login and password recovery)
     *
     * @return void
     */
    public function login()
    {
        if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id']) {
            $data = [
                'message' => "^^[User already logged in]^^",
                'success' => 1,
                'url' => $this->getLink()
            ];
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

        // Print any message
        if (isset($data)) {
            // Redirect to the main page
            if ($this->isAjax()) {
                echo json_encode($data);
            } else {
                if (isset($data['url'])) {
                    header("Location:{$data['url']}\r\n");
                    exit;
                }
            }
        }
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
            $this->database->table("public.users")
                ->column(array('user_hash' => 'null'))
                ->argument(1, "user_id", $user_id)
                ->update()
                ->execute();
        }

        // Removing session
        $_SESSION = array();
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
                    // Check the cookie against the tables
                    $result = $this->database->table("users")
                        ->column("user_id, permission_id, user_locale, user_hash")
                        ->argument(1, "user_id", "$user_id")
                        ->argument(2, "user_hash", "'$access_token'")
                        ->select()
                        ->execute();

                    if ($row = $this->database->fetch_assoc($result)) {
                        // Token matches so register the user again
                        if ($row['user_hash'] == $access_token) {
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
        }

        // After all process check if the user is logged
        if (! $this->getUser()) {
            $param = isset(Render::$urlParam[1]) ? Render::$urlParam[1] : '';

            // Redirect the user to the login page
            if ($param != 'login') {
                // Save referer
                if ($this->isAjax()) {
                    echo json_encode(array('error' => '1', 'message' => '^^[User not authenticated]^^'));
                } else {
                    // Keep the reference to redirect to this page after the login
                    $_SESSION['HTTP_REFERER'] = '/' . implode("/", Render::$urlParam);

                    // Redirect
                    $base = strtolower(Render::$configuration['module_name']) . '/login';
                    $url = Render::getLink($base);
                    header("Location: $url");
                }

                exit();
            }
        }

        return $this->getUser();
    }

    /**
     * Return permissions
     *
     * @param  string $route Check if one route is registered as permitted or return all permissions registered
     * @return mixed
     */
    public function getPermissions($access_route = null)
    {
        $permissions = array();

        // Verify if a specific permission is registered
        if (isset($_SESSION['permission'])) {
            foreach ($_SESSION['permission'] as $k => $v) {
                $k = str_replace('/', '_', $k);
                $permissions[$k] = $v;
            }
        }

        return $permissions;
    }

    /**
     * Check if the registered user is a superuser
     *
     * @param  integer $user_id
     * @return boolean $isSuperuser
     */
    protected function getSuperuser($user_id)
    {
        $isSuperuser = 0;

        // Check if the user is a superuser
        $result = $this->database->table("users u")
            ->innerjoin("permissions p", "u.permission_id = p.permission_id")
            ->column("global_user")
            ->argument(1, "u.user_id", (int) $user_id)
            ->select()
            ->execute();

        if ($row = $this->database->fetch_assoc($result)) {
            $isSuperuser = $row['global_user'];
        }

        return $isSuperuser;
    }

    /**
     * Perform authentication
     *
     * @param array $row
     * @param string $message
     */
    private function authenticate($row, $message = '')
    {
        // Registering permissions
        $_SESSION['permission'] = $this->loadPermissions($row['user_id']);

        // Check if the user is a superuser
        $_SESSION['superuser'] = $this->getSuperuser($row['user_id']);

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
     * Load all permissions for a user_id based on his permission_id
     *
     * @param integer $id - user_id from the table users
     * @return array $permission - Array with all route permited.
     */
    private function loadPermissions($id)
    {
        global $restriction;

        $permission = [];

        // Get the user permission_id
        $result = $this->database->table("users")
            ->column("permission_id")
            ->argument(1, "user_id", (int)$id)
            ->select()
            ->execute();

        if ($row = $this->database->fetch_assoc($result)) {
            if (isset($row['permission_id'])) {
                // Load permission information for this permission_id
                $result = $this->database->table("permissions")
                    ->column("permission_id, global_user")
                    ->argument(1, "permission_id", $row['permission_id'])
                    ->select()
                    ->execute();

                if ($row1 = $this->database->fetch_assoc($result)) {
                    // If the user_id is a superuser register all restrited routes as permited
                    if (isset($row1['global_user']) && $row1['global_user'] == 1) {
                        foreach ($restriction as $k => $v) {
                            $permission[$k] = 1;
                        }
                    } else {
                        if ($row['permission_id']) {
                            // All route permited for his permission_id
                            $result = $this->database->table("permissions_route")
                                ->column("route")
                                ->argument(1, "permission_id", $row['permission_id'])
                                ->select()
                                ->execute();

                            while ($row = $this->database->fetch_assoc($result)) {
                                $permission[$row['route']] = 1;
                            }

                            // All route permited defined in the config.inc.php
                            foreach ($restriction as $k => $v) {
                                if (isset($v['permission']) && $v['permission'] == 1) {
                                    $permission[$k] = 1;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $permission;
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
        // Check for recovery information
        $username = $this->database->bind($_POST['username']);

        $result = $this->database->table("users")
            ->column("user_id, user_email, user_name, user_login")
            ->argument(1, "lower(user_email) = lower($username) OR lower(user_login) = lower($username)", "", "")
            ->argument(2, "user_status", "0", ">")
            ->select()
            ->execute();

        if (! $row = $this->database->fetch_assoc($result)) {
            $data['message'] = "^^[User not found]^^";
        } else {
            // Hash
            $row['user_hash'] = md5(uniqid(mt_rand(), true));

            // Full Url
            $row['url'] = $this->getLink('login');

            // Save hash in the user table, is is a one time code to access the system
            $this->database->table("users")
                ->column(array(
                    'user_hash' => "'{$row['user_hash']}'",
                    'user_recovery' => 1,
                    'user_recovery_date' => "NOW()"
                ))
            ->argument(1, "user_id", $row['user_id'])
            ->update()
            ->execute();

            $data = [
                'url' => $row['url'],
                'message' => "^^[The instructions to recovery your password was sent to your email]^^",
                'success' => 1,
            ];

            // Destroy any existing cookie
            $this->destroySession();

            // Send email with recover instructions
            // Send email with instructions
            $filename = defined('EMAIL_RECOVERY_FILE') && file_exists(EMAIL_RECOVERY_FILE) ?
            EMAIL_RECOVERY_FILE : 'resources/texts/recover.txt';

            try {
                $translate = new Translate;
                $mail = new Mail;
                // Prepare the content
                $content = file_get_contents($filename);
                $content = $translate->run($content);
                $content = $mail->replaceMacros($content, $row);

                // Send email to the user
                $t = array(array($row['user_email'], $row['user_name']));
                $f = array(MS_CONFIG_FROM, MS_CONFIG_NAME);
                $mail->sendmail($t, EMAIL_RECOVERY_SUBJECT, $content, $f);
            } catch (Exception $e) {
                if (class_exists("Error")) {
                    Error::handler("It was not possible to open $filename", $e);
                } else {
                    echo "It was not possible to open $filename";
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
        $username = $this->database->bind($_POST['username']);
        $password = $this->database->bind($_POST['password']);

        // Check for username and password
        $result = $this->database->table("users")
            ->column("user_id, permission_id, user_password, user_salt, user_locale")
            ->argument(1, "lower(user_login) = lower($username) or lower(user_email) = lower($username)", "", "")
            ->argument(2, "user_status", 1)
            ->select()
            ->execute();

        // Fetch results
        $row = $this->database->fetch_assoc($result);

        if (! isset($row['user_id']) || ! $row['user_id']) {
            $data['message'] = "^^[Invalid Username]^^";

            // keep the logs for that transaction
            $this->accessLog(null, "^^[Invalid Username]^^: " . $_POST['username'], 0);
        } else {
            // New passwords
            $pass1 = hash('sha512', $_POST['password'] . $row['user_salt']);

            // Old passwords, keep compatibility
            $pass2 = hash('md5', $_POST['password']);

            // Check to see if password matches
            if (($pass1 == $row['user_password']) || ($pass2 == $row['user_password'])) {
                // Keep session alive by the use of cookies
                $keepAlive = (isset($_POST['remember'])) ? 1 : 0;

                // Register token
                $access_token = $this->setSession($row['user_id'], $keepAlive);

                // Update hash
                $this->database->table("users")
                    ->column(array('user_hash' => "'$access_token'"))
                    ->argument(1, "user_id", "{$row['user_id']}")
                    ->update()
                    ->execute();

                $data['message'] = "^^[Login successfully]^^";
                $data['success'] = 1;
                $data['token'] = $access_token;

                // Redirection to the referer
                if (isset($_SESSION['HTTP_REFERER'])) {
                    $url = $_SESSION['HTTP_REFERER'];
                    unset($_SESSION['HTTP_REFERER']);
                }

                $data['url'] = $url;

                $this->authenticate($row, $data['message']);
            } else {
                $data['message'] = "^^[Incorrect Password]^^";
                $data['success'] = 0;

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
        $hash = $this->database->bind($_GET['h']);

        // Looking for a valid hash among users
        $result = $this->database->table("users")
            ->column("user_id, permission_id, user_locale, user_recovery, user_status")
            ->argument(1, "user_hash", $hash)
            ->select()
            ->execute();

        // Found
        if ($row = $this->database->fetch_assoc($result)) {
            // This block handle user activation
            if ($row['user_status'] == 2) {
                $this->database->table("users")
                    ->column(["user_status" => 1, "user_hash" => "null"])
                    ->argument(1, "user_id", $row['user_id'])
                    ->update()
                    ->execute();

                $data = [
                    'url' => $this->getLink(),
                    'message' => "^^[User Activated]^^",
                    'success' => 1,
                ];

                $this->authenticate($row, $data['message']);
            } else if ($row['user_status'] == 1) {
                // This block handle password recovery
                if ($row['user_recovery'] == 1) {
                    if (isset($_POST['password'])) {
                        // Update user password
                        $salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
                        $pass = hash('sha512', $_POST['password'] . $salt);

                        // Columns
                        $column = array();
                        $column['user_salt'] = "'$salt'";
                        $column['user_password'] = "'$pass'";
                        $column['user_hash'] = "null";
                        $column['user_recovery'] = "null";
                        $column['user_recovery_date'] = "null";

                        // Password Recovery
                        $this->database->table("users")
                            ->column($column)
                            ->argument(1, "user_id", "{$row['user_id']}")
                            ->update()
                            ->execute();

                        $data['url'] = $this->getLink('login');
                        $data['message'] = "^^[Password updated]^^";
                    }
                } else if ($row['user_recovery'] == 2) {
                    // Reset hash
                    $this->database->table("users")
                        ->column(array(
                            'user_hash' => "NULL",
                            "user_recovery" => "NULL",
                            "user_recovery_date" => "NULL"
                        ))
                        ->argument(1, "user_id", "{$row['user_id']}")
                        ->update()
                        ->execute();

                    $data['url'] = $this->getLink();
                    $data['message'] = "^^[Hash access]^^";

                    $this->authenticate($row, $data['message']);
                }
            }
        } else {
            // No recovery or user activation
            $data['url'] = $this->getLink('login');
            $data['success'] = 0;
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
            "access_message" => "$message",
            "access_browser" => $_SERVER['HTTP_USER_AGENT'],
            "access_json" => json_encode($_SERVER),
            "access_status" => $status
        ];
        $column = $this->database->bind($column);
        $column['access_date'] = "NOW()";

        $this->database->table("users_access")
            ->column($column)
            ->insert()
            ->execute();

        return $this->database->insert_id('users_access_user_access_id_seq');
    }

    /**
     * Return the full link of the page
     * @param  string $page
     * @return string $link
     */
    public function getLink($page = null)
    {
        $route = Render::$urlParam;
        $node = array_pop($route);
        $base = implode('/', $route);

        if ($page) {
            if ($base) {
                $base .= '/' . $page;
            } else {
                $base = $page;
            }
        }

        return $link = Render::getLink($base);
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

    public function isAjax()
    {
        return Render::isAjax();
    }
}
