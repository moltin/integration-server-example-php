<?php

namespace IntegrationServer\Services;

class Slack
{

    private $client;

    private static $config = [
        'username' => 'moltin',
        'channel' => false,
        'icon' => 'https://s3-us-west-2.amazonaws.com/slack-files2/avatars/2016-06-28/54965810756_60c820c221e8e648ba90_88.jpg'
    ];

    /**
     *
     */
    public function __construct()
    {
        $this->client = new \Maknz\Slack\Client(getenv('SLACK_WEBHOOK'));

        if (getenv('SLACK_USERNAME')) {
            self::$config['username'] = getenv('SLACK_USERNAME');
        }
        if (getenv('SLACK_CHANNEL')) {
            self::$config['channel'] = getenv('SLACK_CHANNEL');
        }
        if (getenv('SLACK_ICON')) {
            self::$config['icon'] = getenv('SLACK_ICON');
        }

        return $this;
    }

    /**
     *
     */
    public function send($message, $attachment = false)
    {

        if (empty(self::$config['channel'])) {
            return false;
        }

        $notification = $this->client
            ->from(self::$config['username'])
            ->to(self::$config['channel'])
            ->withIcon(self::$config['icon']);
        
        if ($attachment) {
            $notification->attach($attachment);
        }

        return $notification->send($message);
    }

}
