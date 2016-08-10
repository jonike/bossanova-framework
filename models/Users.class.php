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
     * Get user by login
     *
     * @param  string $user_login
     * @return array  $row
     */
    public function getByLogin($user_login)
    {
        $user_login = $this->database->bind($user_login);
        $this->database->Table("users");
        $this->database->Column("user_name, user_login");
        $this->database->Argument(1, "user_login", $user_login);
        $this->database->Select();
        $result = $this->database->Execute();
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
        $user_email = $this->database->bind(trim($user_email));
        $this->database->Table("users");
        $this->database->Column("user_id");
        $this->database->Argument(1, "user_email", $user_email);
        $this->database->Select();
        $result = $this->database->Execute();
        return $this->database->fetch_assoc($result);
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

        return $this->gridFormat($result);
    }

    /**
     * Logical delete a user based on the user_id
     *
     * @param  integer $user_id
     * @return array   $data
     */
    public function delete($user_id)
    {
        $data = array();
        $this->database->Table("users");
        $this->database->Column(array('user_status' => 0));
        $this->database->Argument(1, "user_id", $user_id);
        $this->database->Update();
        $this->database->Execute();

        if (! $this->database->error) {
            $data['message'] = "^^[Successfully deleted]^^";
        } else {
            $data['error'] = 1;
            $data['message'] = "^^[It was not possible delete this record]^^\n";
        }

        return $data;
    }

    /**
     * Update user profile
     *
     * @param integer $user_id
     * @return array $data
     */
    public function setProfile($row, $user_id = null)
    {
        $data = array();

        // Password information
        if (isset($row['user_password']) && $row['user_password']) {
            // Keep old version compatibility
            $salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
            $pass = hash('sha512', hash('sha512', $row['user_password']) . $salt);
            $row['user_salt'] = $salt;
            $row['user_password'] = $pass;
        }

        // Filter data
        $row = $this->database->Bind($row);

        // Last update
        $row['user_updated'] = "NOW()";

        if (isset($user_id)) {
            // Update table
            $this->database->Table("users");
            $this->database->Column($row);
            $this->database->Argument(1, "user_id", $user_id);
            $this->database->Update();
            $this->database->Execute();
        } else {
            // Hash
            $hash = md5(uniqid(mt_rand(), true));

            // New record
            $row['user_inserted'] = "NOW()";
            $row['user_hash'] = "'$hash'";
            $row['user_status'] = 2;

            // Add the user
            $this->database->Table("users");
            $this->database->Column($row);
            $this->database->Insert();
            $this->database->Execute();

            // Get the userid
            $data['user_id'] = $this->database->insert_id('users_user_id_seq');

            // Make the hash available
            $data['user_hash'] = $hash;
        }

        if ($this->database->error) {
            $data = array(
                'error' => '1',
                'message' => '^^[It was not possible to save]^^'
            );
        } else {
            $data['message'] = '^^[Successfully saved]^^';
        }

        return $data;
    }

    /**
     * Get the user profile information including permissions
     *
     * @param  integer $user_id
     * @return array   $row
     */
    public function getProfile($user_id)
    {
        // Query user by id
        $this->database->Table("users");
        $this->database->Column("user_name, user_email, user_facebook, user_location, user_locale");
        $this->database->Argument(1, "user_id", $user_id);
        $this->database->Where();
        $this->database->Select();
        $result = $this->database->Execute();

        if ($row = $this->database->fetch_assoc($result)) {
            // Get only first and last name
            $name = explode(" ", $row['user_name']);
            $row['user_name'] = $name[0];
            if (count($name) > 1) {
                $row['user_name'] .= ' ' . $name[count($name) - 1];
            }

            // Get user permissions
            if (isset($_SESSION['permission']) && count($_SESSION['permission'])) {
                foreach ($_SESSION['permission'] as $k => $v) {
                    $k = "permission_" . str_replace("/", "_", $k);
                    $row['permission'][$k] = 1;
                }
            }
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

            $this->database->Table("users");
            $this->database->Column($column);
            $this->database->Argument(1, "user_id", $user_id);
            $this->database->Update();
            $this->database->Execute();
        }
    }
}
