<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Nodes Admin Controller
 */
namespace modules\Admin\controllers;

use modules\Admin\Admin;

class Nodes extends Admin
{
    /**
     * Insert new node content in the tree
     *
     * @param  array  $row
     * @return string $json
     */
    public function insert($row = NULL)
    {
        // Model
        $nodes = new \models\Nodes();
        $data = $nodes->insert();

        return $this->jsonEncode($data);
    }

    /**
     * Insert new node content in the tree
     *
     * @param  array  $row
     * @return string $json
     */
    public function update($row = NULL)
    {
        $id = (int)$this->getParam(3);

        $model = new \models\Nodes();

        if (isset($_POST['parent_id']) && isset($_POST['ordered'])) {
            $data = $model->get($id);
            $data->parent_id = (int)$this->getPost('parent_id');
            $data->save();

            $options = new \stdClass();
            $options->parent_id = (int)$this->getPost('parent_id');
            $options->ordered   = $this->getPost('ordered');
            $data = $model->setOrder($id, $options);
        } else {
            $data = $model->update($id);
        }

        return $this->jsonEncode($data);
    }

    /**
     * Get the node content
     */
    public function select()
    {
        // Id
        $id = $this->getParam(3);
        $locale = $this->getParam(4) ? $this->getParam(4) : null;

        // Model
        $model = new \models\Nodes();
        $data = $model->getById($id, $locale);

        return $this->jsonEncode($data);
    }

    /**
     * Logical node delete
     */
    public function delete()
    {
        $id = $this->getParam(3);

        // Model
        $model = new \models\Nodes();
        $data = $model->delete($id);

        return $this->jsonEncode($data);
    }

    /**
     * Show all available images in the system
     */
    public function images()
    {
        $this->setLayout(0);

        $result = $this->query->table("nodes")
            ->argument(1, "module_name", "'nodes'")
            ->argument(2, "option_name", "'images'")
            ->select()
            ->execute();

        while ($row = $this->query->fetch_assoc($result)) {
            echo "<img src='/images/{$row['node_id']}/small' class='img' onclick=\"setLink({$row['node_id']});\">";
        }

        echo "<script>";
        echo "function setLink(id) {";
        echo "    url = '/images/' + id;";
        echo "    window.opener.CKEDITOR.tools.callFunction( {$_GET['CKEditorFuncNum']}, url);";
        echo "    window.close();";
        echo "}";
        echo "</script>";
        echo "<style>";
        echo "img { width:100px;height:100px;margin:10px; }";
        echo "<style>";
    }
}
