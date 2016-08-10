<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Upload Image Handler
 */
namespace modules\Admin\controllers;

use modules\Admin\Admin;

class Upload extends Admin
{
    /**
     * Manage images upload as nodes in the tree
     */
    public function __default()
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            if (isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name']) {
                // Images extensions
                $images = array('image/png', 'image/gif', 'image/jpg');

                // Extension
                $content = base64_encode(file_get_contents($_FILES['file']['tmp_name']));
                $extension = mime_content_type($_FILES['file']['tmp_name']);

                // File
                $column['complement'] = "'{$extension}'";
                $column['info'] = "'{$content}'";

                // Save file
                if (isset($_POST['node_id']) && $_POST['node_id']) {
                    $this->query->Table("nodes");
                    $this->query->Column($column);
                    $this->query->Argument(1, "node_id", $_POST['node_id']);
                    $this->query->Update();
                    $this->query->Execute();

                    echo "<script>parent.nodes_refresh_image();</script>";
                } else {
                    $column['title'] = "'" . $_FILES['file']['name'] . "'";
                    $column['parent_id'] = (int) $this->getParam(2);
                    $column['posted'] = "NOW()";
                    $column['updated'] = "NOW()";
                    $column['module_name'] = "'nodes'";
                    $column['option_name'] = in_array($extension, $images) ? "'images'" : "'attach'";
                    $column['status'] = 1;

                    $this->query->Table("nodes");
                    $this->query->Column($column);
                    $this->query->Insert();
                    $this->query->Execute();
                }
            }

            $this->setLayout(0);
            $this->setView(0);
        }
    }
}
