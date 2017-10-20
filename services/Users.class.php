<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Services
 */
namespace services;

use models\Users;
use models\Permissions;

class Users
{
    private $userModel = null;
    private $PermissionsModel = null;

    public function __construct()
    {
        $this->userModel = new Users();
        $this->PermissionsModel = new Permissions();
    }

    /**
     * Select
     *
     * @param  integer $user_id
     * @return array   $data
     */
    public function select($id)
    {
        $data = $this->userModel->getById($id);

        if ((int)$id > 0) {
            if (count($data) > 0) {
                if (! $this->PermissionsModel->isAllowedHierarchy($data['permission_id'])) {
                    $data = [ 'error' => 1, 'message' => '^^[Permission denied]^^' ];
                }
            } else {
                $data = [ 'error' => 1, 'message' => '^^[No record found]^^' ];
            }
        }

        return $data;
    }

    /**
     * Insert
     *
     * @param  array  $data
     * @return array  $data
     */
    public function insert($row)
    {
        // Permission is a mandatory field
        if ($row['permission_id']) {
            if (! $this->PermissionsModel->isAllowedHierarchy($row['permission_id'])) {
                $data = [
                    'error' => 1,
                    'message' => '^^[Permission denied]^^'
                ];
            } else {
                // Generate user Password
                $salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
                $generated = substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyz", 6)), 0, 6);
                $pass = hash('sha512', hash('sha512', $generated) . $salt);
                $row['user_salt'] = $salt;
                $row['user_password'] = $pass;

                // Add a new record
                $result = $this->userModel->column($row)->insert();

                $data = [
                    'data' => $result,
                    'message' => '^^[Successfully saved]^^'
                ];
            }
        } else {
            $data = [
                'error' => 1,
                'message' => '^^[Permission is a mandatory field]^^'
            ];
        }

        return $data;
    }

    /**
     * Update
     *
     * @param  array  $data
     * @return array  $data
     */
    public function update($id, $row)
    {
        $data = $this->userModel->getById($id);

        if (count($data) > 0) {
            if (! $this->PermissionsModel->isAllowedHierarchy($data['permission_id'])) {
                $data = [ 'error' => 1, 'message' => '^^[Permission denied]^^' ];
            } else {
                $result = $this->userModel->column($row)->update($id);
                $data = [ 'data' => $result, 'message' => '^^[Successfully saved]^^' ];
            }
        } else {
            $data = [ 'error' => 1, 'message' => '^^[No record found]^^' ];
        }

        return $data;
    }

    /**
     * Logical delete a user based on the user_id
     *
     * @param  integer $user_id
     * @return array   $data
     */
    public function delete($user_id)
    {
        $data = $this->userModel->getById($user_id);

        if (count($data) > 0) {
            if (! $this->PermissionsModel->isAllowedHierarchy($data['permission_id'])) {
                $data = [ 'error' => 1, 'message' => '^^[Permission denied]^^' ];
            } else {
                $this->userModel->delete($user_id);
                $data = [ 'success' => 1, 'message' => '^^[Successfully deleted]^^' ];
            }
        } else {
            $data = [ 'error' => 1, 'message' => '^^[No record found]^^' ];
        }

        return $data;
    }

    /**
     * Populate users grid
     *
     * @return json $data - list of users
     */
    public function grid()
    {
        $data = $this->userModel->UsersWithPermissions();
        return (count($data) > 0) ? $data : [];
    }

    /**
     * Select Permissions
     *
     * @param  integer $user_id
     * @return array   $data
     */
    public function selectPermissions($id = null)
    {
        if ($id) {
            $permissions = $this->PermissionsModel->select($id);
        } else {
            $permissions = $this->PermissionsModel->combo();
        }

        return count($permissions) > 0 ? $permissions : [ 'error' => 1, 'message' => '^^[No record found]^^' ];
    }
}
