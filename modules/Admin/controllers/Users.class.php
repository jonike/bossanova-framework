<?php

/**
 * (c) 2013 Bossanova PHP Framework
* http://www.bossanova-framework.com
*
* @author: Paul Hodel <paul.hodel@gmail.com>
* @description: User Admin Controller
*/
namespace modules\Admin\controllers;

use modules\Admin\Admin;

class Users extends Admin
{
    public function __construct()
    {
        $this->service = new \services\Users();

        parent::__construct();
    }

    /**
     * Select record
     */
    public function select()
    {
        $id = (int) $this->getParam(3);

        $data = $this->service->select($id);

        return $this->jsonEncode($data);
    }

    /**
     * Insert callback is the same for update.
     */
    public function insert($row = NULL)
    {
        $data = $this->service->insert($this->getPost());

        return $this->jsonEncode($data);
    }

    /**
     * Update user information
     */
    public function update($row = NULL)
    {
        $id = (int) $this->getParam(3);

        $data = $this->service->update($id, $this->getPost());

        return $this->jsonEncode($data);
    }

    /**
     * User Logical delete
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

    /**
     * Information about all users for the grid
     */
    public function dropdown()
    {
        $data = $this->service->getPermissions();

        return $this->jsonEncode($data);
    }
}
