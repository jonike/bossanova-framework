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
    public function __construct()
    {
        $this->service = new \services\Routes();

        parent::__construct();
    }

    /**
     * Get all information about one route
     *
     * @return string $json
     */
    public function select()
    {
        $route_id = $this->getParam(3);

        $data = $this->service->select($route_id);

        return $this->jsonEncode($data);
    }

    /**
     * Insert a new route
     *
     * @return string $json
     */
    public function insert($row = NULL)
    {
        $row = $this->getPost();

        $data = $this->service->insert($row);

        return $this->jsonEncode($data);
    }

    /**
     * Update a existing route
     *
     * @return string $json
     */
    public function update($row = NULL)
    {
        $id = (int) $this->getParam(3);

        $row = $this->getPost();

        $data = $this->service->update($id, $row);

        return $this->jsonEncode($data);
    }

    /**
     * Delete a existing route
     *
     * @return string $json
     */
    public function delete($row = NULL)
    {
        $id = (int) $this->getParam(3);

        $data = $this->service->delete($id);

        return $this->jsonEncode($data);
    }

    /**
     * Interface Grid with all routes defined so far
     *
     * @return string $json
     */
    public function grid()
    {
        $data = $this->service->grid();

        return $this->jsonEncode($data);
    }

    /**
     * Get all available modules
     *
     * @return string $json
     */
    public function modules()
    {
        $data = $this->service->getModules();

        return $this->jsonEncode($data);
    }

    /**
     * Get all available methods from a module or a controller
     *
     * @return string $json
     */
    public function methods()
    {
        $module = $this->getParam(3);
        $controller = $this->getParam(4);

        $data = $this->service->getMethodsByModule($module, $controller);

        return $this->jsonEncode($data);
    }

    /**
     * Get all available modules
     *
     * @return string $json
     */
    public function controllers()
    {
        $module = $this->getParam(3);

        $data = $this->service->getControllersByModule($module);

        return $this->jsonEncode($data);
    }

    /**
     * Get all available templates
     *
     * @return string $json
     */
    public function templates()
    {
        $data = $this->service->getTemplates();

        return $this->jsonEncode($data);
    }

    /**
     * Create a json for the template id combo.
     * Basically search for all HTML ids in the temmplate
     *
     * @return string $json
     */
    public function id()
    {
        $data = $this->service->getObjectIdsByTemplate($_GET['template']);

        return $this->jsonEncode($data);
    }
}

