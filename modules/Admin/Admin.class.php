<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Admin module
 */
namespace modules\Admin;

use Bossanova\Module\Module;
use Bossanova\Database\Database;

class Admin extends Module
{
    // Control breadcrumb for the contents
    public $breadcrumb_position = 0;

    // Connect to the database and keep the resource available for all controllers
    public function __construct()
    {
        $this->query = Database::getInstance(null, array(
            DB_CONFIG_TYPE,
            DB_CONFIG_HOST,
            DB_CONFIG_USER,
            DB_CONFIG_PASS,
            DB_CONFIG_NAME
        ));

        // Force login
        if ($this->getIdent()) {
        }
    }

    /**
     * Quick search for all contents available in the admin module
     *
     * @return string $content All records found from a search
     */
    public function search()
    {
        $content = '';

        if (isset($_POST['q'])) {
            // Strip HTML tags
            $q = strip_tags($_POST['q']);

            // Get search result
            $nodes = new \models\Nodes;
            $content = $nodes->search($q, true);
        }

        return $content;
    }

    /**
     * Return all modules available in the application dir
     *
     * @return string $data All modules found in the application folder
     */
    public function modules()
    {
        // Keep all to be translated text references
        $data = array();

        $i = 0;

        // Search all folders reading all files
        if ($dh = opendir("modules")) {
            while (false !== ($file = readdir($dh))) {
                if (substr($file, 0, 1) != '.') {
                    if (is_dir('modules/' . $file)) {
                        $data[$i]['id'] = $file;
                        $data[$i]['name'] = $file;
                        $i ++;
                    }
                }
            }

            closedir($dh);
        }

        return $this->jsonEncode($data);
    }

    /**
     * Return all controllers available in the selected module
     *
     * @return string $data All controllers found in a given module folder
     */
    public function controllers()
    {
        // Keep all to be translated text references
        $data = array();

        // Module
        $file = ucfirst($this->getParam(2));

        $i = 0;

        // Search all folders reading all files
        if ($dh = opendir('modules/' . $file . '/controllers')) {
            while (false !== ($file = readdir($dh))) {
                if (substr($file, 0, 1) != '.') {
                    $data[$i]['id'] = substr($file, 0, - 10);
                    $data[$i]['name'] = substr($file, 0, - 10);

                    $i ++;
                }
            }

            closedir($dh);
        }

        return $this->jsonEncode($data);
    }

    /**
     * Return all methods available in the module or controllers
     *
     * @return string $data All methods found in a given module or controler
     */
    public function methods()
    {
        // Keep all to be translated text references
        $data = array();

        // Module
        $file = 'modules/' . ucfirst($this->getParam(2));

        // Controller
        if ($controller = ucfirst($this->getParam(3))) {
            $file .= '/controllers/' . $controller;
        } else {
            $file .= '/' . ucfirst($this->getParam(2));
        }

        // Extension
        $file .= '.class.php';

        $i = 0;

        // Load methods
        if (file_exists($file)) {
            $a = file_get_contents($file);

            preg_match_all('/public? function (.*?)\(\)/', $a, $b);

            foreach ($b[1] as $k => $v) {
                $v = trim($v);

                if (substr($v, 0, 2) != '__') {
                    $data[$i]['id'] = $v;
                    $data[$i]['name'] = $v;

                    $i++;
                }
            }
        }

        return $this->jsonEncode($data);
    }

    /**
     * Return all templates available in templates
     *
     * @return string $data All HTML files available
     */
    public function templates()
    {
        $data = array();

        $i = 0;

        // Format grid json data
        foreach ($this->templatesSearch('public/templates') as $k => $v) {
            $v = substr($v, 17);

            $data[$i]['id'] = $v;
            $data[$i]['name'] = $v;

            $i ++;
        }

        return $this->jsonEncode($data);
    }

    /**
     * Internal search for the template files
     *
     * @return array $templates - all templates found
     */
    public function templatesSearch($folder)
    {
        // Keep all to be translated text references
        $templates = array();

        // Search all folders reading all files
        if ($dh = opendir($folder)) {
            while (false !== ($file = readdir($dh))) {
                if (substr($file, 0, 1) != '.') {
                    if (is_dir($folder . '/' . $file)) {
                        if (($file != 'css') && ($file != 'js') && ($file != 'doc') && ($file != 'img')) {
                            $templates = array_merge($templates, $this->templatesSearch($folder . '/' . $file));
                        }
                    } else {
                        if (substr($file, - 4) == 'html') {
                            $templates[] = $folder . '/' . $file;
                        }
                    }
                }
            }

            closedir($dh);
        }

        return $templates;
    }

    /**
     * Tree for the content explorer
     *
     * @return string $json - tree of nodes
     */
    public function tree()
    {
        // Nodes
        $nodes = array(
            array(
                "id" => "0",
                "text" => "^^[Content]^^",
                "children" => $this->tree_nodes(0)
            ),
            array(
                "id" => "trash",
                "text" => "^^[Trash]^^",
                "icon" => "img/tree/trash.png"
            )
        );

        // Return json
        return $this->jsonEncode($nodes);
    }

    /**
     * Internal recursive tree data assembly
     */
    private function tree_nodes($parent_id = 0)
    {
        $nodes = array();

        // Open default locale
        $locale = DEFAULT_LOCALE;

        // Search for nodes
        $this->query->table("nodes n");
        $this->query->leftjoin("nodes_content c", "n.node_id = c.node_id AND c.locale = '{$locale}'");
        $this->query->column("n.node_id, COALESCE(n.parent_id, 0) AS parent_id, n.module_name, n.option_name, n.ordered, COALESCE(c.title, n.title) AS title");
        $this->query->argument(1, "COALESCE(n.parent_id, 0)", $parent_id);
        $this->query->argument(2, "n.status", 0, ">");
        $this->query->order("COALESCE(n.ordered,0), n.node_id");
        $this->query->select();
        $result = $this->query->execute();

        while ($row = $this->query->fetch_assoc($result)) {
            // Avoid overflow in the interface
            if (strlen($row['title']) > 25)
                $row['title'] = substr($row['title'], 0, 25) . ' ...';

                // Title
            $node = array(
                "id" => $row['node_id'],
                "text" => iconv('UTF-8', 'UTF-8//IGNORE', $row['title'])
            );

            // Tree icon
            if ($row['module_name']) {
                $icon = ($row['module_name'] == 'nodes') ? $row['option_name'] : $row['module_name'];

                $node['icon'] = "img/tree/$icon.png";
            }

            // Recursive search
            if ($child = $this->tree_nodes($row['node_id']))
                $node['children'] = $child;

            $nodes[] = $node;
        }

        return $nodes;
    }

    /**
     * All dictionary files in the resources/locales
     *
     * @return string $json - list of locales
     */
    public function locales()
    {
        // Keep all to be translated text references
        $data = array();

        // Default locale
        $locales = array(DEFAULT_LOCALE => '^^[Default]^^');

        // Module
        $file = ucfirst($this->getParam(2));

        $i = 0;

        // Search all folders reading all files
        if ($dh = opendir('resources/locales')) {
            while (false !== ($file = readdir($dh))) {
                // Get all dictionaries
                if (substr($file, - 4) == '.csv') {
                    if (! isset($locales[substr($file, 0, - 4)])) {
                        $locales[substr($file, 0, - 4)] = substr($file, 0, - 4);
                    }
                }
            }

            closedir($dh);
        }

        // Change for the correct format
        if ($locales) {
            foreach ($locales as $k => $v) {
                $data[$i]['id'] = $k;
                $data[$i]['name'] = $v;

                $i ++;
            }
        }

        return $this->jsonEncode($data);
    }

    /**
     * Admin menu
     *
     * @return string $json - admin menu
     */
    public function menu()
    {
        // Assembly menu based on permissions
        $menu = array();

        // Menu Administration
        $item = array();
        if ($this->getPermissions('admin/users'))
            $item[] = array(
                'title' => '^^[Users]^^',
                'id' => 'users',
                'tab' => 'admin/users'
            );
        if ($this->getPermissions('admin/permissions'))
            $item[] = array(
                'title' => '^^[Permissions]^^',
                'id' => 'permissions',
                'tab' => 'admin/permissions'
            );

        if (count($item) > 0)
            $menu[] = array(
                'title' => '^^[Administration]^^',
                'itens' => $item
            );

            // Return json
        return $this->jsonEncode($menu);
    }

    /**
     * Name of the user for the interface
     *
     * @return string $name - user name
     */
    public function name()
    {
        if (isset($_SESSION['user_id'])) {
            // User model
            $users = new \models\Users;

            // Get user by id
            if ($row = $users->getById($_SESSION['user_id'])) {
                $name = $row['user_name'];
            }
        }

        return isset($name) ? $name : null;
    }

    /**
     * Information about the user
     *
     * @return string $json - user profile
     */
    function info()
    {
        $row = array();

        if (isset($_SESSION['user_id'])) {
            // User model
            $users = new \models\Users();

            // Get user profile
            $row = $users->getProfile($_SESSION['user_id']);
        }

        return $this->jsonEncode($row);
    }

    /**
     * Keep a record for all actions in the admin module
     */
    public function __destruct()
    {
        if (isset($_SESSION['user_id'])) {
            if ($this->query) {
                if (class_exists('\\models\\Audit'))
                {
                    // Register log
                    $audit = new \models\Audit;
                    $audit->user_id = (int) $_SESSION['user_id'];
                    $audit->user_access_id = (int) $_SESSION['user_access_id'];
                    $audit->audit_date = "NOW()";
                    $audit->audit_action = implode(",", $this->getParam());
                    $audit->audit_description = $this->audit;
                    $audit->audit_json = str_replace("'", "", json_encode(array_merge($_GET, $_POST)));
                    $audit->module = $this->getParam(0);
                    $audit->save();
                }
            }
        }
    }
}
