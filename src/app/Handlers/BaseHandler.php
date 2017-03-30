<?php

namespace IntegrationServer\Handlers; 

class BaseHandler
{

    public function __construct(\Slim\Container $c)
    {
        // IntegrationServer\Handlers\EmailHandler -> Email
        $reflect = new \ReflectionClass($this);
        $type = str_replace('Handler', '', $reflect->getShortName());
        $c->logger->addInfo(
            $type . " integration notification received (" .
            $c->request->getHeader('X-MOLTIN-INTEGRATION-TRIGGER')[0] . 
            ")"
        );
        return $this;
    }

}
