<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Nodes Module (CMS Plugin)
 */
namespace modules\Nodes;

use Bossanova\Module\Module;

class Nodes extends Module
{
    /**
     * Node information
     *
     * @var $node
     */
    public $node = array();

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
            $this->node = $row;

            // Define global template information
            if ($metadata) {
                // Define global template information
                if ($this->node['title']) {
                    $this->setTitle($this->node['title']);
                }

                if ($this->node['author']) {
                    $this->setAuthor($this->node['author']);
                }

                if ($this->node['keywords']) {
                    $this->setKeywords($this->node['keywords']);
                }

                if ($this->node['description']) {
                    $this->setDescription(strip_tags($this->node['description']));
                }
            }

            // Main content
            $content = $this->{$this->node['option_name']}();
        }

        return isset($content) ? $content : '';
    }

    /**
     * Node is a text/article
     *
     * @return string $html
     */
    public function text()
    {
        $index = '';

        // Return text
        $content = $this->node['breadcrumb'];

        if ($this->node['complement'] == 3) {
            // Article Format
            $this->node['posted'] = substr($this->node['posted'], 0, 19);
            $this->node['updated'] = substr($this->node['updated'], 0, 19);
            $this->node['description'] = strip_tags($this->node['description']);

            $content .= "<div itemscope itemtype='http://schema.org/Article' class='article'>";
            $content .= "<meta itemscope itemprop='mainEntityOfPage' itemType='https://schema.org/WebPage' itemid='{$this->node['parent_url']}' />";

            $content .= "<div class='title' itemprop='headline'>{$this->node['title']}</div>";
            $content .= "<div class='author' itemprop='Author'>{$this->node['author']}</div>";
            $content .= "<div class='publisher' itemprop='publisher' style='display:none;' itemscope itemtype='http://schema.org/Organization'><span itemprop='name'>{$this->node['author']}</span> <span itemprop='logo' itemscope itemtype='https://schema.org/ImageObject'><meta itemprop='url' content='http://www.ue.com.br/logo.png'></span></div>";
            $content .= "<div class='date' itemprop='datePublished' content='{$this->node['published_from']}'>{$this->node['published_from']}</div>";
            $content .= "<div class='date' itemprop='dateCreated' style='display:none;'>{$this->node['posted']}</div>";
            $content .= "<div class='date' itemprop='dateModified' style='display:none;'>{$this->node['updated']}</div>";
            $content .= "<div class='description' itemprop='description'>{$this->node['info']}</div>";
            $content .= "<div class='keywords' itemprop='keywords'><i>{$this->node['keywords']}</i></div>\n";

            $content .= "</div>";
        } else {
            // If the text has automatic indexes
            if ($this->node['complement'] == 2) {
                // Create index options base on H1, H2, H3
                preg_match_all("/<h[1-9]>(.*?)<\/h[1-9]>/i", $this->node['info'], $h1);

                // Create tabulation
                foreach ($h1[1] as $k => $v) {
                    $c = substr($h1[0][$k], 1, 2) == 'h3' ? 'second' : 'first';

                    // Create link
                    $index .= "<li class='{$c}'>" . str_replace('name="', 'href="' . $this->node['url'] . '#', $v) . "</li>";
                }

                // Content
                if ($index) {
                    $index = "<ul class='index'>$index</ul>";
                }
            }

            // Default
            $content .= "<h1 class='title'>{$this->node['title']}</h1>$index<p>{$this->node['info']}</p>";
        }

        return $content;
    }

    /**
     * Node is a link return correspondent HTML
     *
     * @return void
     */
    public function link()
    {
        header("Location: {$this->node['complement']}");
        exit();
    }

    /**
     * Node is a folder return correspondent HTML
     *
     * @return string $html
     */
    public function folder()
    {
        $content = '';

        // Get parent content
        if ($this->node['complement'] == 2) {
            // Blog
            $nodes = new \models\Nodes();
            $content .= $nodes->getBlogContent($this->node['node_id']);
        } elseif ($this->node['complement'] == 1) {
            // List with descriptions
            $nodes = new \models\Nodes();
            $content .= $nodes->getListContent($this->node['node_id']);
        } else {
            // List
            $nodes = new \models\Nodes();
            $content .= $nodes->getList($this->node['node_id']);
        }

        // Create folder content
        $content = "<div class='description' itemprop='description'>{$this->node['info']}</div><ul class='folder'>$content</ul>";

        return $content;
    }

    /**
     * Node is a contact form
     *
     * @return string $html
     */
    public function contact()
    {
        if (isset($_POST['email']) && isset($_POST['message'])) {
            // Final user sending email
            if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                // Model instance
                $node = new \models\Nodes();

                // Get node informatino
                if ($node = $node->getById($this->getParam(1))) {
                    // Message
                    $message = "{$_POST['message']}\n\n<br><br>{$_POST['name']} ({$_POST['email']})";

                    // Can be phpmailer or sendgrid defined in config.inc.php
                    $this->sendmail($node['complement'], $node['title'], $message, array(
                        MS_CONFIG_FROM,
                        MS_CONFIG_NAME
                    ));

                    // Default return
                    if (! $node['description']) {
                        $node['description'] = "^^[Thank you for your contact. We will answer your message as soon as possible]^^.";
                    }

                    // Return to the interface
                    $content = $node['description'];
                }
            } else {
                // No valid email
                $content = "^^[Invalid email address]^^.";
            }
        } else {
            // Show HTML form
            $content = $this->node['breadcrumb'];

            $content .= "<div itemscope itemtype='http://schema.org/ContactPage' class='contact'>";

            $content .= "<input type='hidden' name='node_id' value='{$this->node['node_id']}'>";
            $content .= "<div class='title' itemprop='name'>{$this->node['title']}</div>";
            $content .= "<div class='description' itemprop='description'>{$this->node['info']}</div>";

            $content .= "<div><label>^^[Name]^^</label></div><div><input name='name' class='contact-field' style='width:400px'></div>\n";
            $content .= "<div><label>^^[Email]^^</label></div><div><input name='email' class='contact-field' style='width:400px'></div>\n";
            $content .= "<div><label itemprop='comment'>^^[Message]^^</label></div>\n";
            $content .= "<div><textarea style='width:400px;height:120px;' name='message' class='contact-field'></textarea></div><br>\n";
            $content .= "<div><input type='button' value='^^[Send message]^^' onclick='bossanova_message(this)'></div>\n";

            $content .= "</div>";
        }

        return $content;
    }

    /**
     * Node is an mage
     *
     * @return string $html
     */
    public function images()
    {
        $this->setLayout(0);
        header("Content-type:" . $this->node['complement']);
        return base64_decode($this->node['info']);
    }

    /**
     * Node is a file
     *
     * @return string $html
     */
    public function attach()
    {
        $this->setLayout(0);
        header("Content-type:" . $this->node['complement']);
        return base64_decode($this->node['info']);
    }

    /**
     * Sitemap for google and other crawlers
     *
     * @return string $xml
     */
    public function sitemap()
    {
        // Disable any layout
        $this->setLayout(0);

        // Set mimetype
        header("Content-type:text/xml\r\n");

        // Return XML
        $nodes = new \models\Nodes();

        return $nodes->sitemap();
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
}
