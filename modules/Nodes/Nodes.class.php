<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Nodes Module (CMS Plugin)
 */
namespace modules\Nodes;

use bossanova\Module\Module;

class Nodes extends Module
{
    /**
     * Node information
     *
     * @var $node
     */
    public $node = [];

    /**
     * Default method to search or open a node
     *
     * @return string $html
     */
    public function __default()
    {
        $content = '';

        // Search for an node
        if (isset($_GET['q'])) {
            // Strip HTML tags
            $q = strip_tags($_GET['q']);

            // Get search result
            $nodes = new \models\Nodes();
            $content = $nodes->search($q);
        } else {
            $configuration = $this->getConfiguration();

            // Open a node based on the node_id
            if (isset($configuration['node_id'])) {
                $node_id = $configuration['node_id'];
            }
            else {
                $node_id = ($this->getParam(1)) ? $this->getParam(1) : 0;
            }

            // Get Content by Id
            if ($node_id) {
                $content = $this->getContent($node_id, true);
            }
        }

        return $content;
    }

    /**
     * Get a node content
     *
     * @return string $html
     */
    public function getContent($node_id = 0, $metadata = 1)
    {
        // Loading node information
        $nodes = new \models\Nodes();
        $row = $nodes->getById($node_id);

        if ($row) {
            // Data
            $row = $row + json_decode($row['node_json'], true);

            if ($metadata) {
                // Define global template information
                if (isset($row['title']) && $row['title']) {
                    $this->setTitle($row['title']);
                }

                if (isset($row['author']) && $row['author']) {
                    $this->setAuthor($row['author']);
                }

                if (isset($row['keywords']) && $row['keywords']) {
                    $this->setKeywords($row['keywords']);
                }

                if (isset($row['description']) && $row['description']) {
                    $this->setDescription(strip_tags($row['description']));
                }
            }

            // Main content
            $m = "\\modules\\Nodes\\controllers\\" . ucfirst($row['type']);
            $c = new $m;
            $content = $c->get($row);
        }

        return isset($content) ? $content : '';
    }

    /**
     * Search content
     *
     * @return string $view
     */
    public function search()
    {
        return $this->loadView('search', 'Nodes');
    }

    /**
     * Proxy to import images from ondrop
     */
    public function url()
    {
        if ($filename = $_GET['url']) {
            header('Content-type:image/' . substr($filename, -3));
            echo file_get_contents($filename);
            exit;
        }
    }
}
