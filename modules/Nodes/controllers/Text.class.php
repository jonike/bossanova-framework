<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Nodes
 */
namespace modules\Nodes\controllers;

use modules\Nodes\Nodes;

class Text extends Nodes
{
    public function get($row)
    {
        $index = '';

        // Return text
        $content = $row['breadcrumb'];

        if ($row['format'] == 2) {
            // Article Format
            $row['posted'] = substr($row['posted'], 0, 19);
            $row['updated'] = substr($row['updated'], 0, 19);
            $row['description'] = strip_tags($row['description']);

            $content .= "<div itemscope itemtype='http://schema.org/Article' class='article'>";
            $content .= "<meta itemscope itemprop='mainEntityOfPage' itemType='https://schema.org/WebPage' itemid='{$row['parent_url']}' />";

            $content .= "<div class='title' itemprop='headline'>{$row['title']}</div>";
            $content .= "<div class='author' itemprop='Author'>{$row['author']}</div>";
            $content .= "<div class='publisher' itemprop='publisher' style='display:none;' itemscope itemtype='http://schema.org/Organization'><span itemprop='name'>{$row['author']}</span> <span itemprop='logo' itemscope itemtype='https://schema.org/ImageObject'></span></div>";
            $content .= "<div class='date' itemprop='datePublished' content='{$row['published_from']}'>{$row['published_from']}</div>";
            $content .= "<div class='description' itemprop='description'>{$row['content']}</div>";
            $content .= "<div class='keywords' itemprop='keywords'><i>{$row['keywords']}</i></div>\n";

            $content .= "</div>";
        } else if ($row['format'] == 99) {
            // Custom
            $content = $row['format'];

            foreach ($row as $k => $v) {
                $content = str_replace("[$k]", $v, $content);
            }
        } else {
            // If the text has automatic indexes
            if ($row['format'] == 1) {
                // Create index options base on H1, H2, H3
                preg_match_all("/<h[1-9]>(.*?)<\/h[1-9]>/i", $row['content'], $h1);

                // Create tabulation
                foreach ($h1[1] as $k => $v) {
                    $c = substr($h1[0][$k], 1, 2) == 'h3' ? 'second' : 'first';

                    // Link
                    if (! isset($row['node_link']) || ! $row['node_link']) {
                        $row['node_link'] = 'nodes/' . $row['node_id'];
                    }

                    // Create link
                    $index .= "<li class='{$c}'>" . str_replace('name="', 'href="/' . $row['node_link'] . '#', $v) . "</li>";
                }

                // Content
                if ($index) {
                    $index = "<ul class='index'>$index</ul>";
                }
            }

            // Default
            $content .= "<h1 class='title'>{$row['title']}</h1>$index<p>{$row['content']}</p>";
        }

        return $content;
    }
}
