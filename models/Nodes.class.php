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

class Nodes extends Model
{
    // Table configuration
    public $config = array(
        'tableName' => 'nodes',
        'primaryKey' => 'node_id',
        'sequence' => 'nodes_node_id_seq',
        'recordId' => 0
    );

    // Breacrumb position
    public $breadcrumb_position;

    public function insert()
    {
        // Position in the tree
        $_POST['order'] = isset($_POST['parent_id']) ? (int)$this->getOrder($_POST['parent_id']) : 0;

        // Get Data posted
        $data = $this->getDataPosted();

        // Insert action
        $this->database->table('nodes')
            ->column($data)
            ->insert()
            ->execute();

        // Get parent id
        $id = $this->database->insert_id('nodes_node_id_seq');

        // Return
        if (! $this->database->error) {
            $data = array('id' => $id, 'message' => '^^[Successfully saved]^^');
        } else {
            $data = array('error' => '1', 'message' => '^^[It was not possible save this record]^^');
        }

        return $data;
    }

    public function update($id)
    {
        // Open transaction
        $this->database->begin();

        // Get Data posted
        $data = $this->getDataPosted();

        // Update action
        $this->database->table('nodes')
            ->column($data)
            ->argument(1, "node_id", $id)
            ->update()
            ->execute();

        // Return
        if (! $this->database->error) {
            $data = array('message' => '^^[Successfully saved]^^');
            $this->database->commit();
        } else {
            $data = array('error' => '1', 'message' => '^^[It was not possible save this record]^^');
            $this->database->rollback();
        }

        return $data;
    }

    public function getDataPosted()
    {
        // Json content string
        $row = $this->getPost();

        // If files are being uploaded
        if (isset($_FILES) && count($_FILES)) {
            if (count($_FILES['file']['name'])) {
                foreach ($_FILES['file']['name'] as $k => $v) {
                    $extension = explode('.', $_FILES['file']['name'][$k]);
                    $extension = $extension[count($extension)-1];

                    // Extension
                    $file['size'] = filesize($_FILES['file']['tmp_name'][$k]);
                    $file['filename'] = $_FILES['file']['name'][$k];
                    $file['extension'] = $extension;
                    $file['content'] = base64_encode(file_get_contents($_FILES['file']['tmp_name'][$k]));

                    $row['files'][] = $file;
                }
            }
        }

        // Data
        $data = [
            'parent_id' => $this->getPost('parent_id'),
            'node_link' => $this->getPost('link'),
            'node_order' => $this->getPost('order'),
            'node_status' => $this->getPost('status'),
            'node_json' => json_encode($row)
        ];

        // Bind
        return $data = $this->database->bind($data);
    }

    public function delete($id)
    {
        // Open transaction
        $this->database->begin();

        // Update action
        $this->database->table('nodes')
            ->column(array('node_status' => 0))
            ->argument(1, "node_id", $id)
            ->update()
            ->execute();

        // Logical delete all children without a active parent_id
        $subquery = $this->database->table('nodes')
            ->column('node_id')
            ->argument(1, "node_status", 1)
            ->getSelect();

        $this->database->table('nodes')
            ->column(array('node_status' => 0))
            ->argument(1, 'parent_id', 0, '>')
            ->argument(2, 'parent_id', "NOT IN($subquery)", '')
            ->update()
            ->execute();

        // Return
        if (! $this->database->error) {
            $data = array('message' => '^^[Successfully deleted]^^');
            $this->database->commit();
        } else {
            $data = array('error' => '1', 'message' => '^^[It was not possible delete this record]^^');
            $this->database->rollback();
        }

        return $data;
    }

    /**
     * Get the next order position from a node in the hierarquy
     *
     * @param integer $id
     * @return integer $positino
     */
    public function getOrder($id)
    {
        $result = $this->database->table("nodes")
            ->column("COALESCE(MAX(COALESCE(node_order, 0)), 0) + 1 AS node_order")
            ->argument(1, "parent_id", $id)
            ->select()
            ->execute();

        return ($data = $this->database->fetch_assoc($result)) ? $data['node_order'] : 0;
    }

    /**
     * Get the next order position from a node in the hierarquy
     *
     * @param integer $id
     * @return integer $positino
     */
    public function getChildren($node_id = 0)
    {
        $nodes = '';

        // Search for nodes
        $this->database->table("nodes");
        $this->database->column("node_id, node_json");
        $this->database->argument(1, "COALESCE(parent_id, 0)", $node_id);
        $this->database->argument(2, "node_status", "0", ">");
        $this->database->select();
        $result = $this->database->execute();

        while ($row = $this->database->fetch_assoc($result)) {
            // Avoid overflow in the interface
            $data = json_decode($row['node_json'], true);

            // Get children
            $nodes .= $this->getChildren($row['node_id']);
            $nodes .= ',' . $row['node_id'];
        }

        return $nodes;
    }

    /**
     * Update parent position in the tree
     *
     * @param integer $id
     * @return integer $parent_id
     */
    public function setPosition($id, $options)
    {
        $this->database->table('nodes')
            ->column(array('parent_id' => $options['parent_id']))
            ->argument(1, 'node_id', $id)
            ->update()
            ->execute();

        if ($options['order']) {
            // Update children order
            $order = explode(',', $options['order']);

            foreach ($order as $k => $v) {
                $this->database->table('nodes')
                    ->column(array('node_order' => $k))
                    ->argument(1, 'node_id', $v)
                    ->argument(2, 'parent_id', $options['parent_id'])
                    ->update()
                    ->execute();
            }
        }
    }

    /**
     * Get all node information by id
     *
     * @param  integer $node_id
     * @return array   $data
     */
    public function getById($node_id, $locale = null)
    {
        // Loading node information
        $result = $this->database->table('nodes')
            ->argument(1, 'node_id', $node_id)
            ->argument(2, 'node_status', 0, '>')
            ->select()
            ->execute();

        if ($data = $this->database->fetch_assoc($result)) {
            // Link
            //$data['url'] = $this->nodeLink($data);

            // Breadcrumb
            if ($breadcrumb = $this->getBreadcrumb($data['parent_id'])) {
                $data['breadcrumb'] = "<ol itemprop='breadcrumb' itemscope itemtype='http://schema.org/BreadcrumbList'
                    class='breadcrumb'>$breadcrumb</ol>";
            } else {
                $data['breadcrumb'] = '';
            }

            // Parent link
            $data['parent_url'] = $this->getParentLink($data['parent_id']);
        }

        return $data;
    }

    /**
     * Get the node link from its parent node
     *
     * @param  integer $node_id
     * @return string  $content
     */
    public function getParentLink($node_id)
    {
        if ($node_id) {
            $result = $this->database->table("nodes n")
                ->argument(1, "node_id", $node_id)
                ->select()
                ->execute();

            $data = $this->database->fetch_assoc($result);
        }

        return isset($data['node_link']) ? $data['node_link'] : '';
    }

    /**
     * Search nodes
     *
     * @param  string $q       Query by string
     * @param  string $admin   Edition button in the admin search
     * @return string $content
     */
    public function search($q, $admin = null)
    {
        // Content to be returned
        $content = '';

        // Search terms
        $v = $this->database->bind("%{$q}%");

        // Very simple search @TODO: pagination, contextual/elastic search.
        $this->database->table("nodes n");
        $this->database->Innerjoin("nodes_content c", "n.node_id = c.node_id AND c.locale = '{$this->config->locale}'");
        $this->database->column("n.node_id, n.option_name, n.complement, c.title, c.content, c.description, c.link");
        $this->database->argument(1, "lower(c.title)", "lower($v)", "LIKE");
        $this->database->argument(2, "lower(c.description)", "lower($v)", "LIKE");
        $this->database->argument(3, "lower(c.content)", "lower($v)", "LIKE");
        $this->database->argument(4, "status", 1);
        $this->database->Where("((1) OR (2) OR (3)) AND (4)");
        $this->database->select();
        $result = $this->database->execute();

        while ($row = $this->database->fetch_assoc($result)) {
            // Description
            if (! $row['description']) {
                $info = preg_replace("/\r\n/", " ", strip_tags($row['content']));
            } else {
                $info = preg_replace("/\r\n/", " ", strip_tags($row['description']));
            }

            // Shorten descrption
            if (strlen($info) > 350) {
                $row['description'] = substr($info, 0, 350) . ' ...';
            } else {
                $row['description'] = $info;
            }

            // Node Url
            $url = $this->nodeLink($row);

            // For admin search show a edition link
            if ($admin) {
                $admin = "(<a onclick=\"$('#admin_nodes_tree').jstree('open_all');
                    $('#admin_nodes_tree').jstree('deselect_all');
                    $('#admin_nodes_tree').jstree('select_node', '#{$row['node_id']}');
                    $('#tabs').tabs('open', 'nodes')\" style='cursor:pointer;'>^^[Edit]^^</a>)";
            }

            $content .= "<li><a href='$url'>{$row['title']}</a> $admin<br>{$row['description']}</li>\n";
        }

        if (! $content) {
            $content = "<p>^^[Searching for]^^: {$q}</p><p>^^[No results found]^^</p>";
        } else {
            $content = "<p>^^[Searching for]^^: {$q}</p><ul>$content</ul>";
        }

        return $content;
    }

    /**
     * Sitemap
     *
     * @return string $content Sitemap
     */
    public function sitemap()
    {
        // Headers XML
        $content = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        // Get all enabled nodes
        $this->database->table("nodes n");
        $this->database->Leftjoin("nodes_content c", "n.node_id = c.node_id AND c.locale = '{$this->config->locale}'");
        $this->database->column("n.*, c.link");
        $this->database->argument(1, "status", 1);
        $this->database->select();
        $result = $this->database->execute();

        while ($row = $this->database->fetch_assoc($result)) {
            // Get URL
            $link = $this->nodeLink($row);

            // Last update
            $row['updated'] = substr($row['updated'], 0, 10);

            // Records
            $content .= '    <url>' . PHP_EOL;
            $content .= '        <loc>' . $link . '</loc>' . PHP_EOL;
            $content .= '        <lastmod>' . $row['updated'] . '</lastmod>' . PHP_EOL;
            $content .= '        <changefreq>daily</changefreq>' . PHP_EOL;
            $content .= '        <priority>1</priority>' . PHP_EOL;
            $content .= '    </url>' . PHP_EOL;
        }

        $content .= '</urlset>';

        return $content;
    }

    /**
     * Recursive function to create a breadcrumb
     *
     * @param  integer $parent_id
     * @return string  $node
     */
    public function getBreadcrumb($parent_id = 0)
    {
        $html = '';

        if ($parent_id) {
            // Get the nodes recursivelly
            $result =$this->database->table("nodes n")
                ->argument(1, "n.node_id", $parent_id)
                ->select()
                ->execute();

            if ($row = $this->database->fetch_assoc($result)) {
                $node = json_decode($row['node_json'], true);

                if ($row['parent_id']) {
                    // Recursive call in case there is a parent for this node
                    $html = $this->getBreadcrumb($row['parent_id']);
                    if ($html) {
                        $html = $html . ' > ';
                    }
                }

                // Link for the node
                $url = $this->nodeLink($node);

                // Breadcrump position hierarquy
                $position = ++$this->breadcrumb_position;

                // HTML for the breadcrumb link
                $html .= "<li itemprop='itemListElement' itemscope itemtype='http://schema.org/ListItem' tyle='display:inline;'>";
                $html .= "<a itemprop='item' href='$url'>";
                $html .= "<span itemprop='name'>{$node['title']}</span>";
                $html .= "<meta itemprop='position' content='{$position}' />";
                $html .= "</a>";
                $html .= "</li>";
            }
        }

        return $html;
    }

    /**
     * Create a link to a node based on its content
     *
     * @param  array  $row Node information
     * @return string $url Node URL
     */
    public function nodeLink($row)
    {
        $url = '';

        // The link is a custom link
       if ($row['type'] == 'link' && $row['url']) {
            $url = $row['url'];
        } else {
            // Get the page name
            if ($row['link']) {
                $node = $row['link'];
            } else {
                $node = 'nodes/' . $row['node_id'];
            }
            // Create the full link
            $url = $this->getLink($node);
        }

        return $url;
    }

    /**
     * Create a full link to a page
     *
     * @param  string $pageName
     * @return string $url
     */
    public function getLink($pageName)
    {
        // Get http or https
        $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';

        // Full link to the index
        $url = $scheme . '://' . str_replace('index.php', '', $host . $_SERVER["SCRIPT_NAME"]);

        // Add the page
        if (substr($url, - 1, 1) != '/') {
            $url .= '/';
        }

        // Page
        $url .= $pageName;

        return $url;
    }
}
