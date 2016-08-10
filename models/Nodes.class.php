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
        'primaryKey' => 'nodes_id',
        'sequence' => 'nodes_node_id_seq',
        'recordId' => 0
    );

    // Breacrumb position
    public $breadcrumb_position;

    /**
     * Get all node information by id
     *
     * @param  integer $node_id
     * @return array   $data
     */
    public function getById($node_id)
    {
        // Loading node information
        $this->database->Table("nodes");
        $this->database->Column("*");
        $this->database->Argument(1, "node_id", $node_id);
        $this->database->Argument(2, "status", 1);
        $this->database->Select();
        $result = $this->database->Execute();

        if ($data = $this->database->fetch_assoc($result)) {
            $this->database->Table("nodes_content");
            $this->database->Argument(1, "node_id", $node_id);
            $this->database->Argument(2, "locale", $this->database->bind($this->config->locale));
            $this->database->Select();
            $result1 = $this->database->Execute();

            // Locale information
            if ($row = $this->database->fetch_assoc($result1)) {
                $data['locale'] = $row['locale'];

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

        $this->database->Table("nodes n");
        $this->database->Column("n.node_id, n.option_name, n.complement, n.link");
        $this->database->Argument(1, "n.node_id", $node_id);
        $this->database->Argument(2, "n.status", 1);
        $this->database->Select();
        $result = $this->database->Execute();

        if ($data = $this->database->fetch_assoc($result)) {
            // Get locale
            $this->database->Table("nodes_content");
            $this->database->Argument(1, "node_id", $node_id);
            $this->database->Argument(2, "locale", $this->database->bind($this->config->locale));
            $this->database->Select();
            $result1 = $this->database->Execute();

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

        $this->database->Table("nodes");
        $this->database->Argument(1, "parent_id", $node_id);
        $this->database->Argument(2, "status", 1);
        $this->database->Order("ordered, node_id");
        $this->database->Select();
        $result = $this->database->Execute();

        // List
        while ($data = $this->database->fetch_assoc($result)) {
            // Get locale
            $this->database->Table("nodes_content");
            $this->database->Argument(1, "node_id", $data['node_id']);
            $this->database->Argument(2, "locale", $this->database->bind($this->config->locale));
            $this->database->Select();
            $result1 = $this->database->Execute();

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
    public function getList($node_id)
    {
        $content = '';

        $this->database->Table("nodes");
        $this->database->Argument(1, "parent_id", $node_id);
        $this->database->Argument(2, "status", 1);
        $this->database->Order("ordered, node_id");
        $this->database->Select();
        $result = $this->database->Execute();

        // List
        while ($data = $this->database->fetch_assoc($result)) {
            // Get locale
            $this->database->Table("nodes_content");
            $this->database->Argument(1, "node_id", $data['node_id']);
            $this->database->Argument(2, "locale", $this->database->bind($this->config->locale));
            $this->database->Select();
            $result1 = $this->database->Execute();

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
            $url = $this->nodeLink($data);

            $content .= "<li><a href='$url'>{$data['title']}</a></li>";
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

        $this->database->Table("nodes");
        $this->database->Argument(1, "parent_id", $node_id);
        $this->database->Argument(2, "status", 1);
        $this->database->Order("ordered, node_id");
        $this->database->Select();
        $result = $this->database->Execute();

        // Blog
        while ($data = $this->database->fetch_assoc($result)) {
            // Get locale
            $this->database->Table("nodes_content");
            $this->database->Argument(1, "node_id", $data['node_id']);
            $this->database->Argument(2, "locale", $this->database->bind($this->config->locale));
            $this->database->Select();
            $result1 = $this->database->Execute();

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
        $this->database->Table("nodes n");
        $this->database->Innerjoin("nodes_content c", "n.node_id = c.node_id AND c.locale = '{$this->config->locale}'");
        $this->database->Column("n.node_id, n.option_name, n.complement, c.title, c.info, c.description, c.link");
        $this->database->Argument(1, "lower(c.title)", "lower($v)", "LIKE");
        $this->database->Argument(2, "lower(c.description)", "lower($v)", "LIKE");
        $this->database->Argument(3, "lower(c.info)", "lower($v)", "LIKE");
        $this->database->Argument(4, "status", 1);
        $this->database->Where("((1) OR (2) OR (3)) AND (4)");
        $this->database->Select();
        $result = $this->database->Execute();

        while ($row = $this->database->fetch_assoc($result)) {
            // Description
            if (! $row['description']) {
                $info = preg_replace("/\r\n/", " ", strip_tags($row['info']));
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
        $this->database->Table("nodes n");
        $this->database->Leftjoin("nodes_content c", "n.node_id = c.node_id AND c.locale = '{$this->config->locale}'");
        $this->database->Column("n.*, c.link");
        $this->database->Argument(1, "status", 1);
        $this->database->Select();
        $result = $this->database->Execute();

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
            $this->database->Table("nodes n");
            $this->database->Leftjoin("nodes_content c", "n.node_id = c.node_id AND c.locale = '{$locale}'");
            $this->database->Column("n.node_id, n.parent_id, n.option_name, n.complement, c.title, c.link");
            $this->database->Argument(1, "n.node_id", $parent_id);
            $this->database->Select();
            $result = $this->database->Execute();

            if ($row = $this->database->fetch_assoc($result)) {
                if ($row['parent_id']) {
                    // Recursive call in case there is a parent for this node
                    $node = $this->getBreadcrumb($row['parent_id']);
                    if ($node) {
                        $node = $node . ' › ';
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
}
