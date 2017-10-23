<?php
/**
 * (c) 2013 Bossanova PHP Framework 3
 * http://www.bossanova-framework.com
 *
 * @category PHP
 * @package  BossanovaFramework
 * @author   Paul Hodel <paul.hodel@gmail.com>
 * @license  The MIT License (MIT)
 * @link     http://www.bossanova-framework.com
 *
 * Services Library
 */
namespace bossanova\Services;

use bossanova\Database\Database;
use bossanova\Mail\Mail;

class Services
{
    public $mail = null;

    public function __construct($instance = null)
    {
        $this->mail = new Mail();

        if (isset($instance)) {
            $this->database = $instance;
        } else {
            $this->database = Database::getInstance();
        }
    }

    /**
     * Default sendmail function, used by the modules to send used email
     *
     * @return void
     */
    protected function sendmail($to, $subject, $html, $from, $files = null)
    {
        ob_start();
        $instance = $this->mail->sendmail($to, $subject, $html, $from, $files);
        $result = ob_get_clean();

        return $instance;
    }

    /**
     * Remove special characters from the string
     *
     * @param  string $str
     * @return string
     */
    protected function escape($str)
    {
        $str = trim($str);

        if (get_magic_quotes_gpc()) {
            $str = stripslashes($str);
        }

        $str = htmlentities($str);
        $search = array("\\", "\0", "\n", "\r", "\x1a", "'", '"');
        $replace = array("", "", "", "", "", "", "");

        return str_replace($search, $replace, $str);
    }
}
