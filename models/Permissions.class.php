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
        $this->database->Table("permissions");
        $this->database->Column("permission_order");
        $this->database->Argument(1, "permission_id", $permission_id);
        $this->database->Select();
        $result = $this->database->Execute();
        $row = $this->database->fetch_assoc($result);

        // Get only the permissions with the same level or a lower importance
        $this->database->Table("permissions");
        $this->database->Column("permission_id, permission_description");
        $this->database->Argument(1, "permission_status", 1);

        if ($row['permission_order'] > 0) {
            $this->database->Argument(2, "permission_order", $row['permission_order'], ">=");
        } else {
            $this->database->Argument(2, "permission_id", $permission_id);
        }

        $this->database->Order("permission_description");
        $this->database->Select();
        $result = $this->database->Execute();

        $i = 0;

        // Create the json
        while ($row = $this->database->fetch_assoc($result)) {
            $data[$i]['id'] = $row['permission_id'];
            $data[$i]['name'] = $row['permission_description'];

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
        $this->database->Table("permissions");
        $this->database->Column("permission_id, permission_description, permission_status");
        $this->database->Argument(1, "permission_status", 1);

        if (isset($_GET['value']) && $_GET['value'] != '') {
            $value = $this->database->bind("%{$_GET['value']}%");

            if ($_GET['column'] == 2) {
                $this->database->Argument(1, "permission_status", (int) $_GET['value']);
            } elseif ($_GET['column'] == 1) {
                $this->database->Argument(2, "lower(permission_description)", "lower($value)", "LIKE");
            }
        }

        $this->database->Where();
        $this->database->Order("permission_id");
        $this->database->Select();
        $result = $this->database->Execute();

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
    public function delete($permission_id)
    {
        $data = array();

        // Logical delete the record
        $this->database->Table("permissions");
        $this->database->Column(array("permission_status" => "0"));
        $this->database->Argument(1, "permission_id", (int) $permission_id);
        $this->database->Update();
        $this->database->Execute();

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
    public function setRoutes($permission_id)
    {
        if (isset($_POST['route']) && count($_POST['route'])) {
            // Delete and update route permissions
            $this->database->Table("permissions_route");
            $this->database->Argument(1, "permission_id", (int) $permission_id);
            $this->database->Where();
            $this->database->Delete();
            $this->database->Execute();

            foreach ($_POST['route'] as $k => $v) {
                if ($v) {
                    // Re-include all routes
                    $column1['permission_id'] = $permission_id;
                    $column1['route'] = "'$k'";

                    // URL the user can access
                    $this->database->Table("permissions_route");
                    $this->database->Column($column1);
                    $this->database->Insert();
                    $this->database->Execute();
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
    public function getRoutes($permission_id)
    {
        $data = array();

        // Get the routes from a permission_id
        $this->database->Table("permissions_route");
        $this->database->Argument(1, "permission_id", "{$permission_id}");
        $this->database->Where();
        $this->database->Select();
        $result = $this->database->Execute();

        // Create array
        while ($row = $this->database->fetch_assoc($result)) {
            $data[$row['route']] = true;
        }

        return $data;
    }
}
