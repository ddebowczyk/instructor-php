<?php

namespace Tests;

use Cognesy\Instructor\ApiClient\Contracts\CanCallApi;
use Cognesy\Instructor\ApiClient\Requests\ApiToolsCallRequest;
use Cognesy\Instructor\ApiClient\Responses\ApiResponse;
use Cognesy\Instructor\Clients\OpenAI\OpenAIClient;
use Mockery;

class MockLLM
{
    static public function get(array $args) : CanCallApi {
        $mockLLM = Mockery::mock(OpenAIClient::class);
        $list = [];
        foreach ($args as $arg) {
            $list[] = self::makeFunc($arg);
        }
        $mockLLM->shouldReceive('withDebug')->andReturn($mockLLM);
        $mockLLM->shouldReceive('withEventDispatcher')->andReturn($mockLLM);
        $mockLLM->shouldReceive('withApiRequestFactory')->andReturn($mockLLM);
        $mockLLM->shouldReceive('withRequest')->andReturn($mockLLM);
        $mockLLM->shouldReceive('toolsCall')->andReturn($mockLLM);
        $mockLLM->shouldReceive('getApiRequest')->andReturnUsing(fn() => new ApiToolsCallRequest());
        $mockLLM->shouldReceive('get')->andReturnUsing(...$list);
        return $mockLLM;
    }

    static private function makeFunc(string $json) {
        return fn() => new ApiResponse(
            content: $json,
        );
    }
}
