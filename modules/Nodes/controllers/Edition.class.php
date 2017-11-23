<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Nodes
 */
namespace modules\Nodes\controllers;

use modules\Admin\Admin;

class Edition extends Admin
{
    public function __default()
    {
        // Load tree
        $tree = new \stdClass;
        $tree->node_id = '';
        $tree->template_area = 'tree';
        $tree->module_name = 'nodes';
        $tree->controller_name = 'edition';
        $tree->method_name = 'tree';
        $this->setContent($tree);

        // Id
        $node_id = $this->getParam(2);

        // Nodes model
        $nodes = new \models\Nodes();

        if ($node_id > 0) {
            // Edition
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $data = $nodes->update($node_id);
            }

            // Loading node information
            $result = $this->query->table('nodes')
                ->argument(1, 'node_id', $node_id)
                ->select()
                ->execute();
            $this->view = $this->query->fetch_assoc($result);

            // Data
            if ($this->view['node_json']) {
                $this->view += json_decode($this->view['node_json'], true);
            }

            // Message
            $this->view['message'] = isset($data['message']) ? $data['message'] : '';
        } else {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $data = $nodes->insert();
                header("Location:/nodes/{$data['id']}/edition");
                exit;
            }

            // New node id
            $this->view['node_id'] = '0';
            $this->view['parent_id'] = '0';

            // Parent
            if (isset($_GET['parent_id'])) {
                $this->view['parent_id'] = (int)$_GET['parent_id'];
            }

            // URL Helper
            $this->view['link'] = $nodes->getParentLink($this->view['parent_id']);
        }

        // New content
        if (! isset($this->view['type'])) {
            if ($this->getParam(1) == 'edition' && $this->getParam(3)) {
                $this->view['type'] = $this->getParam(3);
            } else {
                $this->view['type'] = 'edition';
            }
        }

        // Specific content
        if (isset($this->view['type']) && $this->view['type']) {
            $this->setView($this->view['type']);
        }

        // Domain
        $this->view['domain'] = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/';
    }

    public function move()
    {
        if (isset($_POST['parent_id'])) {
            $options = [
                'parent_id' => (int)$_POST['parent_id'],
                'order' => $_POST['order'],
            ];

            $nodes = new \models\Nodes();
            $nodes->setPosition((int)$this->getParam(2), $options);

            return $this->jsonEncode(['message' => 'Updated']);
        }
    }

    /**
     * Search content
     *
     * @return string $view
     */
    public function tree()
    {
        return $this->loadView('tree', 'Nodes');
    }

    /**
     * Tree for the content explorer
     *
     * @return string $json - tree of nodes
     */
    public function explorer()
    {
        // Nodes
        $nodes = array(
            array(
                "id" => "0",
                "text" => "^^[Site content]^^",
                "children" => $this->explorer_content(0)
            ),
            array(
                "id" => "trash",
                "text" => "^^[Trash]^^",
                "icon" => "img/tree/trash.png"
            )
        );

        return $this->jsonEncode($nodes);
    }

    /**
     * Internal recursive tree data assembly
     */
    private function explorer_content($parent_id = 0)
    {
        $nodes = array();

        // Search for nodes
        $result = $this->query->table("nodes n")
            ->column("n.node_id, COALESCE(n.parent_id, 0) AS parent_id, n.node_order, n.node_json")
            ->argument(1, "COALESCE(n.parent_id, 0)", $parent_id)
            ->argument(2, "n.node_status", 0, ">")
            ->order("COALESCE(n.node_order, 0), n.node_id")
            ->select()
            ->execute();

        while ($row = $this->query->fetch_assoc($result)) {
            // Avoid overflow in the interface
            $data = json_decode($row['node_json'], true);

            if (isset($data['title'])) {
                if (strlen($data['title']) > 25) {
                    $data['title'] = substr($data['title'], 0, 25) . ' ...';
                }
            } else {
                $data['title'] = '';
            }

            // Title
            $node = array(
                "id" => $row['node_id'],
                "text" => iconv('UTF-8', 'UTF-8//IGNORE', $data['title'])
            );

            // Tree icon
            if ($data['type']) {
                $icon = ($data['type'] == 'nodes') ? $data['type'] : $data['type'];

                $node['icon'] = "img/tree/$icon.png";
            }

            // Recursive search
            if ($row['node_id']) {
                if ($child = $this->explorer_content($row['node_id'])) {
                    $node['children'] = $child;
                }
            }

            $nodes[] = $node;
        }

        return $nodes;
    }
}
