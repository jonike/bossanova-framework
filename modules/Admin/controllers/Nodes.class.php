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
    protected $nodeFull = array(
        'parent_id',
        'module_name',
        'option_name',
        'canonical',
        'author',
        'complement',
        'ordered',
        'published_from',
        'published_to',
        'status',
        'link',
        'title',
        'legend',
        'keywords',
        'description',
        'info'
    );

    protected $nodePartial = array(
        'parent_id',
        'module_name',
        'option_name',
        'canonical',
        'author',
        'complement',
        'ordered',
        'published_from',
        'published_to',
        'status'
    );

    protected $nodeLocale = array(
        'locale',
        'link',
        'title',
        'legend',
        'keywords',
        'description',
        'info'
    );

    protected $nodeAttach = array(
        'link',
        'title',
        'author',
        'published_from',
        'published_to',
        'status'
    );

    /**
     * Insert new node content in the tree
     *
     * @param  array  $row
     * @return string $json
     */
    public function insert($row = NULL)
    {
        // Keywords Adjusments
        if (isset($_POST['keywords']) && $_POST['keywords']) {
            $_POST['keywords'] = str_replace(",", ", ", $_POST['keywords']);
        }

        // Get the order from the node in the tree
        if (isset($_POST['parent_id'])) {
            $this->query->Table("nodes");
            $this->query->Column("MAX(COALESCE(ordered,0)) AS ordered");
            $this->query->Argument(1, "parent_id", (int) $_POST['parent_id']);
            $this->query->Select();
            $result = $this->query->Execute();
            $data = $this->query->fetch_assoc($result);

            $_POST['ordered'] = ($data['ordered']) ? ++ $data['ordered'] : 0;
        }

        // Update general content table
        if (DEFAULT_LOCALE == $_POST['locale']) {
            $column = $this->getPost($this->nodeFull);
        } else {
            $column = $this->getPost($this->nodePartial);
        }

        // Filter data to be saved
        $column = $this->query->bind($column);

        $column['posted'] = 'NOW()';
        $column['updated'] = 'NOW()';

        // Insert action
        $this->query->Table("nodes");
        $this->query->Column($column);
        $this->query->Insert();
        $this->query->Execute();

        // Update data content table
        $column = $this->getPost($this->nodeLocale);
        $column = $this->query->Bind($column);

        // Get parent id
        $column['node_id'] = $this->query->insert_id("nodes_node_id_seq");

        $this->query->Table("nodes_content");
        $this->query->Column($column);
        $this->query->Insert();
        $this->query->Execute();

        // Return
        if ($this->query->error) {
            $data = array('error' => '1', 'message' => '^^[It was not possible save this record]^^');
        } else {
            $data = array('id' => $column['node_id'], 'message' => '^^[Successfully saved]^^');
        }

        return isset($data) ? $this->jsonEncode($data) : '';
    }

    /**
     * Insert new node content in the tree
     *
     * @param  array  $row
     * @return string $json
     */
    public function update($row = NULL)
    {
        if (isset($_POST['parent_id']) && isset($_POST['ordered'])) {
            // Move node
            $this->query->Table("nodes");
            $this->query->Column(array('parent_id' => $_POST['parent_id']));
            $this->query->Argument(1, "node_id", $this->getParam(3));
            $this->query->Update();
            $this->query->Execute();

            // Update children order
            $ordered = explode(',', $_POST['ordered']);

            foreach ($ordered as $k => $v) {
                $this->query->Table("nodes");
                $this->query->Column(array('ordered' => $k));
                $this->query->Argument(1, "parent_id", $_POST['parent_id']);
                $this->query->Argument(2, "node_id", $v);
                $this->query->Update();
                $this->query->Execute();
            }
        } else {
            // Type image or attach
            if ($_POST['option_name'] == 'images' || $_POST['option_name'] == 'attach') {
                $column = $this->getPost($this->nodeAttach);
                $column = $this->query->Bind($column);
                $column['updated'] = 'NOW()';

                $this->query->Table("nodes");
                $this->query->Column($column);
                $this->query->Argument(1, "node_id", (int) $this->getParam(3));
                $this->query->Update();
                $this->query->Execute();
            } else {
                // Adjust keywords
                if (isset($_POST['keywords']) && $_POST['keywords']) {
                    $_POST['keywords'] = str_replace(",", ", ", $_POST['keywords']);
                }

                    // Update general content table
                if (DEFAULT_LOCALE == $_POST['locale']) {
                    $column = $this->getPost($this->nodeFull);
                } else {
                    $column = $this->getPost($this->nodePartial);
                }

                // Updated
                $column['updated'] = 'NOW()';
                $column = $this->query->Bind($column);

                // Save
                $this->query->Table("nodes");
                $this->query->Column($column);
                $this->query->Argument(1, "node_id", (int) $this->getParam(3));
                $this->query->Update();
                $this->query->Execute();

                // Update data table
                $column = $this->getPost($this->nodeLocale);
                $column = $this->query->Bind($column);

                // Check if content + locale exists
                $this->query->Table("nodes_content");
                $this->query->Column("node_content_id");
                $this->query->Argument(1, "node_id", (int) $this->getParam(3));
                $this->query->Argument(2, "locale", $this->query->Bind($_POST['locale']));
                $this->query->Select();
                $result = $this->query->Execute();

                // Update
                if ($row = $this->query->fetch_assoc($result)) {
                    $this->query->Table("nodes_content");
                    $this->query->Column($column);
                    $this->query->Argument(1, "node_content_id", $row['node_content_id']);
                    $this->query->Update();
                    $this->query->Execute();
                } else {
                    $column['node_id'] = (int) $this->getParam(3);
                    $this->query->Table("nodes_content");
                    $this->query->Column($column);
                    $this->query->Insert();
                    $this->query->Execute();
                }
            }

            // Return
            if ($this->query->error) {
                $data = array('error' => '1', 'message' => '^^[It was not possible save this record]^^');
            } else {
                $data = array('message' => '^^[Successfully saved]^^');
            }
        }

        return isset($data) ? $this->jsonEncode($data) : '';
    }

    /**
     * Get the node content
     */
    public function select()
    {
        $data = array();

        if ($this->getParam(4)) {
            // Open only translated fields
            $locale = $this->query->bind($this->getParam(4));

            // Get record
            $this->query->Table("nodes_content c");
            $this->query->Column("c.link, c.title, c.keywords, c.legend, c.description, c.info");
            $this->query->Argument(1, "c.node_id", (int) $this->getParam(3));
            $this->query->Argument(2, "c.locale", $locale);
            $this->query->Select();
            $result = $this->query->Execute();

            $data = $this->query->fetch_assoc($result);
        } else {
            // Default localtion
            $locale = DEFAULT_LOCALE;

            // Get record
            $this->query->Table("nodes");
            $this->query->Argument(1, "node_id", (int) $this->getParam(3));
            $this->query->Select();
            $result = $this->query->Execute();

            if ($data = $this->query->fetch_assoc($result)) {
                if ($data['option_name'] == 'images' || $data['option_name'] == 'attach') {
                    $data = array(
                        'node_id' => $data['node_id'],
                        'module_name' => $data['module_name'],
                        'option_name' => $data['option_name'],
                        'link' => $data['link'],
                        'author' => $data['author'],
                        'title' => $data['title'],
                        'published_from' => $data['published_from'],
                        'published_to' => $data['published_to'],
                        'status' => $data['status'],
                        );
                } else {
                    $data['locale'] = $locale;

                    $this->query->Table("nodes_content");
                    $this->query->Argument(1, "node_id", (int) $this->getParam(3));
                    $this->query->Argument(2, "locale", $this->query->bind($locale));
                    $this->query->Select();
                    $result = $this->query->Execute();

                    // Locale information
                    if ($row = $this->query->fetch_assoc($result)) {
                        if ($row['link']) {
                            $data['link'] = $row['link'];
                        }
                        if ($row['title']) {
                            $data['title'] = $row['title'];
                        }
                        if ($row['legend']) {
                            $data['legend'] = $row['legend'];
                        }
                        if ($row['keywords']) {
                            $data['keywords'] = $row['keywords'];
                        }
                        if ($row['description']) {
                            $data['description'] = $row['description'];
                        }
                        if ($row['info']) {
                            $data['info'] = $row['info'];
                        }
                    }
                }
            }
        }

        return $this->jsonEncode($data);
    }

    /**
     * Logical node delete
     */
    public function delete()
    {
        // Logical delete the node
        $this->query->Table("nodes");
        $this->query->Column(array('status' => 0));
        $this->query->Argument(1, "node_id", (int) $this->getParam(3));
        $this->query->Update();
        $this->query->Execute();

        // Logical delete all children without a active parent_id
        $this->query->Table("nodes");
        $this->query->Column("node_id");
        $this->query->Argument(1, "status", 1);
        $subquery = $this->query->Select();

        $this->query->Table("nodes");
        $this->query->Column(array('status' => 0));
        $this->query->Argument(1, "parent_id", "0", ">");
        $this->query->Argument(2, "parent_id", "NOT IN($subquery)", "");
        $this->query->Update();
        $this->query->Execute();
    }

    /**
     * Show all available images in the system
     */
    public function images()
    {
        $this->setLayout(0);

        $this->query->Table("nodes");
        $this->query->Argument(1, "module_name", "'nodes'");
        $this->query->Argument(2, "option_name", "'images'");
        $this->query->Select();
        $result = $this->query->Execute();

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
