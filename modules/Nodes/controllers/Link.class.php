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

class Link extends Nodes
{
    public function __default()
    {
        $this->setView(false);
    }

    public function get($row)
    {
        header("Location:/{$row['url']}");
    }
}
