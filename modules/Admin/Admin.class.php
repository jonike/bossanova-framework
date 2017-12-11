<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Admin module
 */
namespace modules\Admin;

use bossanova\Module\Module;
use bossanova\Database\Database;

class Admin extends Module
{
    protected $nativeMethods = false;

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
            // Set layout
            $this->setLayout('default/admin.html');

            // Admin menu
            $area = new \stdClass;
            $area->template_area = 'menu';
            $area->module_name = 'admin';
            $area->method_name = 'menu';
            $this->setContent($area);

            if ($this->getPermission('nodes/edition')) {
                $area = new \stdClass;
                $area->template_area = 'tree';
                $area->module_name = 'nodes';
                $area->controller_name = 'edition';
                $area->method_name = 'tree';
                $this->setContent($area);
            }
        }
    }

    public function menu()
    {
        $html = '';

        if ($this->getPermission('admin/users')) {
            $html .= '<li><a href="/admin/users">Users</a></li>';
        }

        if ($this->getPermission('admin/permissions')) {
            $html .= '<li><a href="/admin/permissions">Permissions</a></li>';
        }

        if ($this->getPermission('admin/routes')) {
            $html .= '<li><a href="/admin/routes">Routes</a></li>';
        }

        if ($this->getPermission('nodes/edition')) {
            $html .= '<li><a href="/nodes/edition">Content</a></li>';
        }

        $html .= '<li><a href="/nodes/logout">Logout</a></li>';

        return "<ul>$html</ul>";
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
