<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Services
 */
namespace services;

use bossanova\Database\Database;

class Rest
{
    public $database = null;

    public function __construct(Database $instance = null)
    {
        if (isset($instance)) {
            $this->database = $instance;
        } else {
            $this->database = Database::getInstance();
        }

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
            $table = $this->getParam(0);
            $action = $this->getParam(0);
        }

        // Table is the name of the first argument in the URL (module name in application)
        $this->table = $this->escape($table);

        // Proccess the RESTful requisition
        if ($_SERVER['REQUEST_METHOD'] == "POST" || $_SERVER['REQUEST_METHOD'] == "PUT") {
            // Find by id
            if ($id > 0) {
                // No restriction defined or restriction defined but permission defined as well
                if ($this->getPermission("$action/update")) {
                    $data = $this->update($id, $_POST);
                } else {
                    $data = [ 'message' => '^^[Permission denied]^^' ];
                }
            } else {
                // No restriction defined or restriction defined but permission defined as well
                if ($this->getPermission("$action/insert")) {
                    $data = $this->insert($_POST);
                } else {
                    $data = [ 'message' => '^^[Permission denied]^^' ];
                }
            }
        } elseif ($_SERVER['REQUEST_METHOD'] == "DELETE") {
            if ($this->getPermission("$action/delete")) {
                if ($id > 0) {
                    $data = $this->delete($id);
                }
            } else {
                $data = [ 'message' => '^^[Permission denied]^^' ];
            }
        } elseif ($_SERVER['REQUEST_METHOD'] == "GET") {
            if ($this->getPermission("$action/select")) {
                if ($id > 0) {
                    $data = $this->select($id);
                }
            } else {
                $data = [ 'message' => '^^[Permission denied]^^' ];
            }
        }

        return $data;
    }

    public function select($id)
    {
        // Runtime model
        $model = $this->query->model($this->table);

        if ($model) {
            $model->select($id);
        }
    }

    public function update($id, $row)
    {
        // Saving data
        $model = $this->query->model($this->table);

        if ($model) {
            $model->column($row)->update($id);

            if ($this->query->error) {
                $data = [ 'error' => 1, 'message' => '^^[It was not possible to save your record]^^' ];
            } else {
                $data = [ 'message' => '^^[Successfully saved]^^' ];
            }
        }
    }

    public function delete($id)
    {
        // Runtime model
        $model = $this->query->model($this->table);

        if ($model) {
            $model->delete($id);
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
     * Get the registered permission_id
     *
     * @return integer $permission_id
     */
    public function getPermission($url)
    {
        $url = explode('/', $url);

        return (Render::isRestricted($url)) ? false : true;
    }
}
