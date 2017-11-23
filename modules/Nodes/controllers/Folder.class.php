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

class Folder extends Nodes
{
    public function get($row)
    {
        // Return text
        $content = '';

        // List
        $nodes = new \models\Nodes();

        // Get children
        $children = $nodes->getChildren($row['node_id']);

        // Get parent content
        if ($row['format'] == 99) {
            // Custom
            foreach ($children AS $k => $v) {
                $t = $row['template_children'];
                foreach ($v as $k1 => $v1) {
                    $t = str_replace("[$k1]", $v1, $t);
                }

                if (! isset($row['children'])) {
                    $row['children'] = '';
                }
                $row['children'] .= $t;
            }

            // Custom TODO in case nodes do not exists append automatically in the end.
            $t = $row['template'];
            foreach ($row as $k => $v) {
                $t = str_replace("[$k]", $v, $t);
            }

            $content .= $t;
        } else if ($row['format'] == 1) {
            if (count($children)) {
                foreach ($children as $k => $v) {
                    $content .= "<li><a href='{$v['url']}'>{$v['title']}</a></li>\n";
                }

                if ($content) {
                    $content = "<ul class='folder'>$content</ul>";
                }
            }

            // Create folder content
            $content = "<div class='children'>$content</div>";
        } else {
            if (count($children)) {
                foreach ($children as $k => $v) {
                    $content .= "<li><a href='{$v['url']}'>{$v['title']}</a></li>\n";
                }

                if ($content) {
                    $content = "<ul class='folder'>$content</ul>";
                }
            }

            // Create folder content
            $content = "<div class='breadcrumb'>{$row['breadcrumb']}</div><div class='title'>{$row['title']}</div><div class='description'>{$row['summary']}</div><div class='children'>$content</div>";
        }

        return $content;
    }
}
