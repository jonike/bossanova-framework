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
        // Data to be saved
        $row = $this->getPost($this->getColumns('full'));

        // Bind
        $row = $this->database->bind($row);

        // Position in the tree
        if (isset($_POST['parent_id'])) {
           $row['ordered'] = (int)$this->getOrder($_POST['parent_id']);
        }

        $row['posted'] = 'NOW()';
        $row['updated'] = 'NOW()';

        // Open transaction
        $this->database->begin();

        // Insert action
        $this->database->table('nodes')
            ->column($row)
            ->insert()
            ->execute();

        // Get parent id
        $id = $this->database->insert_id('nodes_node_id_seq');

       // Update locale
        $this->setLocale($id);

        // Return
        if (! $this->database->error) {
            $data = array('id' => $id, 'message' => '^^[Successfully saved]^^');
            $this->database->commit();
        } else {
            $data = array('error' => '1', 'message' => '^^[It was not possible save this record]^^');
            $this->database->rollback();
        }

        return $data;
    }

    public function update($id)
    {
        if ($_POST['option_name'] == 'images' || $_POST['option_name'] == 'attach') {
            $type = 'attach';
        } else {
            $type = (DEFAULT_LOCALE == $_POST['locale']) ? 'full' : 'partial';
        }

        // Data to be saved
        $row = $this->getPost($this->getColumns($type));

        // Bind
        $row = $this->database->Bind($row);

        $row['updated'] = 'NOW()';

        // Open transaction
        $this->database->begin();

        // Update action
        $this->database->table('nodes')
            ->column($row)
            ->argument(1, "node_id", $id)
            ->update()
            ->execute();

        // Update locale
        $this->setLocale($id);

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

    public function delete($id)
    {
        // Open transaction
        $this->database->begin();

        // Update action
        $this->database->table('nodes')
            ->column(array('status' => 0))
            ->argument(1, "node_id", $id)
            ->update()
            ->execute();

        // Logical delete all children without a active parent_id
        $subquery = $this->database->table('nodes')
            ->column('node_id')
            ->argument(1, "status", 1)
            ->getSelect();

        $this->database->table('nodes')
            ->column(array('status' => 0))
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

    public function setLocale($id)
    {
        // Update data table
        $row = $this->getPost($this->getColumns('locale'));

        // Locale
        if (! isset($row['locale']) || ! $row['locale']) {
            $row['locale'] = DEFAULT_LOCALE;
        }

        // Bind
        $row = $this->database->bind($row);

        // Check if content + locale exists
        $result1 = $this->database->table('nodes_content')
            ->column('node_content_id')
            ->argument(1, 'node_id', $id)
            ->argument(2, 'locale', $row['locale'])
            ->select()
            ->execute();

        // Update
        if ($row1 = $this->database->fetch_assoc($result1)) {
            $this->database->table('nodes_content')
                ->column($row)
                ->argument(1, 'node_content_id', $row1['node_content_id'])
                ->update()
                ->execute();
        } else {
            $row['node_id'] = (int)$id;
            $this->database->table('nodes_content')
                ->column($row)
                ->insert()
                ->execute();
        }
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
            ->column("COALESCE(MAX(COALESCE(ordered, 0)), 0) + 1 AS ordered")
            ->argument(1, "parent_id", $id)
            ->select()
            ->execute();

        return ($data = $this->database->fetch_assoc($result)) ? $data['ordered'] : 0;
    }

    /**
     * Set the order position from a node in the hierarquy
     *
     * @param integer $id
     * @return integer $positino
     */
    public function setOrder($id, $options)
    {
        // Update children order
        $ordered = explode(',', $options->ordered);

        foreach ($ordered as $k => $v) {
            $this->database->table('nodes')
                ->column(array('ordered' => $k))
                ->argument(1, 'parent_id', $options->parent_id)
                ->argument(2, 'node_id', $v)
                ->update()
                ->execute();
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
        if (! isset($locale) || ! $locale) {
            $locale = isset($_SESSION['locale']) ? $_SESSION['locale'] : DEFAULT_LOCALE;
        }

        // Loading node information
        $result = $this->database->table('nodes')
            ->argument(1, 'node_id', $node_id)
            ->argument(2, 'status', 0, '>')
            ->select()
            ->execute();

        if ($data = $this->database->fetch_assoc($result)) {
            $locale = $this->database->bind($locale);

            $result1 = $this->database->table('nodes_content')
                ->argument(1, 'node_id', $node_id)
                ->argument(2, 'locale', $locale)
                ->select()
                ->execute();

            // Locale information
            if ($row = $this->database->fetch_assoc($result1)) {
                $data['locale'] = $row['locale'];

                if ($row['link']) {
                    $data['link'] = $row['link'];
                }
                if ($row['page']) {
                    $data['page'] = $row['page'];
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
                if ($row['summary']) {
                    $data['summary'] = $row['summary'];
                }
                if ($row['content']) {
                    $data['content'] = $row['content'];
                }
            }

            // Link
            $data['url'] = $this->nodeLink($data);

            // Breadcrumb
            if ($breadcrumb = $this->getBreadcrumb($data['parent_id'])) {
                $data['breadcrumb'] = "<ol itemprop='breadcrumb' itemscope itemtype='http://schema.org/BreadcrumbList'
                    class='breadcrumb' style='list-style:none;padding:0px;margin:0px;'>$breadcrumb</ol>";
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
        $url = '';

        $result =$this->database->table("nodes n")
            ->column("n.node_id, n.option_name, n.complement, n.link")
            ->argument(1, "n.node_id", $node_id)
            ->argument(2, "n.status", 1)
            ->select()
            ->execute();

        if ($data = $this->database->fetch_assoc($result)) {
            // Get locale
            $result1 =$this->database->table("nodes_content")
                ->argument(1, "node_id", $node_id)
                ->argument(2, "locale", $this->database->bind($this->config->locale))
                ->select()
                ->execute();

            // Locale information
            if ($row = $this->database->fetch_assoc($result1)) {
                if ($row['link']) {
                    $data['link'] = $row['link'];
                }
            }

            // Link
            if ($data['option_name'] == 'link') {
                $url = $data['complement'];
            } else {
                if ($data['link']) {
                    $node = $data['link'];
                } else {
                    $node = 'nodes/' . $data['node_id'];
                }

                $url = $this->getLink($node);
            }
        }

        return $url;
    }

    /**
     * Get all children nodes
     *
     * @param  integer $node_id
     * @return string  $content
     */
    public function getListContent($node_id)
    {
        $content = '';

        $result =$this->database->table("nodes")
            ->argument(1, "parent_id", $node_id)
            ->argument(2, "status", 1)
            ->order("ordered, node_id")
            ->select()
            ->execute();

        // List
        while ($data = $this->database->fetch_assoc($result)) {
            // Get locale
            $this->database->table("nodes_content");
            $this->database->argument(1, "node_id", $data['node_id']);
            $this->database->argument(2, "locale", $this->database->bind($this->config->locale));
            $this->database->select();
            $result1 = $this->database->execute();

            // Locale information
            if ($row = $this->database->fetch_assoc($result1)) {
                if ($row['link']) {
                    $data['link'] = $row['link'];
                }
                if ($row['title']) {
                    $data['title'] = $row['title'];
                }
                if ($row['description']) {
                    $data['description'] = $row['description'];
                }
            }

            // Content
            $url = $this->nodeLink($data);

            if ($data['description']) {
                $description = $data['description'] . " <a href='$url' class='readmore'>^^[Read more]^^</a>";
                $data['description'] = "<br><span id='description'>{$description}</span>";
            }

            $content .= "<li><a href='$url'>{$data['title']}</a>{$data['description']}</li>";
        }

        return $content;
    }

    /**
     * Get all children nodes
     *
     * @param  integer $node_id
     * @return string  $content
     */
    public function getList($node_id, $format = null)
    {
        $content = '';

        $this->database->table("nodes");
        $this->database->argument(1, "parent_id", $node_id);
        $this->database->argument(2, "status", 1);
        $this->database->Order("ordered, node_id");
        $this->database->select();
        $result = $this->database->execute();

        // List
        while ($data = $this->database->fetch_assoc($result)) {
            // Get locale
            $this->database->table("nodes_content");
            $this->database->argument(1, "node_id", $data['node_id']);
            $this->database->argument(2, "locale", $this->database->bind($this->config->locale));
            $this->database->select();
            $result1 = $this->database->execute();

            // Locale information
            if ($row = $this->database->fetch_assoc($result1)) {
                if ($row['link']) {
                    $data['link'] = $row['link'];
                }
                if ($row['title']) {
                    $data['title'] = $row['title'];
                }
            }

            // Content
            $data['url'] = $this->nodeLink($data);

            if ($format) {
                $contentNode = $format;
                foreach ($data as $k => $v) {
                    $contentNode = str_replace("[$k]", $v, $contentNode);
                }
                $content .= $contentNode;
            } else {
                $content .= "<li><a href='{$data['url']}'>{$data['title']}</a></li>";
            }
        }

        return $content;
    }

    /**
     * Get all children nodes
     *
     * @param  integer $node_id
     * @return string  $content
     */
    public function getBlogContent($node_id)
    {
        $content = '';

        $this->database->table("nodes");
        $this->database->argument(1, "parent_id", $node_id);
        $this->database->argument(2, "status", 1);
        $this->database->Order("ordered, node_id");
        $this->database->select();
        $result = $this->database->execute();

        // Blog
        while ($data = $this->database->fetch_assoc($result)) {
            // Get locale
            $this->database->table("nodes_content");
            $this->database->argument(1, "node_id", $data['node_id']);
            $this->database->argument(2, "locale", $this->database->bind($this->config->locale));
            $this->database->select();
            $result1 = $this->database->execute();

            // Locale information
            if ($row = $this->database->fetch_assoc($result1)) {
                if ($row['link']) {
                    $data['link'] = $row['link'];
                }
                if ($row['title']) {
                    $data['title'] = $row['title'];
                }
                if ($row['description']) {
                    $data['description'] = $row['description'];
                }
            }

            // Content
            $url = $this->nodeLink($data);

            if ($data['description']) {
                $description = $data['description'] . " <a href='$url' class='readmore'>^^[Read more]^^</a>";
                $data['description'] = "<br><span id='description'>{$description}</span>";
            }

            $content .= "<li><a href='{$url}'>{$data['title']}</a>{$data['description']}</li>";
        }

        return $content;
    }

    /**
     * Get all children nodes
     *
     * @param  integer $node_id
     * @return string  $content
     */
    public function getNodes($node_id)
    {
        $content = array();

        $this->database->table("nodes");
        $this->database->argument(1, "parent_id", $node_id);
        $this->database->argument(2, "status", 1);
        $this->database->Order("ordered, node_id");
        $this->database->select();
        $result = $this->database->execute();

        // List
        while ($data = $this->database->fetch_assoc($result)) {
            // Get locale
            $this->database->table("nodes_content");
            $this->database->argument(1, "node_id", $data['node_id']);
            $this->database->argument(2, "locale", $this->database->bind($this->config->locale));
            $this->database->select();
            $result1 = $this->database->execute();

            // Locale information
            if ($row = $this->database->fetch_assoc($result1)) {
                if ($row['link']) {
                    $data['link'] = $row['link'];
                }
                if ($row['title']) {
                    $data['title'] = $row['title'];
                }
            }

            // Content
            $data['url'] = $this->nodeLink($data);

            $content[] = $data;
        }

        return $content;
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
        if (! isset($node)) {
            $node = '';
        }

        if ($parent_id) {
            // Get the nodes recursivelly
            $locale = $this->config->locale;
            $this->database->table("nodes n");
            $this->database->Leftjoin("nodes_content c", "n.node_id = c.node_id AND c.locale = '{$locale}'");
            $this->database->column("n.node_id, n.parent_id, n.option_name, n.complement, c.title, c.link");
            $this->database->argument(1, "n.node_id", $parent_id);
            $this->database->select();
            $result = $this->database->execute();

            if ($row = $this->database->fetch_assoc($result)) {
                if ($row['parent_id']) {
                    // Recursive call in case there is a parent for this node
                    $node = $this->getBreadcrumb($row['parent_id']);
                    if ($node) {
                        $node = $node . ' > ';
                    }
                }

                // Link for the node
                $url = $this->nodeLink($row);

                // Breadcrump position hierarquy
                $position = ++ $this->breadcrumb_position;

                // HTML for the breadcrumb link
                $node .= "<li itemprop='itemListElement' itemscope itemtype='http://schema.org/ListItem'
                    style='display:inline;'>";
                $node .= "<a itemprop='item' href='$url'>";
                $node .= "<span itemprop='name'>{$row['title']}</span>";
                $node .= "<meta itemprop='position' content='{$position}' />";
                $node .= "</a>";
                $node .= "</li>";
            }
        }

        return $node;
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
        if ($row['option_name'] == 'link' && $row['complement']) {
            $url = $row['complement'];
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
        // Full link to the index

        $url = $scheme . '://' . str_replace('index.php', '', $_SERVER["HTTP_HOST"] . $_SERVER["SCRIPT_NAME"]);

        // Add the page
        if (substr($url, - 1, 1) != '/') {
            $url .= '/';
        }

        // Page
        $url .= $pageName;

        return $url;
    }

    protected function getColumns($type = null)
    {
        if ($type == 'partial') {
            $node = array(
                'parent_id',
                'module_name',
                'option_name',
                'author',
                'canonical',
                'complement',
                'ordered',
                'published_from',
                'published_to',
                'status'
            );
        } else if ($type == 'locale') {
            $node = array(
                'locale',
                'link',
                'page',
                'title',
                'legend',
                'keywords',
                'description',
                'summary',
                'content',
                'format',
            );
        } else if ($type == 'attach') {
            $node = array(
                'link',
                'title',
                'author',
                'published_from',
                'published_to',
                'status'
            );
        } else {
            $node = array(
                'parent_id',
                'module_name',
                'option_name',
                'link',
                'page',
                'title',
                'author',
                'legend',
                'keywords',
                'canonical',
                'description',
                'summary',
                'content',
                'format',
                'complement',
                'ordered',
                'published_from',
                'published_to',
                'status',
            );
        }

        return $node;
    }
}
