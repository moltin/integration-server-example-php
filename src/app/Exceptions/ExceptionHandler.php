<?php

namespace IntegrationServer\Exceptions;

class ExceptionHandler
{

    public function __invoke($request, $response, $exception) {

        $body = ['meta' => 'There was an error processing this request'];

        if (getenv('APP_DEBUG') == "true") {
            $body['errors'] = [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTrace()
            ];
        }

        return $response
            ->withStatus(500)
            ->withHeader('Content-Type', 'application/json')
            ->withJson($body);
    }

}
