<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Login
 */

namespace modules\Login;

use bossanova\Module\Module;

class Login extends Module
{
    public function __default () {
        if (isset($_POST)) {
            $this->login();
        }
    }
}
