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

class Routes extends Model
{
    /**
     * Populate routes grid
     *
     * @return json $data
     */
    public function grid()
    {
        // Search for the routes
        $this->database->Table("routes");
        $this->database->Column("route_id, CONCAT('/', COALESCE(route, '')) AS route,
            COALESCE(template_path, '') AS template_path, template_area");

        // Filter from grid searching
        if (isset($_GET['column']) && isset($_GET['value'])) {
            $column = $_GET['column'];

            $value = $_GET['value'];

            if (($column == "0") && ($value)) {
                $value = $this->database->Bind((int) $value);
                $this->database->Argument(2, "route_id", $value);
            } elseif (($column == 1) && ($value)) {
                $value = $this->database->Bind("%$value%");
                $this->database->Argument(2, "lower(route)", "lower($value)", "LIKE");
            } elseif (($column == 2) && ($value)) {
                $value = $this->database->Bind("%$value%");
                $this->database->Argument(2, "lower(template_path)", "lower($value)", "LIKE");
            }
        }

        $this->database->Select();
        $result = $this->database->Execute();

        return $this->gridFormat($result);
    }
}
