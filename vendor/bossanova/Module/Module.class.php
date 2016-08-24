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
 * Module Library
 */
namespace Bossanova\Module;

use Bossanova\Auth\Auth;
use Bossanova\Render\Render;
use Bossanova\Database\Database;
use Bossanova\Mail\Mail;

class Module
{
    /**
     * Global authentication instance
     *
     * @var $auth
     */
    public $auth;

    /**
     * Global database instance
     *
     * @var $query
     */
    public $query;

    /**
     * Global sendmail instance
     *
     * @var $mail
     */
    public $mail;

    /**
     * Global audit description to be store in the table audit in the __destruct
     *
     * @var $audit
     */
    public $audit;

    /**
     * Global data object to be available in the view scope
     *
     * @var $view
     */
    public $view = array();

    /**
     * Connect to the database
     */
    public function __construct()
    {
        $this->query = Database::getInstance(null, array(
            DB_CONFIG_TYPE,
            DB_CONFIG_HOST,
            DB_CONFIG_USER,
            DB_CONFIG_PASS,
            DB_CONFIG_NAME
        ));
    }

    /**
     * The __default module function can be used for RESTful requests if the
     * module name has the name of the table in the database
     *
     * @return string $json
     */
    public function __default()
    {
        global $restriction;

        // Id
        $id = 0;

        // Reading Restful Request
        if ((int) $this->getParam(1) > 0) {
            // Table name
            $table = $this->getParam(0);
            $id = $this->getParam(1);
            $action = $table;
        } elseif ((int) $this->getParam(2) > 0) {
            // Table name
            $table = $this->getParam(1);
            $id = $this->getParam(2);
            $action = $this->getParam(0) . '/' . $table;
        } elseif ($this->getParam(0) && ! $this->getParam(1)) {
            // Table name
            $table = $this->getParam(0);
            $action = $table;
        } elseif ($this->getParam(0) && $this->getParam(1) && ! $this->getParam(2)) {
            // Table name
            $table = $this->getParam(1);
            $action = $this->getParam(0) . '/' . $table;
        } else {
            $action = $this->getParam(0);
        }

        // Proccess the RESTful requisition
        if ($_SERVER['REQUEST_METHOD'] == "POST" || $_SERVER['REQUEST_METHOD'] == "PUT") {
            // Table is the name of the first argument in the URL (module name in application)
            $table = $this->escape($table);

            // Binding data
            if (isset($_POST) && count($_POST)) {
                // Filtering data
                foreach ($_POST as $k => $v) {
                    $column[$k] = $this->query->Bind($v);
                }

                try {
                    // Find by id
                    if ($id > 0) {
                        // Check any restriction for the action insert
                        $action = "$action/update";

                        // No restriction defined or restriction defined but permission defined as well
                        if ($this->getPermission($action)) {
                            // Saving data
                            if ($model = $this->query->model($table)) {
                                $model->column($column)->update($id);

                                if ($this->query->error) {
                                    throw new \Exception('^^[It was not possible update this record]^^');
                                } else {
                                    $data['message'] = "^^[Successfully saved]^^";

                                    if (method_exists($this, "update_callback")) {
                                        $this->update_callback($id);
                                    }
                                }
                            }
                        } else {
                            throw new \Exception('^^[Permission denied]^^');
                        }
                    } else {
                        // Check any restriction for the action insert
                        $action = "$action/insert";

                        // No restriction defined or restriction defined but permission defined as well
                        if ($this->getPermission($action)) {
                            // Saving data
                            if ($model = $this->query->model($table)) {
                                $id = $model->column($column)->insert();

                                if ($this->query->error) {
                                    throw new \Exception('^^[It was not possible insert this record]^^');
                                } else {
                                    $data['id'] = $id;
                                    $data['message'] = "^^[Successfully saved]^^";

                                    if (method_exists($this, "insert_callback")) {
                                        $this->insert_callback($data['id']);
                                    }
                                }
                            }
                        } else {
                            throw new \Exception('^^[Permission denied]^^');
                        }
                    }
                } catch (\Exception $e) {
                    $data = array('error' => '1', 'message' => $e->getMessage());
                }
            }
        } elseif ($_SERVER['REQUEST_METHOD'] == "DELETE") {
            // Check any restriction for the action insert
            $action = "$action/delete";

            // No restriction defined or restriction defined but permission defined as well
            try {
                if ($this->getPermission($action)) {
                    // Id to be removed
                    if ($id > 0) {
                        // Table is the name of the first argument in the URL (module name in application)
                        $table = $this->escape($table);

                        // Runtime model
                        $this->query->model($table)->delete($id);

                        if ($this->query->error) {
                            throw new \Exception('^^[It was not possible delete this record]^^');
                        } else {
                            $data['message'] = "^^[Successfully deleted]^^";

                            if (method_exists($this, "delete_callback")) {
                                $this->delete_callback($id);
                            }
                        }
                    }
                } else {
                    throw new \Exception('^^[Permission denied]^^');
                }
            } catch (\Exception $e) {
                $data = array('error' => '1', 'message' => $e->getMessage());
            }
        } elseif ($_SERVER['REQUEST_METHOD'] == "GET") {
            // Check any restriction for the action insert
            $action = "$action/select";

            // No restriction defined or restriction defined but permission defined as well
            try {
                if ($this->getPermission($action)) {
                    // Id to be selected
                    if ($id > 0) {
                        // Table is the name of the first argument in the URL (module name in application)
                        $table = $this->escape($table);

                        // Runtime model
                        $data = $this->query->model($table)->select($id);

                        if ($this->query->error) {
                            throw new \Exception('^^[It was not possible to load this record]^^');
                        }
                    }
                } else {
                    throw new \Exception('^^[Permission denied]^^');
                }
            } catch (\Exception $e) {
                $data = array('error' => '1', 'message' => $e->getMessage());
            }
        }

        return isset($data) ? $this->jsonEncode($data) : '';
    }

    /**
     * RESTful default method for data selection, to use:
     *
     * @return string json with record information
     */
    public function select()
    {
        // Table name based on the module and controllers
        if ((int) $this->getParam(2) > 0) {
            $table = $this->escape($this->getParam(0));
            $tabid = (int) $this->getParam(2);
        } elseif ((int) $this->getParam(3) > 0) {
            $table = $this->escape($this->getParam(1));
            $tabid = (int) $this->getParam(3);
        } else {
            $data['error'] = "Malformed Bossanova RESTful URL";
        }

        // Id to be selected
        if (isset($tabid) && $tabid > 0) {
            // Table is the name of the first argument in the URL (module name in application)
            $table = $this->escape($table);

            // Runtime model
            $data = $this->query->model($table)->select($tabid);
        }

        return isset($data) ? $this->jsonEncode($data) : '';
    }

    /**
     * RESTful default method for insert a new record
     *
     * @return string json with new inserted id and messages
     */
    public function insert($row = null)
    {
        // Data to be saved
        if (! isset($row)) {
            $row = $_POST;
        }

        // Any data to be saved?
        if (count($row) > 0) {
            // Table name based on the module and controllers
            if (strtolower($this->getParam(2)) == 'insert') {
                $table = $this->escape($this->getParam(1));
            } else {
                $table = $this->escape($this->getParam(0));
            }

            // Saving data
            $id = $this->query->model($table)->column($row)->insert();

            // Check if there is any error from the database class
            if (! $this->query->error) {
                $data['id'] = $id;
                $data['message'] = "^^[Successfully saved]^^";

                // If the method insert_callback in the module exists called it
                if (method_exists($this, "insert_callback")) {
                    $this->insert_callback($id);
                }
            } else {
                $data['error'] = 1;
                $data['message'] = "^^[It was not possible save this record]^^\n";
            }
        }

        return isset($data) ? $this->jsonEncode($data) : '';
    }

    /**
     * RESTful default method for update a record
     *
     * @return string json with new inserted id and messages
     */
    public function update($row = null)
    {
        // Table name based on the module and controllers
        if ((int) $this->getParam(2) > 0) {
            $table = $this->escape($this->getParam(0));
            $tabid = (int) $this->getParam(2);
        } elseif ((int) $this->getParam(3) > 0) {
            $table = $this->escape($this->getParam(1));
            $tabid = (int) $this->getParam(3);
        } else {
            $data['error'] = "Malformed Bossanova RESTful URL";
        }

        // Record id to be saved
        if (isset($tabid) && $tabid > 0) {
            // Data to be saved
            if (! isset($row)) {
                $row = $_POST;
            }

            // Any data to be saved?
            if (count($row) > 0) {
                // Find by id
                if ($tabid > 0) {
                    // Saving data
                    $data = $this->query->model($table)->column($row)->update($tabid);

                    // Check if there is any error from the database class
                    if (! $this->query->error) {
                        $data['message'] = "^^[Successfully saved]^^";

                        // If the method update_callback in the module exists called it
                        if (method_exists($this, "update_callback")) {
                            $this->update_callback($tabid);
                        }
                    } else {
                        $data['error'] = 1;
                        $data['message'] = "^^[It was not possible save this record]^^\n";
                    }
                }
            }
        }

        return isset($data) ? $this->jsonEncode($data) : '';
    }

    /**
     * RESTful default method for delete a record, to use:
     *
     * @return string json with new inserted id and messages
     */
    public function delete()
    {
        // Table name based on the module and controllers
        if ((int) $this->getParam(2) > 0) {
            $table = $this->escape($this->getParam(0));
            $tabid = (int) $this->getParam(2);
        } elseif ((int) $this->getParam(3) > 0) {
            $table = $this->escape($this->getParam(1));
            $tabid = (int) $this->getParam(3);
        } else {
            $data['error'] = "Malformed Bossanova RESTful URL";
        }

        // Record to be deleted
        if (isset($tabid) && $tabid > 0) {
            //$data = $this->query->model($table)->delete($tabid);

            // Check if there is any error from the database class
            if (! $this->query->error) {
                $data['message'] = "^^[Successfully deleted]^^";

                // If the method update_callback in the module exists called it
                if (method_exists($this, "delete_callback")) {
                    $this->delete_callback($tabid);
                }
            } else {
                $data['error'] = 1;
                $data['message'] = "^^[It was not possible delete this record]^^\n";
            }
        }

        return isset($data) ? $this->jsonEncode($data) : '';
    }

    /**
     * Locale information about the current session
     *
     * @return string json with new inserted id and messages
     */
    public function locale($locale = null)
    {
        // External updates
        if ($this->getParam(1) == 'locale') {
            if ($locale = $this->getParam(2)) {
                if (file_exists("resources/locales/$locale.csv")) {
                    // Update the session language reference
                    $_SESSION['locale'] = $this->getParam(2);

                    // Exclude the current dictionary words
                    unset($_SESSION['dictionary']);

                    // If the user is defined update the user preferences in the table
                    if (isset($_SESSION['user_id']) && $_SESSION['user_id']) {
                        $user = new \models\Users;
                        $user->get($_SESSION['user_id']);
                        $user->user_locale = $_SESSION['locale'];
                        $user->save();
                    }

                    $url = $this->getParam(0);

                    // Redirect to the main page
                    header("Location: /$url");
                }
            } else {
                // Return the current locale in the memory
                return isset($_SESSION['locale']) ? $_SESSION['locale'] : '';
            }
        } else {
            // Internal calls
            if (isset($locale)) {
                // Check if the source file exists
                if (file_exists("resources/locales/$locale.csv")) {
                    // Update the session language reference
                    $_SESSION['locale'] = $locale;

                    // Exclude the current dictionary words
                    unset($_SESSION['dictionary']);
                }
            } else {
                // Return the current locale in the memory
                return isset($_SESSION['locale']) ? $_SESSION['locale'] : '';
            }
        }
    }

    /**
     * This function return the parameters from the URL
     *
     * @param  integer $index number of the param http://domain/0/1/2/3/4/5/6/7...
     * @return mixed
     */
    public function getParam($index = null)
    {
        $value = null;

        // Get the global value defined in the router class
        if (isset($index)) {
            if (isset(Render::$urlParam[$index])) {
                $value = Render::$urlParam[$index];
            }
        } else {
            $value = Render::$urlParam;
        }

        // Return value
        return $value;
    }


    /**
     * This function BF configuration definition
     *
     * @return array $configuration
     */
    public function getConfiguration()
    {
        // Return value
        return Render::$configuration;
    }

    /**
     * Enable disable automatic view load
     *
     * @param integer $mode - set to show the view in case exists
     * @return void
     */
    public function setView($mode)
    {
        Render::$configuration['module_view'] = ($mode) ? 1 : 0;
    }

    /**
     * Enable disable layout
     *
     * @param integer $mode
     * @return void
     */
    public function setLayout($render, $template_path = null)
    {
        Render::$configuration['template_render'] = ($render) ? 1 : 0;

        if (isset($template)) {
            Render::$configuration['template_path'] = $template_path;
        }
    }

    /**
     * Set Layout Title
     *
     * @param string $author
     * @return void
     */
    public function setTitle($data)
    {
        Render::$configuration['template_meta']['title'] = $data;
    }

    /**
     * Set Layout Author Meta
     *
     * @param string $author
     * @return void
     */
    public function setAuthor($data)
    {
        Render::$configuration['template_meta']['author'] = $data;
    }

    /**
     * Set Layout Description Meta
     *
     * @param string $value
     */
    public function setDescription($data)
    {
        Render::$configuration['template_meta']['description'] = $data;
    }

    /**
     * Set Layout Keywords Meta
     *
     * @param string $value
     * @return void
     */
    public function setKeywords($data)
    {
        Render::$configuration['template_meta']['keywords'] = $data;
    }

    /**
     * This function is to return $_POST values
     *
     * @param  array $filter Array with the values you want to get from the $_POST, if is NULL return the whole $_POST.
     * @return array $row    Array with values from $_POST
     */
    public function getPost($filter = null)
    {
        $row = array();

        // Return all variables in the post
        if (! isset($filter)) {
            if (isset($_POST)) {
                $row = $_POST;
            }
        } else {
            // Return only what you have defined as important
            foreach ($filter as $k => $v) {
                if (isset($_POST[$v])) {
                    $row[$v] = $_POST[$v];
                }
            }
        }

        return $row;
    }

    /**
     * This method reads and return a view content
     *
     * @param  string $moduleName
     * @param  string $viewName
     * @return string $html
     */
    public function loadView($viewName, $moduleName = null)
    {
        // Module
        if (! $moduleName) {
            $moduleName = $this->getParam(0);
        }

        // View full path
        $viewPath = 'modules/' . ucfirst($moduleName) . '/views/' . strtolower($viewName) . '.html';

        // Call view if exists
        if (file_exists($viewPath)) {
            ob_start();
            include_once $viewPath;
            return ob_get_clean();
        }
    }

    /**
     * Return a json format
     *
     * @return string $json
     */
    public function jsonEncode($data)
    {
        // Disable layout for Ajax requests
        $this->setLayout(0);

        // Apache headers
        header("Content-type:text/json");

        // Encode string
        $data = json_encode($data);

        // Return json
        return $data;
    }

    /**
     * Default sendmail function, used by the modules to send used email
     *
     * @return void
     */
    protected function sendmail($to, $subject, $html, $from, $files = null)
    {
        if (! $this->mail) {
            $this->mail = new Mail();
        }

        $this->mail->sendmail($to, $subject, $html, $from, $files);
    }

    /**
     * Remove special characters from the string
     *
     * @param  string $str
     * @return string
     */
    protected function escape($str)
    {
        $str = trim($str);

        if (get_magic_quotes_gpc()) {
            $str = stripslashes($str);
        }

        $str = htmlentities($str);
        $search = array("\\", "\0", "\n", "\r", "\x1a", "'", '"');
        $replace = array("", "", "", "", "", "", "");

        return str_replace($search, $replace, $str);
    }

    /**
     * Return the full link of the page
     *
     * @return string $link;
     */
    public function getLink($page = null)
    {
        return Render::getLink($page);
    }

    /**
     * Return the full domain name
     *
     * @return string $domain
     */
    public function getDomain()
    {
        return Render::getDomain();
    }

    /**
     * Login actions
     *
     * @return void
     */
    public function login()
    {
        if (! $this->auth) {
            $this->auth = new Auth($this->query);
        }

        $this->auth->login();
    }

    /**
     * Logout actions
     *
     * @return void
     */
    public function logout()
    {
        if (! $this->auth) {
            $this->auth = new Auth($this->query);
        }

        $this->auth->logout();
    }

    /**
     * Get the registered user_id
     *
     * @return integer $user_id
     */
    public function getIdent()
    {
        if (! $this->auth) {
            $this->auth = new Auth($this->query);
        }

        return $this->auth->getIdent();
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
     * Get the registered permission_id
     *
     * @return integer $permission_id
     */
    public function getGroup()
    {
        return (isset($_SESSION['permission_id'])) ? $_SESSION['permission_id'] : 0;
    }

    /**
     * Get the registered permission_id
     *
     * @return integer $permission_id
     */
    public function getPermission($url)
    {
        $url = explode('/', $url);
        return (Render::isRestricted($url)) ? false : true;
    }

    /**
     * Get the registered permission_id
     *
     * @return integer $permission_id
     */
    public function getPermissions()
    {
        if (! $this->auth) {
            $this->auth = new Auth($this->query);
        }

        return $this->auth->getPermissions();
    }
}
