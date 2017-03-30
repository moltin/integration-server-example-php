<?php

namespace IntegrationServer;

use IntegrationServer\Services\Slack;

class Entity
{

    public function slack()
    {
        return empty(getenv('SLACK_WEBHOOK')) ? false : new Slack();
    }

    public function moltin()
    {
        if (!empty(getenv('MOLTIN_CLIENT_ID')) && !empty(getenv('MOLTIN_CLIENT_SECRET'))) {
            return new \Moltin\Client([
                'client_id' => getenv('MOLTIN_CLIENT_ID'),
                'client_secret' => getenv('MOLTIN_CLIENT_SECRET'),
                'currency_code' => getenv('MOLTIN_CURRENCY_CODE'),
                'language' => getenv('MOLTIN_LANGUAGE'),
                'locale' => getenv('MOLTIN_LOCALE')
            ]);
        }
        return false;
    }

    public function getResource($type, $payload)
    {
        foreach($payload->resources as $resource) {
            if ($resource['type'] === $type) {
                return $resource;
            }
        }
        return false;
    }

    public function forgeLink($uri)
    {
        return "https://forge.molt.in/" . $uri;
    }

}
