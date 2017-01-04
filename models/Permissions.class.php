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

class Permissions extends Model
{
    // Table configuration
    public $config = array(
        'tableName' => 'permissions',
        'primaryKey' => 'permission_id',
        'sequence' => 'permissions_permission_id_seq',
        'recordId' => 0
    );

    /**
     * Populate permission combo
     *
     * @return json $data - list of permissions
     */
    public function combo()
    {
        $data = array();

        // The user current permissios
        $permission_id = isset($_SESSION['permission_id']) ? $_SESSION['permission_id'] : 0;

        // Get the permission level
        $this->database->table("permissions");
        $this->database->column("permission_order");
        $this->database->argument(1, "permission_id", $permission_id);
        $this->database->select();
        $result = $this->database->execute();
        $row = $this->database->fetch_assoc($result);

        // Get only the permissions with the same level or a lower importance
        $this->database->Table("permissions");
        $this->database->column("permission_id, permission_name");
        $this->database->argument(1, "permission_status", 1);

        if ($row['permission_order'] > 0) {
            $this->database->argument(2, "permission_order", $row['permission_order'], ">=");
        } else {
            $this->database->argument(2, "permission_id", $permission_id);
        }

        $this->database->Order("permission_name");
        $this->database->select();
        $result = $this->database->execute();

        $i = 0;

        // Create the json
        while ($row = $this->database->fetch_assoc($result)) {
            $data[$i]['id'] = $row['permission_id'];
            $data[$i]['name'] = $row['permission_name'];

            $i ++;
        }

        return $data;
    }

    /**
     * Populate permission grid
     *
     * @return json $data List of permissions
     */
    public function grid()
    {
        $data = array();
        $page = isset($_GET['page']) && $_GET['page'] ? $_GET['page'] : 1;

        // Get all records by a search
        $this->database->table("permissions");
        $this->database->column("permission_id, permission_name, permission_status");
        $this->database->argument(1, "permission_status", 1);

        if (isset($_GET['value']) && $_GET['value'] != '') {
            $value = $this->database->bind("%{$_GET['value']}%");

            if ($_GET['column'] == 2) {
                $this->database->argument(1, "permission_status", (int) $_GET['value']);
            } elseif ($_GET['column'] == 1) {
                $this->database->argument(2, "lower(permission_name)", "lower($value)", "LIKE");
            }
        }

        $this->database->where();
        $this->database->order("permission_id");
        $this->database->select();
        $result = $this->database->execute();

        $i = 0;
        $j = 0;

        // Create the json
        while ($row = $this->database->fetch_assoc($result)) {
            if (($j >= ($page - 1) * 10) && ($j < ((($page - 1) * 10) + 10))) {
                $data['rows'][$i]['id'] = $row['permission_id'];
                $data['rows'][$i]['cell'] = $row;

                $i ++;
            }

            $j ++;
        }

        $data['page'] = $page;
        $data['total'] = (int) $j;

        return $data;
    }

    /**
     * Logical delete of a record
     *
     * @param integer $permission_id
     *            - permission_id
     * @return array $data - message
     */
    public function delete($id)
    {
        // Logical delete the record
        $this->database->table('permissions')
            ->column(array('permission_status' => 0))
            ->argument(1, 'permission_id', (int) $id)
            ->update()
            ->execute();

        // Message
        if (! $this->database->error) {
            $data['message'] = "^^[Successfully deleted]^^";
        } else {
            $data['error'] = 1;
            $data['message'] = "^^[It was not possible to delete this record]^^";
        }

        return $data;
    }

    /**
     * Update the routes from a permissions_id
     *
     * @param integer $permission_id
     */
    public function setRoutes($id)
    {
        // Delete and update route permissions
        $this->database->table("permissions_route")
            ->argument(1, "permission_id", $id)
            ->delete()
            ->execute();

        if (isset($_POST['route']) && count($_POST['route'])) {
            foreach ($_POST['route'] as $k => $v) {
                if ($v) {
                    // Re-include all routes
                    $column['permission_id'] = $id;
                    $column['route'] = "'$k'";

                    // URL the user can access
                    $this->database->table('permissions_route')
                        ->column($column)
                        ->insert()
                        ->execute();
                }
            }
        }
    }

    /**
     * Get all routes from a permission
     *
     * @param  integer $permission_id
     * @return array   $data
     */
    public function getRoutes($id)
    {
        $data = array();

        // Get the routes from a permission_id
        $result = $this->database->table('permissions_route')
            ->argument(1, 'permission_id', (int) $id)
            ->select()
            ->execute();

        // Create array
        while ($row = $this->database->fetch_assoc($result)) {
            $data[$row['route']] = true;
        }

        return $data;
    }

    public function isAllowedHierarchy($id)
    {
        if (! $id) {
            // Permission to be defined
            $bool = true;
        } else {
            if ($_SESSION['permission_id'] == $id) {
                $bool = true;
            } else {
                $result = $this->database->table("permissions")
                    ->column("permission_order")
                    ->argument(1, "permission_id", $_SESSION['permission_id'])
                    ->select()
                    ->execute();
                $row1 = $this->database->fetch_assoc($result);

                $result = $this->database->table("permissions")
                    ->column("permission_order")
                    ->argument(1, "permission_id", $id)
                    ->select()
                    ->execute();
                $row2 = $this->database->fetch_assoc($result);

                $bool = ($row1['permission_order'] > $row2['permission_order']) ? false : true;
            }
        }

        return $bool;
    }
}
