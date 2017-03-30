<?php

namespace IntegrationServer\Entities;

use IntegrationServer\Services\Email;

class Order extends \IntegrationServer\Entity
{

    public function created($type, $payload)
    {

        if ($type === 'webhook') {

            if (($slack = $this->slack())) {

                $message = 'An order has been created on your store';
                $fallback = "New order";

                if (($order = $this->getResource('order', $payload))) {

                    $forgeLink = $this->forgeLink("admin/orders/" . $order['id']);
                    $message .= "\n<" . $forgeLink . "|View on Forge>";
                    $attachment = [
                        "text" => false,
                        "fallback" => false,
                        "author_name" => $order['customer']['name'],
                        "author_link" => $forgeLink,
                        "fields" => [
                            [
                                "title" => "Value",
                                "value" => $order['meta']['value']['with_tax']['formatted'],
                                "short" => true
                            ],
                            [
                                "title" => "Products",
                                "value" => $order['meta']['counts']['products']['total'] . " (total) / " . $order['meta']['counts']['products']['unique'] . " (unique)",
                                "short" => true
                            ]
                        ]
                    ];
                }

                $slack->send($message, $attachment);
            }
        }

        if ($type === 'email') {

            $email = new Email();

            $email->setSubject("♥ Thanks for your order. You Superstar!");
            if (($order = $this->getResource('order', $payload))) {
                $email->setSubject("♥ Thanks for your order (" . $order['id'] . "). You Superstar!");
            }


            $email->setHTML("<p>Wow. You've done some mighty fine work getting yourself some good swag.</p><p>We've got your order and are processing it now.</p><p>We'll be in touch soon!</p>");

            $email->setPlain("Wow. You've done some mighty fine work getting yourself some good swag.\r\nWe've got your order and are processing it now. We'll be in touch soon!");

            // let's call moltin and add some products to the email body
            if (($moltin = $this->moltin())) {

                $products = $moltin->products->sort('name')->limit(10)->all()->data();

                if (!empty($products)) {
                    $email->setPlain($email->getPlain() . "\n\nPS, why not take a look around some more of our products whilst you're wating:\n\n");
                    $email->setHTML($email->getHTML(), "<p>PS, why not take a look around some more of our products whilst you're wating:</p><ul>");
                    foreach($products as $product) {
                        $email->setPlain($email->getPlain() . $product->name . ": http://yourstore.com/products/" . $product->id . "\n");
                        $email->setHTML($email->getHTML() . "<li><a href=http://yourstore.com/products/\"" . $product->id . "\">" . $product->name . "</a></li>");
                    }
                    $email->setHTML($email->getHTML() . "</ul>");
                }
            }

            return $email->getResponseBody();
        }
    }

}
