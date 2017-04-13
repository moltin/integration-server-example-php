<?php

namespace IntegrationServer\Entities;

use \IntegrationServer\Services\Email;

class Currency extends \IntegrationServer\Entity
{

    public function created($type, $payload)
    {
        if ($type === 'webhook') {

            if (($slack = $this->slack())) {

                $message = 'A currency has been created';
                if (($currency = $this->getResource('currency', $payload))) {
                    $forgeLink = $this->forgeLink("currencies/" . $currency->id);
                    $message .= " (" . $currency->code . ")";
                    $message .= "\n<" . $forgeLink . "|View on Forge>";
                }

                $slack->send($message);
            }
        }

        if ($type === 'email') {

            $email = new Email();
            $email->setSubject("Currency Updated");
            $email->setHTML("<p>A currency has been created</p>");
            $email->setPlain("A currency has been created");
            return $email->getResponseBody();
        }
    }

    public function updated($type, $payload)
    {
        if ($type === 'webhook') {

            if (($slack = $this->slack())) {

                $message = 'A currency has been updated';
                if (($currency = $this->getResource('currency', $payload))) {
                    $forgeLink = $this->forgeLink("currencies/" . $currency->id);
                    $message .= " (" . $currency->code . ")";
                    $message .= "\n<" . $forgeLink . "|View on Forge>";
                }

                $slack->send($message);
            }
        }

        if ($type === 'email') {

            $email = new Email();
            $email->setSubject("Currency Updated");
            $email->setHTML("<p>A currency has been updated</p>");
            $email->setPlain("A currency has been updated");
            return $email->getResponseBody();
        }
    }

    public function deleted($type, $payload)
    {
        if ($type === 'webhook') {

            if (($slack = $this->slack())) {

                $message = 'A currency has been deleted';
                $slack->send($message);
            }
        }

        if ($type === 'email') {

            $email = new Email();
            $email->setSubject("Currency Deleted");
            $email->setHTML("<p>A currency has been deleted</p>");
            $email->setPlain("A currency has been deleted");
            return $email->getResponseBody();
        }
    }
}
