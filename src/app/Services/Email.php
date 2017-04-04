<?php

namespace IntegrationServer\Services;

class Email
{

    // Should we proceed with the delivery of this email?
    // Do some of your own checks if required and tell us if you want to
    // abandon delivery of the email by setting this to false.
    // If you do set it to false, we will not attempt any more deliveries
    // of this integration
    private $proceed = true;

    // When we receive a response you must have one of (or both) of these
    // variables set to strings so that we have an email body to send
    private $subject = "";
    private $html = "";
    private $plain = "";

    public function cancel()
    {
        $this->proceed = false;
        return $this;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function getHTML()
    {
        return $this->html;
    }

    public function getPlain()
    {
        return $this->plain;
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    public function setHTML($html)
    {
        $this->html = $html;
        return $this;
    }

    public function setPlain($plain)
    {
        $this->plain = $plain;
        return $this;
    }

    public function getResponseBody()
    {
        return [
            'proceed' => $this->proceed,
            'email' => [
                'subject' => $this->subject,
                'html' => $this->html,
                'plain' => $this->plain
            ]
        ];
    }

}
