<?php

/**
 * (c) 2013 Bossanova PHP Framework
 * http://www.bossanova-framework.com
 *
 * @author: Paul Hodel <paul.hodel@gmail.com>
 * @description: Sendgrid
 */
namespace Bossanova\Mail;

class AdapterSendgrid implements MailService
{
    /**
     * Debug
     *
     * @var $debug
     */
    public $debug = false;

    /**
     * Sendmail adapter
     *
     * @var $adapter
     */
    protected $adapter = null;

    /**
     * Customization
     *
     * @var $personalization
     */
    protected $personalization = null;

    public function login(array $config)
    {
        $this->adapter = new \SendGrid\Mail();
        $this->personalization = new \SendGrid\Personalization();
    }

    public function addTo($email, $name = null)
    {
    	$email = new \Sendgrid\Email($name, $email);
        $this->personalization->addTo($email);
    }

    public function addAddress($email, $name = null)
    {
    	$email = new \Sendgrid\Email($name, $email);
        $this->personalization->addTo($email);
    }

    public function setFrom($email, $name = null)
    {
        $email = new \Sendgrid\Email($name, $email);
        $this->adapter->setFrom($email);
    }

    public function setReplyTo($email, $name = null)
    {
        $email = new \Sendgrid\Email($name, $email);
        $this->adapter->setReplyTo($replyTo);
    }

    public function setSubject($subject)
    {
        $this->personalization->setSubject($subject);
    }

    public function setHtml($html)
    {
        $content = new \Sendgrid\Content("text/html", $html);
        $this->adapter->addContent($content);
    }

    public function setText($text)
    {
        $content = new \Sendgrid\Content("text/plain", $text);
        $this->adapter->addContent($content);
    }

    public function addAttachment($path, $name)
    {
        //$attachment = new \Sendgrid\Attachment();
        //$this->adapter->addAttachment($path, $name);
    }

    public function setDebug($value = false)
    {
        $this->debug = $value;
    }

    public function send()
    {
        $sg = new \SendGrid(MS_CONFIG_KEY);

        if ($this->personalization) {
            $this->adapter->addPersonalization($this->personalization);
        }

        $response = $sg->client->mail()->send()->post($this->adapter);

        if ($this->debug == true) {
            echo $response->statusCode();
            echo $response->body();
            echo $response->headers();
        }
    }

    public function error()
    {
        // @TODO: return error
    }
}
