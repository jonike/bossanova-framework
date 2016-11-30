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
     * Select
     *
     * @param  integer $user_id
     * @return array   $data
     */
    public function select($id)
    {
        $result = $this->database->table("users")
            ->argument(1, "user_id", $id)
            ->select()
            ->execute();

        if ($data = $this->database->fetch_assoc($result)) {
            $permission = new \models\Permissions();
            if (! $permission->isAllowedHierarchy($data['permission_id'])) {
                $data = array('error' => 1, 'message' => '^^[Permission denied]^^');
            }
        } else {
            $data = array('error' => 1, 'message' => '^^[No record found]^^');
        }

        return $data;
    }


    /**
     * Insert
     *
     * @param  integer $user_id
     * @return array   $data
     */
    public function insert()
    {
        // Permission
        $permission_id = isset($_POST['permission_id']) ? $_POST['permission_id'] : 0;

        // Checkings
        $user_email = $this->database->bind(strtolower(trim($_POST['user_email'])));

        $result = $this->database->table("users")
            ->argument(1, "lower(user_email)", $user_email)
            ->select()
            ->execute();

        if ($data = $this->database->fetch_assoc($result)) {
            $data = array('error' => 1, 'message' => '^^[This email is already in registered]^^');
        } else {
            $permission = new \models\Permissions();
            if (! $permission->isAllowedHierarchy($_POST['permission_id'])) {
                $data = array('error' => 1, 'message' => '^^[Permission denied]^^');
            } else {
                // Columns to be saved
                $row = $this->config->column;

                // Permission
                if (isset($permission_id) && $permission_id) {
                    $row['permission_id'] = $permission_id;
                }

                $this->database->begin();

                $this->database->table('users')
                    ->column($row)
                    ->insert()
                    ->execute();

                $id = $this->database->insert_id('users_user_id_seq');

                if (! $this->database->error) {
                    $data = array('message' => "^^[Successfully saved]^^");
                    $this->database->commit();
                } else {
                    $data = array('error' => 1, 'message' => '^^[It was not possible to save this record]^^');
                    $this->database->rollBack();
                }
            }
        }

        return $data;
    }

    /**
     * Update
     *
     * @param  integer $user_id
     * @return array   $data
     */
    public function update($id)
    {
        // Permission
        $permission_id = isset($_POST['permission_id']) ? $_POST['permission_id'] : 0;

        // Select user
        $result = $this->database->table("users")
            ->argument(1, "user_id", $id)
            ->select()
            ->execute();

        // Check if the user has permission to change users from this client
        if ($data = $this->database->fetch_assoc($result)) {
            // Check if this emails is already registered for another user
            $user_email = $this->database->bind(strtolower(trim($_POST['user_email'])));

            $result = $this->database->table("users")
                ->argument(1, "user_id", $id, "!=")
                ->argument(2, "lower(user_email)", $user_email)
                ->select()
                ->execute();

            if ($data = $this->database->fetch_assoc($result)) {
                $data = array('error' => 1, 'message' => '^^[This email is already in registered]^^');
            } else {
                // Check the user has permission to change this user
                $permission = new \models\Permissions();

                if (! $permission->isAllowedHierarchy($permission_id)) {
                    $data = array('error' => 1, 'message' => '^^[Permission denied]^^');
                } else {
                    // Data
                    $row = $this->config->column;

                    // Permission
                    if (isset($permission_id) && $permission_id) {
                        $row['permission_id'] = $permission_id;
                    }

                    $this->database->begin();

                    $this->database->table('users')
                        ->column($row)
                        ->argument(1, 'user_id', $id)
                        ->update()
                        ->execute();

                    if (! $this->database->error) {
                        $data = array('message' => "^^[Successfully saved]^^");
                        $this->database->commit();
                    } else {
                        $data = array('error' => 1, 'message' => '^^[It was not possible to save this record]^^');
                        $this->database->rollBack();
                    }
                }
            }
        } else {
            $data = array('error' => 1, 'message' => '^^[No record found]^^');
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
        $data = array();

        $this->database->table("users")
            ->column(array('user_status' => 0))
            ->argument(1, "user_id", $user_id)
            ->update()
            ->execute();

        if (! $this->database->error) {
            $data['message'] = "^^[Successfully deleted]^^";
        } else {
            $data['error'] = 1;
            $data['message'] = "^^[It was not possible delete this record]^^\n";
        }

        return $data;
    }

    /**
     * Get user by login
     *
     * @param  string $user_login
     * @return array  $row
     */
    public function getByLogin($user_login)
    {
        $user_login = $this->database->bind($user_login);

        $result = $this->database->table("users")
            ->column("user_name, user_login")
            ->argument(1, "user_login", $user_login)
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
        $user_email = $this->database->bind(trim($user_email));

        $result = $this->database->Table("users")
            ->column("user_id")
            ->argument(1, "user_email", $user_email)
            ->select()
            ->execute();

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
        $this->database->table("users");
        $this->database->column("user_id, user_name, user_status");
        $this->database->argument(1, "user_status", 1);

        if (isset($_GET['value'])) {
            if ($_GET['value'] != '') {
                if ($_GET['column'] == 0) {
                    $this->database->argument(2, "user_id", (int) $_GET['value']);
                } elseif ($_GET['column'] == 1) {
                    $this->database->argument(2, "lower(user_name)", "lower('%{$_GET['value']}%')", "LIKE");
                } elseif ($_GET['column'] == 2) {
                    $this->database->argument(1, "user_status", $_GET['value']);
                }
            }
        }

        $this->database->select();
        $result = $this->database->execute();

        return $this->gridFormat($result);
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
        $row = $this->database->bind($row);

        // Last update
        $row['user_updated'] = "NOW()";

        if (isset($user_id)) {
            // Update table
            $this->database->table("users")
                ->column($row)
                ->argument(1, "user_id", $user_id)
                ->update()
                ->execute();
        } else {
            // Hash
            $hash = md5(uniqid(mt_rand(), true));

            // New record
            $row['user_inserted'] = "NOW()";
            $row['user_hash'] = "'$hash'";
            $row['user_status'] = 2;

            // Add the user
            $this->database->table("users")
                ->column($row)
                ->insert()
                ->execute();

            // Get the userid
            $data['user_id'] = $this->database->insert_id('users_user_id_seq');

            // Make the hash available
            $data['user_hash'] = $hash;
        }

        if (! $this->database->error) {
            $data['message'] = '^^[Successfully saved]^^';
        } else {
            $data = array('error' => '1', 'message' => '^^[It was not possible to save]^^');

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
        $result = $this->database->table("users")
            ->column("user_name, user_email, user_facebook, user_location, user_locale")
            ->argument(1, "user_id", $user_id)
            ->select()
            ->execute();

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

            $this->database->table("users")
                ->column($column)
                ->argument(1, "user_id", $user_id)
                ->update()
                ->execute();
        }
    }
}
