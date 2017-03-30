<?php

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require '../../vendor/autoload.php';

/**
 *  Load ENV vars from a file if they are supplied
 */
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
    $dotenv->load();
}

if (getenv('APP_DEBUG') == "true") {
    error_reporting(E_ALL);
}

/**
 *  Create a Slim\App
 */
$app = new \Slim\App(['settings' => ['displayErrorDetails' => (getenv('APP_DEBUG') == "true")]]);

/**
 *  Add a middleware function to verify the X-MOLTIN-SECRET-KEY header
 *  and reject unverified calls
 */
$app->add(function ($request, $response, $next) {

    if (
        getenv('MOLTIN_INTEGRATION_SECRET') && (
            empty($request->getHeader('X-MOLTIN-SECRET-KEY')) ||
            $request->getHeader('X-MOLTIN-SECRET-KEY')[0] !== getenv('MOLTIN_INTEGRATION_SECRET')
        )
    ) {
        return $response->withJson([
            'meta' => 'Your request is unverified. Please try again using the correct secret key.'
        ], 401);
    }

    return $next($request, $response);
});

/**
 *  Add a middleware to get the payload from the body
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

/**
 *  Get the App's container so we can inject into it
 */
$container = $app->getContainer();

$container['errorHandler'] = function ($c) {
    return new \IntegrationServer\Exceptions\ExceptionHandler();
};

$container['phpErrorHandler'] = function ($container) {
    return $container['errorHandler'];
};

/**
 *  Register a logger (default goes to disk)
 */
$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('example.integration.server');
    $file_handler = new \Monolog\Handler\StreamHandler("../logs/app.log");
    $logger->pushHandler($file_handler);
    return $logger;
};

/**
 * Receive a root request. Nothing to do but show a message...
 */
$app->get('/', function($request, $response, $args) {
    return $response->withJson([
        'meta' => 'Welcome to the moltin PHP integration example'
    ], 200);
});

/**
 * Receive an incoming notification of webhook integration type
 */
$app->post('/webhook', IntegrationServer\Handlers\WebhookHandler::class . ":incoming");

/**
 * Receive an incoming notification of email
 *
 * When your integration is an email and you have specified an email_body_url 
 * this endpoint will be hit
 */
$app->post('/email', IntegrationServer\Handlers\EmailHandler::class . ":incoming");

// let's go
$app->run();
