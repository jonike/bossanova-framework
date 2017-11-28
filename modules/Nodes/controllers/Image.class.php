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

class Image extends Nodes
{
    public function __default()
    {
        $this->setView(false);
    }

    public function get($row)
    {
        $content = '';

        if (isset($row['files']) && count($row['files'])) {
            if ($row['files'][0]) {
                $content = $row['files'][0]['content'];
            }
        }

        if ($content) {
            // File information
            $fileData = explode(';', $content);
            // Mime
            $mimeType = str_replace('data:', '', $fileData[0]);
            // Content
            $fileContent = str_replace('base64,', '', $fileData[1]);

            // Show file
            header("Content-type:$mimeType\r\n");
            echo base64_decode($fileContent);
            exit;
        }
    }
}