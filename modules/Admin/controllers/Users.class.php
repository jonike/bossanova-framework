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
    /**
     * Information about all users for the grid
     */
    public function grid()
    {
        $users = new \models\Users();
        $data = $users->grid();

        return $this->jsonEncode($data);
    }

    /**
     * Insert callback is the same for update.
     */
    public function insert($row = NULL)
    {
        // Get post variables
        $row = $this->getPost();

        // Password
        if (isset($row['user_password']) && ! $row['user_password']) {
            unset($row['user_password']);
        } else {
            // Update password information
            $salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
            $pass = hash('sha512', hash('sha512', $row['user_password']) . $salt);
            $row['user_salt'] = $salt;
            $row['user_password'] = $pass;
        }

        // Update user informartion
        return parent::insert($row);
    }

    /**
     * Update user information
     */
    public function update($row = NULL)
    {
        // Get post variables
        $row = $this->getPost();

        // Password
        if (isset($row['user_password']) && ! $row['user_password']) {
            unset($row['user_password']);
        } else {
            // Update password information
            $salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
            $pass = hash('sha512', hash('sha512', $row['user_password']) . $salt);
            $row['user_salt'] = $salt;
            $row['user_password'] = $pass;
        }

        // Update user informartion
        return parent::update($row);
    }

    /**
     * User Logical delete
     */
    public function delete()
    {
        // Get the id
        $id = (int) $this->getParam(3);

        // Logical delete
        $user = new \models\Users();
        $data = $user->delete($id);

        return $this->jsonEncode($data);
    }
}
