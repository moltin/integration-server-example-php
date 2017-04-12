<?php

namespace IntegrationServer\Entities;

use \IntegrationServer\Services\Email;

class Integration extends \IntegrationServer\Entity
{

    public function created($type, $payload)
    {
        if ($type === 'webhook') {

            if (($slack = $this->slack())) {

                $message = 'An integration has been created';
                if (($integration = $this->getResource('integration', $payload))) {
                    $forgeLink = $this->forgeLink("integrations/" . $integration->id);
                    $message .= " (" . $integration->name . ")";
                    $message .= "\n<" . $forgeLink . "|View on Forge>";
                }

                $slack->send($message);
            }
        }

        if ($type === 'email') {

            $email = new Email();
            $email->setSubject("Integration Updated");
            $email->setHTML("<p>An integration has been created</p>");
            $email->setPlain("An integration has been created");
            return $email->getResponseBody();
        }
    }

    public function updated($type, $payload)
    {
        if ($type === 'webhook') {

            if (($slack = $this->slack())) {

                $message = 'An integration has been updated';
                if (($integration = $this->getResource('integration', $payload))) {
                    $forgeLink = $this->forgeLink("integrations/" . $integration->id);
                    $message .= " (" . $integration->name . ")";
                    $message .= "\n<" . $forgeLink . "|View on Forge>";
                }

                $slack->send($message);
            }
        }

        if ($type === 'email') {

            $email = new Email();
            $email->setSubject("Integration Updated");
            $email->setHTML("<p>An integration has been updated</p>");
            $email->setPlain("An integration has been updated");
            return $email->getResponseBody();
        }
    }

    public function deleted($type, $payload)
    {
        if ($type === 'webhook') {

            if (($slack = $this->slack())) {

                $message = 'An integration has been deleted';
                $slack->send($message);
            }
        }

        if ($type === 'email') {

            $email = new Email();
            $email->setSubject("Integration Deleted");
            $email->setHTML("<p>An integration has been deleted</p>");
            $email->setPlain("An integration has been deleted");
            return $email->getResponseBody();
        }
    }
}
