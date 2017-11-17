<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Services
 */
namespace services;

class Permissions
{
    private $PermissionsModel = null;

    public function __construct()
    {
        $this->PermissionsModel = new \models\Permissions();
    }

    /**
     * Select
     *
     * @param  integer $user_id
     * @return array   $data
     */
    public function select($id)
    {
        $data = $this->PermissionsModel->select($id);

        return $data;
    }

    /**
     * Insert
     *
     * @param  array  $row
     * @return array  $data
     */
    public function insert($row)
    {
        $row['permission_routes'] = json_encode($row['permission_routes']);

        $data = $this->PermissionsModel->column($row)->insert();

        if ($data) {
            $data = [
                'data' => $data,
                'message' => '^^[Sucessfully saved]^^'
            ];
        } else {
            $data = [
                'error' => 1,
                'message' => '^^[It was not possilble to save this record]^^: ' . $this->PermissionsModel->getError()
            ];
        }

        return $data;
    }

    /**
     * Update
     *
     * @param  int $id
     * @param  array  $row
     * @return array  $data
     */
    public function update($id, $row)
    {
        $row['permission_routes'] = json_encode($row['permission_routes']);

        $data = $this->PermissionsModel->column($row)->update($id);

        if ($data) {
            $data = [
                'data' => $data,
                'message' => '^^[Sucessfully saved]^^'
            ];
        } else {
            $data = [
                'error' => 1,
                'message' => '^^[It was not possilble to save this record]^^: ' . $this->PermissionsModel->getError()
            ];
        }

        return $data;
    }

    /**
     * Delete
     *
     * @param  integer $user_id
     * @return array   $data
     */
    public function delete($id)
    {
        $data = $this->PermissionsModel->delete($id);

        if ($data) {
            $data = [
                'data' => $data,
                'message' => '^^[Sucessfully deleted]^^'
            ];
        } else {
            $data = [
                'error' => 1,
                'message' => '^^[It was not possilble to delete this record]^^: ' . $this->PermissionsModel->getError()
            ];
        }

        return $data;
    }

    public function grid()
    {
        $data = $this->PermissionsModel->grid();

        // Convert to grid
        $grid = new \services\Grid();
        $data = $grid->get($data);

        return $data;
    }

    /**
     * Get the permissions by permission_id
     * @param integer $id
     * @return array $permissions
     */
    public function getPermissionsById($id)
    {
        // Get restrictions
        $restriction = $this->PermissionsModel->getRestrictions();

        // Permissions container
        $permissions = [];

        // Load permission information for this permission_id
        $row = $this->PermissionsModel->getById((int)$id);

        if (isset($row['permission_id']) && $row['permission_id'] > 0) {
            // If the user_id is a superuser register all restrited routes as permited
            if (isset($row['global_user']) && $row['global_user'] == 1) {
                foreach ($restriction as $k => $v) {
                    $k = str_replace('-', '_', $k);
                    $permissions[$k] = 1;
                    $k = str_replace('_', '-', $k);
                    $permissions[$k] = 1;
                }
            } else {
                // All route permited for his permission_id
                if ($permission_routes = json_decode($row['permission_routes'], true)) {
                    foreach ($permission_routes as $k => $v) {
                        $k = str_replace('-', '_', $k);
                        $permissions[$k] = 1;
                        $k = str_replace('_', '-', $k);
                        $permissions[$k] = 1;
                    }

                    // All route permited defined in the config.inc.php
                    foreach ($restriction as $k => $v) {
                        if (isset($v['permission']) && $v['permission'] == 1) {
                            $k = str_replace('-', '_', $k);
                            $permissions[$k] = 1;
                            $k = str_replace('_', '-', $k);
                            $permissions[$k] = 1;
                        }
                    }
                }
            }
        }

        return $permissions;
    }

    public function isPermissionsSuperUser($id)
    {
        $row = $this->PermissionsModel->getById((int) $id);

        if (isset($row['permission_id'])) {
            $status = $row['global_user'] ? 1 : 0;
        }

        return $status;
    }
}
