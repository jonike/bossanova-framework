<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Permissions Admin Controller
 */
namespace modules\Admin\controllers;

use modules\Admin\Admin;

class Permissions extends Admin
{
    /**
     * Return all permissions available to populate the grid interface
     *
     * @return string $json
     */
    public function grid()
    {
        $permissions = new \models\Permissions();
        $data = $permissions->grid();

        return $this->jsonEncode($data);
    }

    /**
     * Return permissions list to populate the interface
     *
     * @return string $json
     */
    public function combo()
    {
        $permissions = new \models\Permissions();
        $data = $permissions->combo();

        return $this->jsonEncode($data);
    }

    /**
     * Insert specific columns in the table permissions
     *
     * @return string $json
     */
    public function insert($row = NULL)
    {
        $row = $this->getPost(array(
            'permission_description',
            'permission_status',
            'global_user',
            'permission_order'
        ));

        return parent::insert($row);
    }

    /**
     * Insert/update callback
     *
     * @return string $json
     */
    public function insert_callback($id)
    {
        $this->update_callback($id);
    }

    /**
     * Update a permission record
     *
     * @return string $json
     */
    public function update($row = NULL)
    {
        $row = $this->getPost(array(
            'permission_description',
            'permission_status',
            'global_user',
            'permission_order'
        ));

        return parent::update($row);
    }

    /**
     * Insert/update callback
     *
     * @return string $json
     */
    public function update_callback($id)
    {
        $permissions = new \models\Permissions();
        $permissions->setRoutes($id);
    }

    /**
     * Delete a permission
     *
     * @return string $json
     */
    public function delete()
    {
        // Get ID
        $id = $this->getParam(3);

        // Logical delete
        $permissions = new \models\Permissions();
        $data = $permissions->delete($id);

        // return message
        return $this->jsonEncode($data);
    }

    /**
     * Show available restricted URL defined in the config.inc.php file array $restriction
     *
     * @return string $html
     */
    public function actions()
    {
        // Array of restrictions in the config.inc.php
        global $restriction;

        $permissao = '';

        // Reorder before send to the interface
        ksort($restriction);

        if (count($restriction)) {
            $relation = array();

            // Create relationsn between the permissions
            if ($id = $this->getParam(3)) {
                $permissions = new \models\Permissions();
                $relation = $permissions->getRoutes($id);
            }

            // Assembly tables
            $permissao = "<table cellpadding='2' cellspacing='0'>";
            $permissao .= "<tr>";
            $permissao .= " <td><b>^^[Route]^^</b></td><td><b>^^[Action]^^</b></td><td><b>^^[Permission]^^</b></td>";
            $permissao .= "</tr>";

            $color = '';
            $bgcolor = '';

            foreach ($restriction as $k => $v) {
                if (! isset($v['parent'])) {
                    $title = isset($v['title']) ? $v['title'] : '';

                    $selected = (isset($relation[$k])) ? " selected" : "";
                    $color = (isset($relation[$k])) ? $color = 'green' : $color = 'red';
                    $bgcolor = ($bgcolor == 'eee') ? $bgcolor = 'fff' : $bgcolor = 'eee';

                    $permissao .= "<tr style='background-color:#$bgcolor;color:$color'>";
                    $permissao .= " <td width='120'>$k</td><td width='280'>{$title}</td>";
                    $permissao .= " <td>";
                    $permissao .= "  <select name='route[$k]' onchange=\"updateColor(this)\">";
                    $permissao .= "   <option value='0'>^^[No]^^</option>";
                    $permissao .= "   <option value='1'$selected>^^[Yes]^^</option>";
                    $permissao .= "  </select>";
                    $permissao .= " </td>";
                    $permissao .= "</tr>";
                }
            }

            $permissao .= "</table>";
        }

        // Return final HTML table of restricted URLs
        return $permissao;
    }
}
