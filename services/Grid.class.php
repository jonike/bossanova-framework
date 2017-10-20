<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Services
 */
namespace services;

use bossanova\Database\Database;

class Grid
{
    public $database = null;

    public function __construct(Database $instance = null)
    {
        if (isset($instance)) {
            $this->database = $instance;
        } else {
            $this->database = Database::getInstance();
        }
    }

    /**
     * Bossanova UI grid json format
     */
    public function get($result)
    {
        $page = isset($_GET['page']) && $_GET['page'] ? (int) $page = $_GET['page'] : 1;

        $i = 0;
        $j = 0;
        $data['rows'] = array();

        // Grid rows
        while ($row = $this->database->fetch_assoc($result)) {
            if (($j >= ($page - 1) * 10) && ($j < ((($page - 1) * 10) + 10))) {
                if (! isset($data['rows'][$i]['id'])) {
                    $data['rows'][$i]['id'] = current($row);
                }
                $data['rows'][$i]['cell'] = $row;
                $i++;
            }

            $j++;
        }

        // Total results
        $data['page'] = $page;
        $data['total'] = (int) $j;

        return $data;
    }

}
