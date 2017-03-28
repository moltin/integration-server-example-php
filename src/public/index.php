<?php

require '../../vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();

$config = [
    'settings' => [
        'displayErrorDetails' => true
    ],
];

$app = new \Slim\App($config);

/**
 *  Add a middleware function to verify the X-MOLTIN-SECRET-KEY header
 *  and reject unverified calls
 */
$app->add(function ($request, $response, $next) {

    if (
        getenv('INTEGRATION_SECRET') && (
            empty($request->getHeader('X-MOLTIN-SECRET-KEY')) ||
            $request->getHeader('X-MOLTIN-SECRET-KEY')[0] !== getenv('INTEGRATION_SECRET')
        )
    ) {
        return $response->withJson([
            'meta' => 'Your request is unverified. Please try again using the correct secret key.'
        ], 401);
    }

    return $next($request, $response);
});

/**
 *  Add a middleware to get the payload
 */
$app->add(function($request, $response, $next) {

    $body = $request->getParsedBody();
    $payload = new \StdClass();
    $payload->jobID = $body['id'];
    $payload->trigger = $body['triggered_by'];
    $payload->attempt = (int) $body['attempt'];
    $payload->integration = $body['integration'];
    $payload->resources = isset($body['resources']) ? $body['resources'] : [];

    // add the payload to the request
    $request = $request->withAttribute('payload', $payload);

    return $next($request, $response);
});

$container = $app->getContainer();

/**
 *  Add the moltin API as a service so you can make API calls
 */
$container['moltin']  = new \Moltin\Client([
    'client_id' => getenv('CLIENT_ID'),
    'client_secret' => getenv('CLIENT_SECRET'),
    'currency_code' => getenv('CURRENCY_CODE'),
    'language' => getenv('LANGUAGE'),
    'locale' => getenv('LOCALE')
]);

$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('example.integration.server');
    $file_handler = new \Monolog\Handler\StreamHandler("../logs/app.log");
    $logger->pushHandler($file_handler);
    return $logger;
};

/**
 *  Add a slack webhook to the container
 */
$container['slack'] = new \Maknz\Slack\Client(getenv('SLACK_WEBHOOK'));

/**
 * Receive a root request. Nothing to do but show a message
 */
$app->get('/', function($request, $response, $args) {

    $json = [
        'meta' => 'Welcome to the moltin PHP integration example'
    ];

    return $response->withJson($json, 200);
});

/**
 *  NOTE you will want to abstract this, probably into anther class
 */
function sendSlackNewOrderNotification($slack, $from, $to, $icon, $payload) {

    $notification = $slack
        ->from($from)
        ->to($to)
        ->withIcon($icon);

    $message = 'An order has been created on your store';
    $fallback = "New order";
    $colour = "#3AA3E3";

    $order = false;
    foreach($payload->resources as $resource) {
        if ($resource['type'] === "order") {
            $forgeLink = "https://forge.moltin.com/admin/orders/" . $resource['id'];
            $message .= "\n<" . $forgeLink . "|View the order on Forge>";
            $attachment = [
                "text" => false,
                "fallback" => false,
                "author_name" => $resource['customer']['name'],
                "author_link" => $forgeLink,
                "color" => $colour,
                "fields" => [
                    [
                        "title" => "Value",
                        "value" => $resource['meta']['value']['with_tax']['formatted'],
                        "short" => true
                    ],
                    [
                        "title" => "Products",
                        "value" => $resource['meta']['counts']['products']['total'] . " (total) / " . $resource['meta']['counts']['products']['unique'] . " (unique)",
                        "short" => true
                    ]
                ]
            ];
            $notification->attach($attachment);
            break;
        }
    }

    $notification->send($message);
}

function sendSlackNewProductNotification($slack, $from, $to, $icon, $payload) {

    $notification = $slack
        ->from($from)
        ->to($to)
        ->withIcon($icon);

    $message = 'A product has been created';
    foreach($payload->resources as $resource) {
        if ($resource['type'] === "product") {
            $forgeLink = "https://forge.molt.in/inventory/products/" . $resource['id'];
            $message .= " (" . $resource['name'] . ")";
            $message .= "\n<" . $forgeLink . "|View the product on Forge>";
            break;
        }
    }

    $notification->send($message);
}

/**
 * Receive an incoming notification of webhook integration type
 */
$app->post('/webhook', function($request, $response, $args) {

    // add a log entry
    $this->logger->addInfo("Webhook integration notification received (" . $request->getHeader('X-MOLTIN-INTEGRATION-TRIGGER')[0] . ")");

    // get the payload
    $payload = $request->getAttribute('payload');

    // Do something with it. Ideally this should be done after the response is
    // sent so that we can acknowledge receipt and do not attempt to resend the
    // webhook. If you cannot send us a response in time, you will receive
    // multiple notifications of the same job as we retry delivery.
    if ($payload->trigger === "order.created") {

        if (!empty(getenv('SLACK_WEBHOOK'))) {
            sendSlackNewOrderNotification(
                $this->slack,               // container
                getenv('SLACK_USERNAME'),   // from
                getenv('SLACK_CHANNEL'),    // to
                getenv('SLACK_ICON'),       // icon URL
                $payload                    // payload
            );
        }

        // do other stuff

    }

    if ($payload->trigger === "product.created") {

        if (!empty(getenv('SLACK_WEBHOOK'))) {
            sendSlackNewProductNotification(
                $this->slack,               // container
                getenv('SLACK_USERNAME'),   // from
                getenv('SLACK_CHANNEL'),    // to
                getenv('SLACK_ICON'),       // icon URL
                $payload                    // payload
            );
        }
    }

    // Note: the body is not required in the response, nothing is done with
    // it on our side, it's here to help with your debugging
    $body = ['acknowledged' => true];
    return $response->withJson($body, 200);
});

/**
 * Receive an incoming notification of email
 *
 * When your integration is an email and you have specified an email_body_url 
 * this endpoint will be hit
 */
$app->post('/email', function($request, $response, $args) {

    // add a log entry
    $this->logger->addInfo("Email integration notification received (" . $request->getHeader('X-MOLTIN-INTEGRATION-TRIGGER')[0] . ")");

    // Should we proceed with the delivery of this email?
    // Do some of your own checks if required and tell us if you want to
    // abandon delivery of the email by setting this to false.
    // If you do set it to false, we will not attempt any more deliveries
    // of this integration
    $proceed = true;

    // When we receive a response you must have one of (or both) of these
    // variables set to strings so that we have an email body to send
    $html = "";
    $plain = "";

    // If you want to change the email subject dynamically, do so here in
    // accordance with https://tools.ietf.org/html/rfc2822
    $subject = "";

    // get the payload
    $payload = $request->getAttribute('payload');

    // do something with it
    if ($payload->trigger === "order.created") {

        // default subject
        $subject = "♥ Thanks for your order. You Superstar!";
        foreach($payload->resources as $resource) {
            if ($resource['type'] === "order" && !empty($resource['id'])) {
                // change the email subject
                $subject = "♥ Thanks for your order (" . $resource['id'] . "). You Superstar!";
            }
        }

        $html = "<p>Wow. You've done some mighty fine work getting yourself some good swag.</p><p>We've got your order and are processing it now.</p><p>We'll be in touch soon!</p>";

        $plain = "Wow. You've done some mighty fine work getting yourself some good swag.

We've got your order and are processing it now. We'll be in touch soon!";

        // let's call moltin and add some products to the email body
        if (!empty(getenv('CLIENT_ID')) && !empty(getenv('CLIENT_SECRET'))) {

            $products = $this->moltin->products->sort('name')->limit(10)->all()->data();

            if (!empty($products)) {
                $plain .= "\n\nPS, why not take a look around some more of our products whilst you're wating:\n\n";
                $html .= "<p>PS, why not take a look around some more of our products whilst you're wating:</p><ul>";
                foreach($products as $product) {
                    $plain .= $product->name . ": http://yourstore.com/products/" . $product->id . "\n";
                    $html .= "<li><a href=http://yourstore.com/products/\"" . $product->id . "\">" . $product->name . "</a></li>";
                }
                $html .= "</ul>";
            }

        }
    }

    // respond
    $json = [
        'proceed' => $proceed,
        'email' => [
            'subject' => $subject,
            'html' => $html,
            'plain' => $plain
        ]
    ];

    // this is not needed or used - it is a utility to echo back
    // the incoming request for debugging purposes
    if (getenv('DEBUG') === "true") {
        $json['debug'] = [
            'payload' => $payload
        ];
    }

    // We require a JSON body and a 200 response
    return $response->withJson($json, 200);
});

$app->run();
