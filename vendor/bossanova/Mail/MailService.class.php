<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Mail Interface
 */
namespace Bossanova\Mail;

interface MailService
{
    public function login(array $config);

    public function addTo($email, $name = null);

    public function addAddress($email, $name = null);

    public function setFrom($email, $name);

    public function setReplyTo($replyTo);

    public function setSubject($subject);

    public function setHtml($html);

    public function setText($text);

    public function addAttachment($path, $name);

    public function setDebug($value = false);

    public function send();

    public function error();
}
