<?php
/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * PHP version 5
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
        if (! isset($_SESSION['user_id']) || ! $_SESSION['user_id']) {
            // Check any login information been posted
            if (isset($_POST['username'])) {
                if (isset($_POST['recovery']) && $_POST['recovery']) {
                    // Check for recovery information
                    $username = $this->database->bind($_POST['username']);

                    $this->database->table("users");
                    $this->database->column("user_id, user_email, user_name, user_login");
                    $this->database->argument(1, "lower(user_email)", "lower($username)");
                    $this->database->argument(2, "user_status", "0", ">");
                    $this->database->select();
                    $result = $this->database->execute();

                    if (! $row = $this->database->fetch_assoc($result)) {
                        $data['message'] = "^^[User not found]^^";
                    } else {
                        $row['user_hash'] = md5(uniqid(mt_rand(), true));

                        // Save hash in the user table, is is a one time code to access the system
                        $this->database->table("users");
                        $this->database->column(array(
                            'user_hash' => "'{$row['user_hash']}'",
                            'user_recovery' => 1,
                            'user_recovery_date' => "NOW()"
                        ));
                        $this->database->argument(1, "user_id", $row['user_id']);
                        $this->database->update();
                        $this->database->execute();

                        $data['message'] = "^^[The instructions to recovery your password was sent to your email]^^";
                        $data['success'] = 1;

                        // Destroy any cookie
                        $this->destroySession();

                        // Full Url
                        $row['url'] = $this->getLink('login');

                        // Call login recovery method
                        $this->loginRecovery($row);
                    }
                } else {
                    $username = $this->database->bind($_POST['username']);
                    $password = $this->database->bind($_POST['password']);

                    // Check for username and password
                    $this->database->table("users");
                    $this->database->column("user_id, permission_id, user_password, user_salt, user_locale");
                    $this->database->argument(1, "lower(user_login)", "lower($username)");
                    $this->database->argument(2, "lower(user_email)", "lower($username)");
                    $this->database->argument(3, "user_status", 1);
                    $this->database->where("((1) OR (2)) AND (3)");
                    $this->database->select();
                    $result = $this->database->execute();
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
                            // Update cookie
                            $keepAlive = (isset($_POST['remember'])) ? 1 : 0;
                            $access_token = $this->setSession($row['user_id'], $keepAlive);

                            // Update hash
                            $this->database->table("users");
                            $this->database->column(array('user_hash' => "'$access_token'"));
                            $this->database->argument(1, "user_id", "{$row['user_id']}");
                            $this->database->update();
                            $this->database->execute();

                            // Registering permissions
                            $_SESSION['permission'] = $this->loadPermissions($row['user_id']);

                            // Check if the user is a superuser
                            $_SESSION['superuser'] = $this->getSuperuser($row['user_id']);

                            // User session
                            $_SESSION['user_id'] = $row['user_id'];

                            // Permission
                            $_SESSION['permission_id'] = $row['permission_id'];

                            // Locale registration
                            $this->setLocale($row['user_locale']);

                            $data['message'] = "^^[Login successfully]^^";
                            $data['success'] = 1;

                            // keep the logs for that transaction
                            $_SESSION['user_access_id'] = $this->accessLog($row['user_id'], $data['message'], 1);
                        } else {
                            $data['message'] = "^^[Incorrect Password]^^";

                            // keep the logs for that transaction
                            $this->accessLog($row['user_id'], $data['message'], 0);
                        }
                    }
                }
            } else {
                // Check for any recovery acess
                if (isset($_GET['h']) && $_GET['h']) {
                    $hash = $this->database->bind($_GET['h']);

                    $this->database->table("users");
                    $this->database->column("user_id, permission_id, user_locale");
                    $this->database->argument(1, "user_recovery", 1);
                    $this->database->argument(2, "user_status", 1);
                    $this->database->argument(3, "user_hash", $hash);
                    $this->database->select();
                    $result = $this->database->execute();

                    if ($row = $this->database->fetch_assoc($result)) {
                        // Update hash
                        $this->database->table("users");
                        $this->database->column(array(
                            'user_hash' => "NULL",
                            "user_recovery" => "NULL",
                            "user_recovery_date" => "NULL"
                        ));
                        $this->database->argument(1, "user_id", "{$row['user_id']}");
                        $this->database->update();
                        $this->database->execute();

                        // Registering permissions
                        $_SESSION['permission'] = $this->loadPermissions($row['user_id']);

                        // Check if the user is a superuser
                        $_SESSION['superuser'] = $this->getSuperuser($row['user_id']);

                        // User session
                        $_SESSION['user_id'] = $row['user_id'];

                        // Permission
                        $_SESSION['permission_id'] = $row['permission_id'];

                        // Recovery flag
                        $_SESSION['recovery'] = 1;

                        // Locale registration
                        $this->setLocale($row['user_locale']);

                        // keep the logs for that transaction
                        $_SESSION['user_access_id'] = $this->accessLog($row['user_id'], '^^[Login Successfully]^^', 1);

                        // Redirect to the main page

                        $url = $this->getLink($base);
                        header("Location: $url");
                        exit();
                    } else {
                        // User activation
                        $this->database->table("users");
                        $this->database->column("user_id");
                        $this->database->argument(1, "user_hash", $hash);
                        $this->database->argument(2, "user_status", 2);
                        $this->database->select();
                        $result = $this->database->execute();

                        if ($row = $this->database->fetch_assoc($result)) {
                            $this->database->table("users");
                            $this->database->column(array("user_status" => 1, 'user_hash' => "NULL"));
                            $this->database->argument(1, "user_id", $row['user_id']);
                            $this->database->update();
                            $result = $this->database->execute();

                            // Registering permissions
                            $_SESSION['permission'] = $this->loadPermissions($row['user_id']);

                            // Check if the user is a superuser
                            $_SESSION['superuser'] = $this->getSuperuser($row['user_id']);

                            // User session
                            $_SESSION['user_id'] = $row['user_id'];

                            // Permission
                            $_SESSION['permission_id'] = $row['permission_id'];

                            // Recovery flag
                            $_SESSION['activated'] = 1;

                            // Locale registration
                            $this->setLocale($row['user_locale']);

                            // keep the logs for that transaction
                            $_SESSION['user_access_id'] = $this->accessLog($row['user_id'], "^^[User Activated]^^", 1);

                            // Redirect to the main page
                            $url = $this->getLink();
                            header("Location: $url");
                        }
                    }
                }
            }
        } else {
            $data['message'] = "^^[User already logged in]^^";
            $data['success'] = 1;

            // Redirect to the main page
            if (! $this->isAjax()) {
                 $url = $this->getLink();
                 header("Location: $url");
                 exit();
            }
        }

        // Print any message
        if (isset($data)) {
            if ($this->isAjax()) {
                echo json_encode($data);
            } else {
                echo $data['message'];
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
            $this->database->table("users")
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
        $url = $this->getLink('login');
        header("Location: $url");
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
                    $this->database->table("users");
                    $this->database->column("user_id, permission_id, user_locale, user_hash");
                    $this->database->argument(1, "user_id", "$user_id");
                    $this->database->argument(2, "user_hash", "'$access_token'");
                    $this->database->select();
                    $result = $this->database->execute();

                    if ($row = $this->database->fetch_assoc($result)) {
                        // Token matches so register the user again
                        if ($row['user_hash'] == $access_token) {
                            // Update cookie
                            $this->access_token = $row['user_hash'];

                            // Registering permissions
                            $_SESSION['permission'] = $this->loadPermissions($row['user_id']);

                            // Check if the user is a superuser
                            $_SESSION['superuser'] = $this->getSuperuser($row['user_id']);

                            // User session
                            $_SESSION['user_id'] = $row['user_id'];

                            // Permission
                            $_SESSION['permission_id'] = $row['permission_id'];

                            // keep the logs for that transaction
                            $message = "Recovery session from cookie {$row['user_hash']}";
                            $_SESSION['user_access_id'] = $this->accessLog($row['user_id'], $message, 1);

                            // Locale registration
                            $this->setLocale($row['user_locale']);
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
                    $_SESSION['HTTP_REFERER'] = implode("/", Render::$urlParam);

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
        $this->database->table("users u");
        $this->database->innerjoin("permissions p", "u.permission_id = p.permission_id");
        $this->database->column("global_user");
        $this->database->argument(1, "u.user_id", (int) $user_id);
        $this->database->select();
        $result = $this->database->execute();

        if ($row = $this->database->fetch_assoc($result)) {
            $isSuperuser = $row['global_user'];
        }

        return $isSuperuser;
    }

    /**
     * Load all permissions for a user_id based on his permission_id
     *
     * @param  integer  $id         user_id from the table users
     * @return array    $permission Array with all route permited.
     */
    private function loadPermissions($id)
    {
        global $restriction;

        $permission = array();

        // Get the user permission_id
        $this->database->table("users");
        $this->database->column("permission_id");
        $this->database->argument(1, "user_id", "$id");
        $this->database->select();
        $result = $this->database->execute();
        $row = $this->database->fetch_assoc($result);

        if (isset($row['permission_id'])) {
            // Load permission information for this permission_id
            $this->database->table("permissions");
            $this->database->column("permission_id, global_user");
            $this->database->argument(1, "permission_id", $row['permission_id']);
            $this->database->select();
            $result = $this->database->execute();
            $row1 = $this->database->fetch_assoc($result);

            // If the user_id is a superuser register all restrited routes as permited
            if (isset($row1['global_user']) && $row1['global_user'] == 1) {
                foreach ($restriction as $k => $v) {
                    $permission[$k] = 1;
                }
            } else {
                if ($row['permission_id']) {
                    // All route permited for his permission_id
                    $this->database->table("permissions_route");
                    $this->database->column("route");
                    $this->database->argument(1, "permission_id", $row['permission_id']);
                    $this->database->select();
                    $result = $this->database->execute();
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
                $base_domain = Render::getDomain();
                $data = json_encode(array(
                    'domain' => $base_domain,
                    'user_id' => $userId,
                    'id' => $this->access_token,
                    'date' => time()
                ));
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
     *
     * @param  array $row
     * @return void
     */
    private function loginRecovery($row)
    {
        $filename = defined('EMAIL_RECOVERY_FILE') && file_exists(EMAIL_RECOVERY_FILE) ?
            EMAIL_RECOVERY_FILE :
            'resources/texts/recover.txt';

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
        $column = array(
            "user_id" => $user_id,
            "access_message" => "$message",
            "access_browser" => $_SERVER['HTTP_USER_AGENT'],
            "access_json" => json_encode($_SERVER),
            "access_status" => $status
        );
        $column = $this->database->bind($column);
        $column['access_date'] = "NOW()";

        $this->database->table("users_access");
        $this->database->column($column);
        $this->database->insert();
        $this->database->execute();

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
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strpos(strtolower($_SERVER['HTTP_X_REQUESTED_WITH']), 'http') !== false;
    }
}
