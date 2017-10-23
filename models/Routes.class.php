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

class Routes extends Model
{
    // Table configuration
    public $config = array(
        'tableName' => 'routes',
        'primaryKey' => 'route_id',
        'sequence' => 'routes_route_id_seq',
        'recordId' => 0
    );

    /**
     * Populate routes grid
     *
     * @return json $data
     */
    public function grid()
    {
        // Search for the routes
        $this->database->Table("routes");
        $this->database->column("route_id, CONCAT('/', COALESCE(route, '')) AS route,
            COALESCE(template_path, '') AS template_path, template_area");

        // Filter from grid searching
        if (isset($_GET['column']) && isset($_GET['value'])) {
            $column = $_GET['column'];

            $value = $_GET['value'];

            if (($column == "0") && ($value)) {
                $value = $this->database->Bind((int) $value);
                $this->database->argument(2, "route_id", $value);
            } elseif (($column == 1) && ($value)) {
                $value = $this->database->Bind("%$value%");
                $this->database->argument(2, "lower(route)", "lower($value)", "LIKE");
            } elseif (($column == 2) && ($value)) {
                $value = $this->database->Bind("%$value%");
                $this->database->argument(2, "lower(template_path)", "lower($value)", "LIKE");
            }
        }

        $this->database->select();
        $result = $this->database->execute();

        return $this->gridFormat($result);
    }

    /**
     * Return the record as an array
     *
     * @param  integer $id
     * @return object
     */
    public function getByRoute($url)
    {
        $url = $this->database->bind($url);

        $result = $this->database->table('routes')
            ->argument(1, "COALESCE(route, '')", $url)
            ->select()
            ->execute();

        return $this->database->fetch_assoc($result);
    }
}
