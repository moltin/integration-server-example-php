<?php

namespace IntegrationServer\Entities;

use \IntegrationServer\Services\Email;

class File extends \IntegrationServer\Entity
{

    public function created($type, $payload)
    {
        if ($type === 'webhook') {

            if (($slack = $this->slack())) {

                $message = 'A file has been created';
                if (($file = $this->getResource('file', $payload))) {
                    $forgeLink = $this->forgeLink("files/" . $file->id);
                    $message .= " (" . $file->name . ")";
                    $message .= "\n<" . $forgeLink . "|View on Forge>";
                }

                $slack->send($message);
            }
        }

        if ($type === 'email') {

            $email = new Email();
            $email->setSubject("File Updated");
            $email->setHTML("<p>A file has been created</p>");
            $email->setPlain("A file has been created");
            return $email->getResponseBody();
        }
    }

    public function updated($type, $payload)
    {
        if ($type === 'webhook') {

            if (($slack = $this->slack())) {

                $message = 'A file has been updated';
                if (($file = $this->getResource('file', $payload))) {
                    $forgeLink = $this->forgeLink("files/" . $file->id);
                    $message .= " (" . $file->name . ")";
                    $message .= "\n<" . $forgeLink . "|View on Forge>";
                }

                $slack->send($message);
            }
        }

        if ($type === 'email') {

            $email = new Email();
            $email->setSubject("File Updated");
            $email->setHTML("<p>A file has been updated</p>");
            $email->setPlain("A file has been updated");
            return $email->getResponseBody();
        }
    }

    public function deleted($type, $payload)
    {
        if ($type === 'webhook') {

            if (($slack = $this->slack())) {

                $message = 'A file has been deleted';
                $slack->send($message);
            }
        }

        if ($type === 'email') {

            $email = new Email();
            $email->setSubject("File Deleted");
            $email->setHTML("<p>A file has been deleted</p>");
            $email->setPlain("A file has been deleted");
            return $email->getResponseBody();
        }
    }
}
