<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: BF Model Library
 */
namespace Bossanova\Model;

use Bossanova\Database\Database;

class Model extends \stdClass
{
    // Database instance
    public $database = null;

    // Table configuration
    public $config = null;

    /**
     * Return the model instance in a object format
     *
     * @param  object $db instance from the database
     * @param  string $table table name
     * @return void
     */
    public function __construct(&$instance = null, $tableName = null)
    {
        if (isset($instance)) {
            $this->database = $instance;
        } else {
            $this->database = Database::getInstance();
        }

        // Set table configuration
        if (! $this->config) {
            $this->setConfig($tableName);
        }

        // Make it a object
        $this->config = (object) $this->config;

        // Locale
        $this->config->locale = isset($_SESSION['locale']) ? $_SESSION['locale'] : DEFAULT_LOCALE;

        return $this;
    }

    private function setConfig($tableName = null)
    {
        try {
            // Table name
            $tableName = ($tableName) ? $tableName : strtolower(str_replace('models\\', '', get_class($this)));

            // Looing for the table information
            if ($info = $this->getTableInfo($tableName)) {
                $this->config = (object) array(
                    'tableName' => $tableName,
                    'primaryKey' => $info['primaryKey'],
                    'sequence' => $info['sequence'],
                    'recordId' => 0
                );
            } else {
                throw new ModelException("^^[Table could not be found.]^^");
            }
        } catch (ModelException $e) {
            echo $e;
        }
    }

    /**
     * Return the record as an array
     *
     * @param  integer $id
     * @return object
     */
    public function getById($id)
    {
        $this->database->table($this->config->tableName);
        $this->database->argument(1, $this->config->primaryKey, $id);
        $this->database->select();
        $result = $this->database->execute();
        return $this->database->fetch_assoc($result);
    }

    /**
     * Return the record in a object format
     *
     * @param  integer
     * @return object
     */
    public function get($id)
    {
        // Get data from the table
        $this->database->table($this->config->tableName);
        $this->database->argument(1, $this->config->primaryKey, $id);
        $this->database->select();
        $result = $this->database->execute();
        $data = $this->database->fetch_assoc($result);

        // Update object
        $this->config->recordId = $id;
        foreach ($data as $k => $v) {
            $this->{$k} = $v;
        }

        return $this;
    }

    /**
     * Update or insert the data on the database
     *
     * @return integer last inserted id, sequence or record id
     */
    public function save()
    {
        $column = array();

        // Accepted types
        $acceptedTypes = array('boolean','integer','double','string','null');

        // Binding column types
        foreach ($this as $k => $v) {
            if (in_array(gettype($v), $acceptedTypes)) {
                $column[$k] = $this->database->Bind($v);
            }
        }

        // Check the operation type, insert or update
        if (! $this->config->recordId) {
            // Insert a new record
            $this->database->table($this->config->tableName);
            $this->database->column($column);
            $this->database->insert();
            $this->database->execute();

            // Return id
            $this->config->recordId = $this->database->insert_id($this->config->sequence);
        } else {
            // Update existing record
            $this->database->table($this->config->tableName);
            $this->database->column($column);
            $this->database->argument(1, $this->config->primaryKey, $this->config->recordId);
            $this->database->update();
            $this->database->execute();
        }

        return $this->config->recordId;
    }

    /**
     * Set the data
     *
     * @param  integer
     * @return object
     */
    public function column($row)
    {
        // Set data
        $this->config->column = $this->database->bind($row);

        // Return the object
        return $this;
    }

    /**
     * Select record
     *
     * @param  integer
     * @return array
     */
    public function select($id)
    {
        // Get data from the table
        $this->database->table($this->config->tableName);
        $this->database->argument(1, $this->config->primaryKey, $id);
        $this->database->select();
        $result = $this->database->execute();
        return $row = $this->database->fetch_assoc($result);
    }

    /**
     * Update record
     *
     * @param  integer
     * @return void
     */
    public function update($id)
    {
        // Get data from the table
        $this->database->table($this->config->tableName);
        $this->database->column($this->config->column);
        $this->database->argument(1, $this->config->primaryKey, $id);
        $this->database->update();
        $result = $this->database->execute();
    }

    /**
     * Insert a new record
     *
     * @return integer
     */
    public function insert()
    {
        $pk = $this->config->primaryKey;

        if (isset($this->config->column[$pk]) && (! $this->config->column[$pk] || $this->config->column[$pk])) {
            unset($this->config->column[$pk]);
        }

        // Get data from the table
        $this->database->table($this->config->tableName);
        $this->database->column($this->config->column);
        $this->database->insert();
        $result = $this->database->execute();

        // Return the id
        return $this->database->insert_id($this->config->sequence);
    }

    /**
     * Delete the record
     *
     * @param  integer
     * @return object
     */
    public function delete($id)
    {
        // Get data from the table
        $this->database->table($this->config->tableName);
        $this->database->argument(1, $this->config->primaryKey, $id);
        $this->database->delete();
        $result = $this->database->execute();

        // Return the object
        return $this;
    }

    /**
     * Return the primary key from the table
     *
     * @return string table primary key
     */
    public function getPrimaryKey()
    {
        // Return the string name
        return $this->config->primaryKey;
    }

    /**
     * Return the main information from a given table
     *
     * @param  string
     * @return array
     */
    protected function getTableInfo($tableName)
    {
        $row = $this->database->getTableInfo($tableName);

        $column_name = isset($row['Column_name']) ? $row['Column_name'] : $row['column_name'];
        $row['primaryKey'] = $column_name;
        $row['sequence'] = str_replace(array("nextval","regclass","(",")","::","'"), "", $row['column_default']);

        return $row;
    }

    /**
     * Bossanova UI grid json format
     */
    protected function gridFormat($result)
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

    /**
     * Update or insert the data on the database
     *
     * @return integer last inserted id, sequence or record id
     */
    protected function clear()
    {
        // Create a new record
        $this->config->recordId = 0;
    }
}
