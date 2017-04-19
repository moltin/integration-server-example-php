<?php

namespace IntegrationServer\Handlers; 

class WebhookHandler extends BaseHandler
{

    public function incoming(\Slim\Http\Request $request, \Slim\Http\Response $response, $args)
    {
        // get the payload
        $payload = $request->getAttribute('payload');

        // parse the trigger and create $entity and $action vars
        $parsedTrigger = explode('.', $payload->trigger);
        $entity = ucfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $parsedTrigger[0]))));
        $action = strtolower($parsedTrigger[1]);

        // if we have a matching $entity class and a method with the $action, fire it
        $entityClass = '\IntegrationServer\Entities\\'.$entity;
        if (class_exists($entityClass) && method_exists($entityClass, $action)) {
            $e = new $entityClass();
            $e->$action('webhook', $payload);
        }

        // Note: the body is not required in the response, nothing is done with
        // it on our side, it's here to help with your debugging
        return $response->withJson(['acknowledged' => true], 200);
    }

}
