<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: BF Render Library
 */
namespace Bossanova\Render;

use Bossanova\Database\Database;
use Bossanova\Error\Error;

class Render
{
    /**
     * Route not found
     *
     * @var $debug boolean
     */
    public $debug = false;

    /**
     * Page not found
     *
     * @var $notFound boolean
     */
    public static $notFound = false;

    /**
     * Route based on the URL
     *
     * @var $urlParam array
     */
    public static $urlParam = array();

    /**
     * Bossanova main configuration it is populated by the method router
     *
     * @var $configuration array();
     */
    public static $configuration = array(
        'route_id' => null,
        'template_area' => null,
        'template_path' => null,
        'template_render' => 1,
        'template_recursive' => null,
        'template_meta' => array(),
        'module_name' => null,
        'module_controller' => null,
        'module_view' => 1,
        'node_id' => null,
        'content' => array(),
        'extra_config' => array()
    );

    /**
     * Bossanova configuration this is a optional since it is possible to config
     * use config.inc.php in the root of bossanova
     *
     * @var $database object
     */
    private $database = null;

    /**
     * Explode de URL request to define the route and create the main global database instance connection
     *
     * @return void
     */
    public function __construct()
    {
        // Identifing a CLI call
        if (isset($_SERVER['argv']) && $_SERVER['argv'][1]) {
            $_GET["bossanova"] = $_SERVER['argv'][1];
        }

        // Get the route URL
        if (isset($_GET["bossanova"])) {
            // Windows compatibility
            $_GET["bossanova"] = str_replace('\\', '/', str_replace("'", "", $_GET["bossanova"]));

            // Custom sitemap call
            if ($_GET["bossanova"] == 'sitemap.xml') {
                self::$urlParam[0] = 'nodes';
                self::$urlParam[1] = 'sitemap';
            } else {
                // Explode route based the URL
                self::$urlParam = explode("/", $this->escape($_GET["bossanova"]));
            }

            // Remove bossanova GET variable
            unset($_GET['bossanova']);

            // Check if last item is empty
            $index = count(self::$urlParam) - 1;

            // If yes redirect to previous path
            if (self::$urlParam[$index] == '') {
                unset(self::$urlParam[$index]);
                // Bossanova URL
                if (! count($_POST) && ! count($_GET)) {
                    $url = substr(str_replace('\\', '/', $_SERVER['REQUEST_URI']), 0, - 1);
                    header("Location: $url");
                }
            }
        }

        // Loading route
        $this->route();

        // Debug mode
        if ($this->debug == true) {
            $this->debugMode();
        }
    }

    /**
     * Render method is the most used method, get all definitions in the configuration loaded by the router
     * and loads files, create instances, loads templates and return the contents to the user.
     *
     * @return void
     */
    public function run()
    {
        $restriction = $this->getConfig('restriction');

        // Return content
        $content = "";

        // Loading restricted modules
        if (! is_array($restriction)) {
            $restriction = array();
        }

        // If is an ajax call don't show the main template. This can be overwrite by the module.
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            self::$configuration['template_render'] = 0;
            // Only load the view for GET requestse (Don't load for POST, PUT, DELETE)
            if ($_SERVER['REQUEST_METHOD'] != "GET") {
                self::$configuration['module_view'] = 0;
            }
        }

        // Check for restriction
        if ($restricted = self::isRestricted()) {
            if (! isset($_SESSION['user_id'])) {
                $module_name = (isset(self::$urlParam[0])) ? ucfirst(strtolower(self::$urlParam[0])) : '';
                if (file_exists("modules/$module_name/$module_name.class.php")) {
                    // Just to check if is possible to recover the login from a cookie
                    // Module must have call getIdent in the __construct
                    $name = "\\modules\\$module_name\\$module_name";
                    $instance = new $name();
                    // Re-check the permissions
                    $restricted = self::isRestricted();
                }
            }
        }

        // Recheck permission after the module being loaded
        if ($restricted) {
            // If not login send user to the login page
            if (isset($_SESSION['user_id']) || isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                // Default template for errors
                if (defined("TEMPLATE_ERROR")) {
                    self::$configuration['template_path'] = TEMPLATE_ERROR;
                }

                // User message
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                    $content = json_encode(array('error'=>'1', 'message' => '^^[Permission denied]^^'));
                } else {
                    header("HTTP/1.1 403 Forbidden");
                    $content = "^^[Permission denied]^^";
                }
            } else {
                // Redirect the user to the login page
                $base = strtolower(self::$configuration['module_name']) . '/login';
                $url = self::getLink($base);
                header("Location: $url");
            }
        } else {
            // Executing module
            if (self::$configuration['module_name']) {
                // Load the content from the main module
                $content = $this->getContent();
            } elseif (self::$notFound == 1) {
                // Default template for errors
                if (defined("TEMPLATE_ERROR")) {
                    self::$configuration['template_path'] = TEMPLATE_ERROR;
                }

                // User message
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                    $content = json_encode(array('error'=>'1', 'message' => '^^[Permission denied]^^'));
                } else {
                    header("HTTP/1.1 404 Not found");
                    $content = "^^[Page not found]^^";
                }
            }
        }

        // Loading template
        if (self::$configuration['template_path'] && self::$configuration['template_render'] == 1) {
            if (file_exists("public/templates/" . self::$configuration['template_path'])) {
                // Get extra contents
                $contents = $this->getContents($content);
                // Loading template layout
                $content = $this->template($contents);
            } else {
                $content = "^^[Template not found]^^ templates/" . self::$configuration['template_path'];
            }
        }

        // Showing content
        if (isset($content)) {
            echo $content;
        }
    }

    /**
     * Get the main module content
     *
     * return string $content
     */
    private function getContent()
    {
        // Loading module
        $module_name = ucfirst(strtolower(self::$configuration['module_name']));

        try {
            if (self::$configuration['module_controller']) {
                $controller_name = self::$configuration['module_controller'];
                $name = "\\modules\\$module_name\\controllers\\$controller_name";
                $instance = new $name();

                // Default method is call view
                $method_name = "__default";

                if (isset(self::$urlParam[2])) {
                    if ((isset(self::$urlParam[3])) && (is_numeric(self::$urlParam[1]))) {
                        if (method_exists($instance, self::$urlParam[3])) {
                            $method_name = self::$urlParam[3];
                        }
                    } else {
                        if (method_exists($instance, self::$urlParam[2])) {
                            $method_name = self::$urlParam[2];
                        }
                    }
                }
            } else {
                // Creating an instance of the module that matches this call
                $name = "\\modules\\$module_name\\$module_name";
                $instance = new $name();
                $method_name = "__default";

                if (isset(self::$urlParam[1])) {
                    if ((isset(self::$urlParam[2])) && (is_numeric(self::$urlParam[1]))) {
                        if (method_exists($instance, self::$urlParam[2])) {
                            // Define other method to call diferent from
                            // default that was defined in the route call
                            $method_name = self::$urlParam[2];
                        }
                    } else {
                        if (method_exists($instance, self::$urlParam[1])) {
                            // Define other method to call diferent from
                            // default that was defined in the route call
                            $method_name = self::$urlParam[1];
                        }
                    }
                }
            }

            // If there is any method call it.
            if ($method_name) {
                // Check if there is OB active for translations
                if (count(ob_list_handlers()) > 1) {
                    // Loadind methods content including translation
                    ob_start();
                    $content = $instance->$method_name();
                    $content .= ob_get_clean();
                } else {
                    // Loading methods content
                    $content = $instance->$method_name();
                }
            }

            // Automatic load view
            if (self::$configuration['module_view'] == 1) {
                if (isset(self::$urlParam[0]) && ! isset(self::$urlParam[1])) {
                    $view = $instance->loadView(self::$urlParam[0], $module_name);
                } elseif (isset(self::$urlParam[1]) && ! isset(self::$urlParam[2])) {
                    $view = $instance->loadView(self::$urlParam[1], $module_name);
                }

                if (isset($view)) {
                    $content = $view . $content;
                }
            }
        } catch (\Exception $e) {
            Error::handler("Error loading main module files.", $e);
        }

        return $content;
    }

    /**
     * Get any extra content
     *
     * return array $contents
     */
    private function getContents($content)
    {
        // Array of contents
        $contents = array();

        // Default area if not defined
        if (! self::$configuration['template_area']) {
            self::$configuration['template_area'] = 'content';
        }

        // Create the default area if exist content for it
        if ($content) {
            $contents[self::$configuration['template_area']] = $content;
        }

        // Sub contents in the database
        $this->database = Database::getInstance(null, array(
            DB_CONFIG_TYPE,
            DB_CONFIG_HOST,
            DB_CONFIG_USER,
            DB_CONFIG_PASS,
            DB_CONFIG_NAME
        ));

        // Check for any configured route
        if (self::$configuration['extra_config']) {
            // Extra configuration
            $extra_config = self::$configuration['extra_config'];

            foreach ($extra_config as $k => $v) {
                // Make sure string is in correct format
                if (isset($extra_config[$k]->module_name)) {
                    $extra_config[$k]->module_name = ucfirst(strtolower($extra_config[$k]->module_name));
                }
                if (isset($extra_config[$k]->controller_name)) {
                    $extra_config[$k]->controller_name = ucfirst(strtolower($extra_config[$k]->controller_name));
                }

                // If nothing yet loaded in the template area
                if (! isset($contents[$extra_config[$k]->template_area])) {
                    // If it is a node call the nodes module
                    if (isset($extra_config[$k]->node_id) && $extra_config[$k]->node_id > 0) {
                        // Create node instance
                        if (! isset($nodes)) {
                            $nodes = new \modules\Nodes\Nodes();
                        }
                        // Load the content to the righth area
                        $area = $extra_config[$k]->template_area;
                        $contents[$area] = $nodes->getContent($extra_config[$k]->node_id, false);
                    } elseif (isset($extra_config[$k]->module_name) && $extra_config[$k]->module_name) {
                        $module_name = $extra_config[$k]->module_name;

                        // Check information about the module call
                        if (isset($extra_config[$k]->controller_name) && $extra_config[$k]->controller_name) {
                            // It is a controlle?
                            $cn = "modules\\{$module_name}\\controllers\\" . $extra_config[$k]->controller_name;
                            $cn = new $cn();
                        } else {
                            // It is a method inside the module
                            $cn = "modules\\{$module_name}\\" . $extra_config[$k]->module_name;
                            $cn = new $cn();
                        }

                        // Check if there is OB active for translations
                        if (count(ob_list_handlers()) > 1) {
                            // Loadind methods content including translation
                            ob_start();
                            $content = $cn->{$extra_config[$k]->method_name}();
                            $content .= ob_get_clean();
                        } else {
                            // Loading methods content
                            $content = $cn->{$extra_config[$k]->method_name}();
                        }

                        // Place content in the correct area
                        $contents[$extra_config[$k]->template_area] = $content;
                    }
                }
            }
        }

        return $contents;
    }

    /**
     * Process the URL and populate the configuration.
     * Configuration defines which module,
     * controller, view, template and other configurations to be loaded.
     *
     * @return void
     */
    private function route()
    {
        // Available routes
        $routes = array();

        // Global config.inc.php definitions
        if (isset($GLOBALS['route'])) {
            foreach ($GLOBALS['route'] as $k => $v) {
                $routes[$k] = $v;
            }
        }

        // If database routing is defined
        if (defined('DATABASE_ROUTING') && DATABASE_ROUTING) {
            // Load information from database conection
            $this->database = Database::getInstance(null, array(
                DB_CONFIG_TYPE,
                DB_CONFIG_HOST,
                DB_CONFIG_USER,
                DB_CONFIG_PASS,
                DB_CONFIG_NAME
            ));

            if (is_object($this->database)) {
                // Search for the URL configuration
                $this->database->Table("routes");
                $this->database->Select();
                $result = $this->database->Execute();

                while ($row = $this->database->fetch_assoc($result)) {
                    $routes[$row['route']] = $row;
                }
            }
        }

        // Order routes
        ksort($routes);

        // Current URL request
        $route = implode('/', self::$urlParam);

        // Looking for a global configuration for the URL
        if (isset($routes) && count($routes)) {
            if (isset($routes[$route])) {
                // Set the configuration
                $this->setConfiguration($routes[$route]);
            } else {
                // Could not find any global configuration, search for any parent URL the is recursive
                if (count(self::$urlParam) && self::$urlParam[count(self::$urlParam) - 1] != 'login') {
                    $url = '';
                    foreach (self::$urlParam as $k => $v) {
                        // Loading configuration
                        $r = 'template_recursive';
                        if (isset($routes[$url]) && isset($routes[$url][$r]) && $routes[$url][$r] == 1) {
                            if (! self::$configuration[$r] || (self::$configuration[$r] && $routes[$url][$r])) {
                                // Configuration for this URL
                                $config = $routes[$url];
                                // Set the configuration
                                $this->setConfiguration($config);
                            }
                        }

                        if ($url) {
                            $url .= '/';
                        }

                        $url .= $v;
                    }
                }
            }
        }

        // Find persistent elements
        $persistentElem = isset($GLOBALS['persistent_elements']['']) ? $GLOBALS['persistent_elements'][''] : array();
        // Could not find any global configuration, search for any parent URL the is recursive

        if (count(self::$urlParam)) {
            $url = '';

            foreach (self::$urlParam as $k => $v) {
                // Loading configuration
                if (isset($GLOBALS['persistent_elements'][$url])) {
                    $persistentElem = $GLOBALS['persistent_elements'][$url];
                }

                if ($url) {
                    $url .= '/';
                }

                $url .= $v;
            }
        }

        if (count($persistentElem)) {
            foreach ($persistentElem as $k => $v) {
                self::$configuration['extra_config'][] = (object) $v;
            }
        }

        // Looking for modules or CMS elements
        if (isset(self::$urlParam[0])) {
            // Module information, check if the call referes to a existing module
            $module_name = ucfirst(strtolower(self::$urlParam[0]));

            // Module exists TODO: improve speed by cache the the IO checkings
            if (file_exists("modules/$module_name/$module_name.class.php")) {
                // Module name
                self::$configuration['module_name'] = $module_name;

                // Controller information: check if the call referes to a existing module
                if (isset(self::$urlParam[1])) {
                    if (self::$urlParam[count(self::$urlParam) - 1] == 'login') {
                        if (! self::$configuration['template_path']) {
                            self::$configuration['template_path'] = "default/login.html";
                            self::$configuration['template_area'] = "content";
                            self::$configuration['template_render'] = 1;
                        }
                    } else {
                        if (isset(self::$urlParam[2]) && is_numeric(self::$urlParam[1])) {
                            // Check if exists any controller that matches this call
                            $controller_name = ucfirst(strtolower(self::$urlParam[2]));

                            if (file_exists("modules/$module_name/controllers/$controller_name.class.php")) {
                                // Controller found
                                self::$configuration['module_controller'] = $controller_name;
                            }
                        } else {
                            // Check if exists any controller that matches this call
                            $controller_name = ucfirst(strtolower(self::$urlParam[1]));

                            if (file_exists("modules/$module_name/controllers/$controller_name.class.php")) {
                                // Controller found
                                self::$configuration['module_controller'] = $controller_name;
                            }
                        }

                        // Integrated CMS option
                        if (self::$urlParam[0] == 'nodes' && self::$urlParam[1] > 0) {
                            self::$configuration['node_id'] = self::$urlParam[1];
                        }
                    }
                }
            } else {
                // Default for page not found
                self::$notFound = 1;

                // Load information from database conection
                $this->database = Database::getInstance(null, array(
                    DB_CONFIG_TYPE,
                    DB_CONFIG_HOST,
                    DB_CONFIG_USER,
                    DB_CONFIG_PASS,
                    DB_CONFIG_NAME
                ));

                if (is_object($this->database)) {
                    // Locale
                    $locale = isset($_SESSION['locale']) && $_SESSION['locale'] ? $_SESSION['locale'] : DEFAULT_LOCALE;

                    // Is there any nodes for this URL
                    $this->database->Table("nodes n");
                    $this->database->Leftjoin("nodes_content c", "n.node_id = c.node_id");
                    $this->database->Column("n.node_id, n.module_name");
                    $this->database->Argument(1, "c.link", "'{$route}'");
                    $this->database->Argument(2, "n.status", 1);
                    $this->database->Select();
                    $result = $this->database->Execute();

                    if ($row = $this->database->fetch_assoc($result)) {
                        self::$configuration['node_id'] = $row['node_id'];
                        self::$configuration['module_name'] = $row['module_name'];
                        self::$notFound = 0;
                    } else {
                        if (defined('SOCIAL_NETWORK_EXTENSION') && SOCIAL_NETWORK_EXTENSION) {
                            // Last try is the URL is reference for the social network
                            $this->getSocialNetworkNames();
                        }
                    }
                }
            }
        }
    }

    /**
     * Set the configuration based on config or in a record save in the route table
     *
     * @param  array $row Configuration to be loaded
     * @return void
     */
    public function setConfiguration($row)
    {
        // Avoid notices
        foreach (self::$configuration as $k => $v) {
            if (! isset($row[$k])) {
                $row[$k] = null;
            }
        }

        // Set configuration new values
        self::$configuration['route_id'] = $row['route_id'];
        self::$configuration['module_name'] = $row['module_name'];
        self::$configuration['template_path'] = $row['template_path'];
        self::$configuration['template_area'] = $row['template_area'];
        self::$configuration['template_render'] = $row['template_render'] == '0' ? 0 : 1;
        self::$configuration['template_recursive'] = $row['template_recursive'] ? 1 : 0;
        self::$configuration['template_meta'] = $row['template_meta'];
        self::$configuration['extra_config'] = json_decode($row['extra_config']);
    }

    /**
     * Get the configuration loaded
     *
     * @return array $configuration Bossanova loaded configuration
     */
    public function getConfiguration()
    {
        return self::$configuration;
    }

    /**
     * Loading the HTML layout including the bossanova needs (base href, and javascript in the end)
     *
     * @param  string $content
     * @return string $html
     */
    public function template($contents)
    {
        // Scheme
        $request_scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https:' : 'http:';

        // Template content
        $html = "";

        // Loading template file content
        $template_path = self::$configuration['template_path'];
        $template_file = fopen("public/templates/{$template_path}", "r");
        while (! feof($template_file)) {
            $html .= fgets($template_file, 9999);
        }
        fclose($template_file);

        // Custom Dashboard
        if (isset(self::$urlParam[0])) {
            $html = str_replace("__NAME__", self::$urlParam[0], $html);
        }

        // Defining baseurl for a correct template images, styling, javascript reference
        $url = $_SERVER["HTTP_HOST"] . substr($_SERVER["SCRIPT_NAME"], 0, strrpos($_SERVER["SCRIPT_NAME"], "/"));
        $baseurl = explode('/', self::$configuration['template_path']);
        array_pop($baseurl);
        $baseurl = implode('/', $baseurl);

        // Page configuration
        $extra = '';

        if (isset(self::$configuration['template_meta']['title'])) {
            $extra .= "\n<title>" . self::$configuration['template_meta']['title'] . "</title>";
        }
        if (isset(self::$configuration['template_meta']['author'])) {
            $value = self::$configuration['template_meta']['author'];
            $extra .= "\n<meta name='author' content='$value'>";
        }
        if (isset(self::$configuration['template_meta']['keywords'])) {
            $value = self::$configuration['template_meta']['keywords'];
            $extra .= "\n<meta itemprop='keywords' name='keywords' content='$value'>";
        }
        if (isset(self::$configuration['template_meta']['description'])) {
            $value = self::$configuration['template_meta']['description'];
            $extra .= "\n<meta itemprop='description' property='og:description' name='description' content='$value'>";
        }
        if (isset(self::$configuration['template_meta']['news_keywords'])) {
            $value = self::$configuration['template_meta']['news_keywords'];
            $extra .= "\n<meta name='news_keywords' content='$value'>";
        }

        if (isset(self::$configuration['template_meta']['title'])) {
            $value = self::$configuration['template_meta']['title'];
            $extra .= "\n<meta property='og:title' content='$value'>";
        }

        // Dynamic Tags (TODO: implement a more effient replace)
        $html = str_replace("<head>", "<head>\n<base href='$request_scheme//$url/templates/$baseurl/'>$extra", $html);
        $html = str_replace("</html>", "", $html);

        // Loading params based on the URL
        $bossanova = implode('/', self::$urlParam);

        // Bossanova JS controllers
        $html .= "<script src='$request_scheme//$url/bossanova.js'></script>\n\n";
        $html .= "<script>\n";
        $html .= "var bossanova_base = '$bossanova';\n";
        $html .= "var bossanova_url = '$request_scheme//$url/';\n";

        if (isset($_SESSION['HTTP_REFERER'])) {
            $html .= "var bossanova_referer = '{$_SESSION['HTTP_REFERER']}'\n";
        } else {
            $html .= "var bossanova_referer = ''\n";
        }

        $token = (isset($_SESSION['user_id'])) ? md5($_SESSION['user_id']) : '';

        $template_area = self::$configuration['template_area'];
        $html .= "$(document).ready(function () { load('{$template_area}', '{$token}'); });";
        $html .= "</script>\n";
        $html .= "</html>";

        // Looking for the template area to insert the content
        if ($contents) {
            $id = '';
            $tag = 0;
            $test = strtolower($html);

            // Is id found?
            $found = 0;

            // Merging HTML
            $merged = $html{0};

            for ($i = 1; $i < strlen($html); $i ++) {
                $merged .= $html{$i};

                // Inside a tag
                if ($tag > 0) {
                    // Inside an id property?
                    if ($tag > 1) {
                        if ($tag == 2) {
                            // Found [=]
                            if ($test{$i} == chr(61)) {
                                $tag = 3;
                            } else {
                                // [space], ["], [']
                                if ($test{$i} != chr(32) && $test{$i} != chr(34) && $test{$i} != chr(39)) {
                                    $tag = 1;
                                }
                            }
                        } else {
                            // Separate any valid id character
                            if ((ord($test{$i}) >= 0x30 && ord($test{$i}) <= 0x39) ||
                                (ord($test{$i}) >= 0x61 && ord($test{$i}) <= 0x7A) ||
                                (ord($test{$i}) == 95) ||
                                (ord($test{$i}) == 45)) {
                                $id .= $test{$i};
                            }

                            // Checking end of the id string
                            if ($id) {
                                // Check for an string to be closed in the next character [>], [space], ["], [']
                                if ($test{$i + 1} == chr(62) ||
                                    $test{$i + 1} == chr(32) ||
                                    $test{$i + 1} == chr(34) ||
                                    $test{$i + 1} == chr(39)) {
                                    // Id found mark flag
                                    if (isset($contents[$id])) {
                                        $found = $contents[$id];
                                    }

                                    $id = '';
                                    $tag = 1;
                                }
                            }
                        }
                    } elseif ($test{$i - 1} == chr(105) && $test{$i} == chr(100)) {
                        // id found start testing
                        $tag = 2;
                    }
                }

                // Tag found <
                if ($test{$i - 1} == chr(60)) {
                    $tag = 1;
                }

                // End of a tag >
                if ($test{$i} == chr(62)) {
                    $id = '';
                    $tag = 0;

                    // Inserted content in the correct position
                    if ($found) {
                        $merged .= $found;
                        $found = '';
                    }
                }
            }

            $html = $merged;
        }

        return $html;
    }

    /**
     * This method check if the URL has any defined restriction in the global scope
     *
     * @param  array  $route      Route
     * @return string $restricted First restricted route from the most to less significative argument
     */
    public static function isRestricted(array $urlRoute = null)
    {
        global $restriction;

        $restricted = '';

        // Route he trying to access
        $access_route = (isset($urlRoute)) ? $urlRoute : self::$urlParam;

        // Check the access url against the restriction array definition in config.inc.php
        if (count($access_route)) {
            $route = '';

            foreach ($access_route as $k => $v) {
                // Check all route possibilities
                if ($route) {
                    $route .= '/';
                }
                $route .= $v;

                // Restriction exists for this route
                if (isset($restriction[$route])) {
                    $restricted = $route;
                }

                // Allowed by main configuration
                if (isset($restriction[$route]['permission']) && $restriction[$route]['permission'] == 1) {
                    $restricted = '';
                }
            }

            // Always allow login/logout method
            $param = $access_route[count($access_route) - 1];
            if ($param == 'login' || $param == 'logout') {
                $restricted = '';
            }
        }

        // If there is a restriction check the permission, this should be implemented by the login function

        if ($restricted) {
            if (isset($_SESSION['permission'])) {
                // Check if the user has access to the module
                $key = $restricted;
                if (isset($_SESSION['permission'][$key])) {
                    $restricted = '';
                }

                // Check if the user has access to a parent function, if defined
                if (isset($restriction[$restricted]['parent'])) {
                    $key = $restriction[$restricted]['parent'];
                    if (isset($_SESSION['permission'][$key])) {
                        $restricted = '';
                    }
                }
            }
        }

        return $restricted;
    }

    /**
     * Return domain name
     *
     * @return string $domain
     */
    public static function getDomain()
    {
        $domain = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : $_SERVER['SERVER_NAME'];

        return $domain;
    }

    /**
     * Return full url
     *
     * @return string
     */
    public static function getUrl()
    {
        return $_SERVER["HTTP_HOST"] . $_SERVER["SCRIPT_NAME"];
    }

    /**
     * Return full url
     *
     * @return string
     */
    public static function getLink($page = null)
    {
        $scheme = 'http';

        if (isset($_SERVER['REQUEST_SCHEME'])) {
            $scheme = $_SERVER['REQUEST_SCHEME'];
        }

        $script = $_SERVER["HTTP_HOST"] . $_SERVER["SCRIPT_NAME"];
        $url = $scheme . '://' . str_replace('index.php', '', $script);

        if (substr($url, - 1, 1) != '/') {
            $url .= '/';
        }

        $url .= $page;

        return $url;
    }

    /**
     * Print debug information
     *
     * @return string
     */
    public function debugMode()
    {
        echo "<h1>Bossanova Framework</h1>";
        echo "<p>Debug mode active</p>";
        echo '<pre>';
        echo '1 . Request<br>' . implode('/', self::$urlParam) . '<br><br>';
        echo '2 . Configuration loaded based on the request<br>';
        print_r(self::$configuration);
        print_r($trace = debug_backtrace());
    }

    /**
     * Embedded social network URL/user_login identifier
     *
     * @return void
     */
    private function getSocialNetworkNames()
    {
        if (strlen(self::$urlParam[0]) > 1) {
            // For default me refers to the logged user
            if (self::$urlParam[0] == "me" && isset($_SESSION['me'])) {
                self::$urlParam[0] = $_SESSION['me'];
            }

            // Locate route
            $realpath = self::$urlParam[0];

            // Check if the user exists
            $this->database->Table("users");
            if ($realpath > 0) {
                $this->database->Argument(1, "user_id", "$realpath");
            } else {
                $realpath = $this->database->Bind(strtolower($realpath));
                $this->database->Argument(1, "user_login", "$realpath");
            }

            $this->database->Select();
            $result = $this->database->Execute();

            if ($row = $this->database->fetch_assoc($result)) {
                self::$configuration['module_name'] = "Me";

                if (isset(self::$urlParam[1]) && $controller_name = self::$urlParam[1]) {
                    $controller_name = ucfirst(strtolower($controller_name));
                    if (file_exists("modules/Me/controllers/$controller_name.class.php")) {
                        self::$configuration['module_controller'] = $controller_name;
                    }
                }

                self::$configuration['template_path'] = "ue/index.html";
                self::$configuration['template_area'] = "default";
                self::$notFound = 0;
            }
        }
    }

    /**
     * Scape string
     *
     * @param  string
     * @return string
     */
    private function escape($str)
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
     * Get the configuration from config.inc.php
     *
     * return string $content
     */
    private function getConfig($key)
    {
        $vars = array('restriction', 'route', 'module');
        $rows = null;

        if (in_array($key, $vars)) {
            $rows = isset($GLOBALS[$key]) ? $GLOBALS[$key] : null;
        }

        return $rows;
    }
}
