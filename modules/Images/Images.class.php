<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Image loader
 */
namespace modules\Images;

use Bossanova\Module\Module;

class Images extends Module
{
    public function __default()
    {
        if ($id = (int) $this->getParam(1)) {
            $this->setLayout(0);

            $this->query->Table("nodes");
            $this->query->Column("complement, info");
            $this->query->Argument(1, "node_id", $id);
            $this->query->Select();
            $result = $this->query->Execute();
            $row = $this->query->fetch_assoc($result);

            header("Content-type:" . $row['complement']);
            echo base64_decode($row['info']);
            exit();
        }
    }
}
