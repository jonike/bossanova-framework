<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Model
 */
namespace models;

use Bossanova\Model\Model;

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
        $data = array();

        $this->database->table("users")
            ->column(array('user_status' => 0))
            ->argument(1, "user_id", $user_id)
            ->update()
            ->execute();

        return (! $this->database->error) ? true : false;
    }

    /**
     * Get user
     *
     * @param  string $user_email
     * @return array  $row
     */
    public function getUserByIdent($ident)
    {
        $ident = $this->database->bind(strtolower(trim($ident)));

        $result = $this->database->Table("users")
            ->argument(1, "lower(user_login) = lower($ident) or lower(user_email) = lower($ident) or lower(user_hash) = lower($ident)", "", "")
            ->select()
            ->execute();

        if ($row = $this->database->fetch_assoc($result)) {
            // Register user object
            $this->get($row['user_id']);
        }

        return $row;
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

    /**
     * Populate users grid
     *
     * @return json $data - list of users
     */
    public function grid()
    {
        // Selectd users
        $this->database->Table("users");
        $this->database->Column("user_id, user_name, user_status");
        $this->database->Argument(1, "user_status", 1);

        if (isset($_GET['value'])) {
            if ($_GET['value'] != '') {
                if ($_GET['column'] == 0) {
                    $this->database->Argument(2, "user_id", (int) $_GET['value']);
                } elseif ($_GET['column'] == 1) {
                    $this->database->Argument(2, "lower(user_name)", "lower('%{$_GET['value']}%')", "LIKE");
                } elseif ($_GET['column'] == 2) {
                    $this->database->Argument(1, "user_status", $_GET['value']);
                }
            }
        }

        $this->database->Select();
        $result = $this->database->Execute();

        return $result;
    }
}
