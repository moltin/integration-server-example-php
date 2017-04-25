<?php

namespace IntegrationServer\Entities;

use \IntegrationServer\Services\Email;

class Settings extends \IntegrationServer\Entity
{

    public function created($type, $payload)
    {
        if ($type === 'webhook') {

            if (($slack = $this->slack())) {

                $message = 'Settings have been created';
                if (($settings = $this->getResource('settings', $payload))) {
                    $forgeLink = $this->forgeLink("admin/settings");
                    $message .= "\n<" . $forgeLink . "|View on Forge>";
                }

                $slack->send($message);
            }
        }

        if ($type === 'email') {

            $email = new Email();
            $email->setSubject("Settings Created");
            $email->setHTML("<p>Settings have been created</p>");
            $email->setPlain("Settings have been created");
            $email->cancel();
            return $email->getResponseBody();
        }
    }

    public function updated($type, $payload)
    {
        if ($type === 'webhook') {

            if (($slack = $this->slack())) {

                $message = 'Settings have been updated';
                if (($settings = $this->getResource('settings', $payload))) {
                    $forgeLink = $this->forgeLink("admin/settings");
                    $message .= "\n<" . $forgeLink . "|View on Forge>";
                }

                $slack->send($message);
            }
        }

        if ($type === 'email') {

            $email = new Email();
            $email->setSubject("Settings Updated");
            $email->setHTML("<p>Settings have been updated</p>");
            $email->setPlain("Settings have been updated");
            $email->cancel();
            return $email->getResponseBody();
        }
    }
}
