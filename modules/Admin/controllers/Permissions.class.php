<?php

/**
 * (c) 2013 Bossanova PHP Framework
* http://www.bossanova-framework.com
*
* @author: Paul Hodel <paul.hodel@gmail.com>
* @description: Permissions Admin Controller
*/
namespace modules\Admin\controllers;

use modules\Admin\Admin;

class Permissions extends Admin
{
    public function __construct()
    {
        $this->service = new \services\Permissions();

        parent::__construct();
    }

    /**
     * Add a new permission
     */
    public function select($row = NULL)
    {
        $id = (int) $this->getParam(3);

        $data = $this->service->select($id);

        return $this->jsonEncode($data);
    }

    /**
     * Add a new permission
     */
    public function insert($row = NULL)
    {
        $data = $this->service->insert($this->getPost());

        return $this->jsonEncode($data);
    }

    /**
     * Insert callback is the same for update.
     */
    public function update($row = NULL)
    {
        $id = (int) $this->getParam(3);

        $data = $this->service->update($id, $this->getPost());

        return $this->jsonEncode($data);
    }

    /**
     * Add a new permission
     */
    public function delete()
    {
        $id = (int) $this->getParam(3);

        $data = $this->service->delete($id);

        return $this->jsonEncode($data);
    }

    /**
     * Information about all users for the grid
     */
    public function grid()
    {
        $data = $this->service->grid();

        return $this->jsonEncode($data);
    }
}
