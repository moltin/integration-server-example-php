<?php

namespace IntegrationServer\Entities;

use \IntegrationServer\Services\Email;

class Product extends \IntegrationServer\Entity
{

    public function created($type, $payload)
    {
        if ($type === 'webhook') {

            if (($slack = $this->slack())) {

                $message = 'A product has been created';
                if (($product = $this->getResource('product', $payload))) {
                    $forgeLink = $this->forgeLink("catalogue/products/" . $product->id);
                    $message .= " (" . $product->name . ")";
                    $message .= "\n<" . $forgeLink . "|View on Forge>";
                }

                $slack->send($message);
            }
        }

        if ($type === 'email') {

            $email = new Email();
            $email->setSubject("Product Updated");
            $email->setHTML("<p>A product has been updated</p>");
            $email->setPlain("A product has been updated");
            $email->cancel();
            return $email->getResponseBody();
        }
    }

}
