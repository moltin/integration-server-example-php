<?php

namespace IntegrationServer\Entities;

use \IntegrationServer\Services\Email;

class PaymentGateway extends \IntegrationServer\Entity
{

    public function updated($type, $payload)
    {
        if ($type === 'webhook') {

            if (($slack = $this->slack())) {

                $message = 'A payment gateway has been updated';
                if (($gateway = $this->getResource('gateway', $payload))) {
                    $forgeLink = $this->forgeLink("gateways/" . $gateway->slug);
                    $message .= " (" . $gateway->code . ")";
                    $message .= "\n<" . $forgeLink . "|View on Forge>";
                }

                $slack->send($message);
            }
        }

        if ($type === 'email') {

            $email = new Email();
            $email->setSubject("Payment Gateway Updated");
            $email->setHTML("<p>A payment gateway has been updated</p>");
            $email->setPlain("A payment gateway has been updated");
            return $email->getResponseBody();
        }
    }
}
