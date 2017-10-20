<?php

/**
 * (c) 2013 Bossanova PHP Framework
* http://www.bossanova-framework.com
*
* @author: Paul Hodel <paul.hodel@gmail.com>
* @description: Model
*/
namespace models;

use bossanova\Model\Model;

class Users extends Model
{
    // Table configuration
    public $config = array(
        'tableName' => 'users',
        'primaryKey' => 'user_id',
        'sequence' => 'users_user_id_seq',
        'recordId' => 0
    );

    /**
     * Logical delete a user based on the user_id
     *
     * @param  integer $user_id
     * @return array   $data
     */
    public function delete($user_id)
    {
        $this->database->table("users")
            ->column(array('user_status' => 0))
            ->argument(1, "user_id", $user_id)
            ->update()
            ->execute();

        if ($this->database->error) {
            $this->setError($this->database->error);
        }

        return (! $this->database->error) ? true : false;
    }

    /**
     * Get user by login
     *
     * @param  string $user_login
     * @return array  $row
     */
    public function getByLogin($user_login)
    {
        $user_login = $this->database->bind(trim(strtolower($user_login)));

        $result = $this->database->table("users")
            ->column("user_name, user_login")
            ->argument(1, "lower(user_login)", $user_login)
            ->select()
            ->execute();

        return $this->database->fetch_assoc($result);
    }

    /**
     * Get user by email
     *
     * @param  string $user_email
     * @return array  $row
     */
    public function getByEmail($user_email)
    {
        $user_email = $this->database->bind(trim(strtolower($user_email)));

        $result = $this->database->Table("users")
            ->column("user_id")
            ->argument(1, "lower(user_email)", $user_email)
            ->select()
            ->execute();

        return $this->database->fetch_assoc($result);
    }

    /**
     * Update the password of a user based on a user_id
     *
     * @param integer $user_id
     * @return void
     */
    public function setPassword($user_id, $password)
    {
        if (isset($password) && $password) {
            // Update user password
            $salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
            $pass = hash('sha512', hash('sha512', $password) . $salt);

            // Columns
            $column = array();
            $column['user_salt'] = "'$salt'";
            $column['user_password'] = "'$pass'";

            $this->database->table("users")
                ->column($column)
                ->argument(1, "user_id", $user_id)
                ->update()
                ->execute();
        }
    }
}
