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
        header("Location:/{$row['url']}");
    }
}
