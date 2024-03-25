<?php

namespace Cognesy\Instructor\ApiClient;

use Cognesy\Instructor\Events\EventDispatcher;
use Cognesy\Instructor\Events\HttpClient\ApiRequestErrorRaised;
use Cognesy\Instructor\Events\HttpClient\ApiRequestInitiated;
use Cognesy\Instructor\Events\HttpClient\ApiResponseReceived;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Response;

class ApiResponseHandler
{
    public function __construct(
        protected ApiConnector $connector,
        protected EventDispatcher $events,
    ) {}

    public function respondRaw(JsonRequest $request): Response {
        $this?->events->dispatch(new ApiRequestInitiated($request));
        try {
            $response = $this->connector->send($request);
        } catch (RequestException $exception) {
            $this?->events->dispatch(new ApiRequestErrorRaised($exception));
            throw $exception;
        }
        $this?->events->dispatch(new ApiResponseReceived($response));
        return $response;
    }
}
