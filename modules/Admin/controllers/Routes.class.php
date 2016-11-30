<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Routes Admin Controller
 */
namespace modules\Admin\controllers;

use modules\Admin\Admin;

class Routes extends Admin
{
    /**
     * Interface Grid with all routes defined so far
     *
     * @return string $json
     */
    public function grid()
    {
        $routes = new \models\Routes();
        $data = $routes->grid();

        return $this->jsonEncode($data);
    }

    /**
     * Insert a new route
     *
     * @return string $json
     */
    public function insert($row = NULL)
    {
        $extra_config = array();

        if (isset($_POST['extra_config']['node_id']) && count($_POST['extra_config']['node_id'])) {
            foreach ($_POST['extra_config']['node_id'] as $k => $v) {
                $extra_config[$k]['node_id'] = $_POST['extra_config']['node_id'][$k];
                $extra_config[$k]['template_area'] = $_POST['extra_config']['template_area'][$k];
                $extra_config[$k]['module_name'] = $_POST['extra_config']['module_name'][$k];
                $extra_config[$k]['controller_name'] = $_POST['extra_config']['controller_name'][$k];
                $extra_config[$k]['method_name'] = $_POST['extra_config']['method_name'][$k];
            }
        }

        $_POST['extra_config'] = json_encode($extra_config);

        return parent::insert($row);
    }

    /**
     * Update a existing route
     *
     * @return string $json
     */
    public function update($row = NULL)
    {
        $extra_config = array();

        if (isset($_POST['extra_config']['node_id']) && count($_POST['extra_config']['node_id'])) {
            foreach ($_POST['extra_config']['node_id'] as $k => $v) {
                $extra_config[$k]['node_id'] = $_POST['extra_config']['node_id'][$k];
                $extra_config[$k]['template_area'] = $_POST['extra_config']['template_area'][$k];
                $extra_config[$k]['module_name'] = $_POST['extra_config']['module_name'][$k];
                $extra_config[$k]['controller_name'] = $_POST['extra_config']['controller_name'][$k];
                $extra_config[$k]['method_name'] = $_POST['extra_config']['method_name'][$k];
            }
        }

        $_POST['extra_config'] = json_encode($extra_config);

        return parent::update($row);
    }

    /**
     * Create a json for the template id combo.
     * Basically search for all HTML ids in the temmplate
     *
     * @return string $json
     */
    public function id()
    {
        $data = array();

        $template = $_GET['template'];
        if (file_exists("public/templates/" . $template)) {
            $template = file_get_contents("public/templates/" . $template);
            preg_match_all("/<(.*)id=[\"'](.*?)[\"'](.*)>/", $template, $test);

            // Format grid json data
            $i = 0;
            foreach ($test[2] as $k => $v) {
                $data[$i]['id'] = $v;
                $data[$i]['name'] = $v;

                $i ++;
            }
        }

        return $this->jsonEncode($data);
    }

    /**
     * Get all information about one route
     *
     * @return string $json
     */
    public function select()
    {
        $route_id = $this->getParam(3);

        $nodes = new \models\Nodes();
        $routes = new \models\Routes();

        if (isset($_GET['route']) && $_GET['route']) {
            $row = $routes->getByRoute($_GET['route']);
        } else {
            $row = $routes->getById($route_id);
        }

        if ($row['extra_config']) {
            $extra_config = json_decode($row['extra_config']);

            foreach ($extra_config as $k => $v) {
                if ($extra_config[$k]->node_id) {
                    $node = $nodes->getById($extra_config[$k]->node_id);
                    $extra_config[$k]->title = $node['title'];
                }
            }

            $row['extra_config'] = $extra_config;
        }

        return $this->jsonEncode($row);
    }
}

