<?php
namespace Tests;

use Cognesy\Instructor\Contracts\CanCallFunction;
use Cognesy\Instructor\Core\EventDispatcher;
use Cognesy\Instructor\Events\LLM\RequestSent;
use Cognesy\Instructor\Events\LLM\ResponseReceived;
use Cognesy\Instructor\Extras\Scalars\Scalar;
use Cognesy\Instructor\Instructor;
use Cognesy\Instructor\LLMs\OpenAI\OpenAIFunctionCaller;
use Cognesy\Instructor\Utils\Configuration;
use Mockery;

it('extracts int type', function () {
    $mockLLM = Mockery::mock(OpenAIFunctionCaller::class);
    $mockLLM->shouldReceive('callFunction')->andReturnUsing(
        fn() => '{"age":28}',
    );

    $text = "His name is Jason, he is 28 years old.";
    $value = (new Instructor([CanCallFunction::class => $mockLLM]))->respond(
        messages: [
            ['role' => 'system', 'content' => $text],
            ['role' => 'user', 'content' => 'What is Jason\'s age?'],
        ],
        responseModel: Scalar::integer('age'),
    );
    expect($value)->toBeInt();
    expect($value)->toBe(28);
})->only();

it('extracts string type', function () {
    $mockLLM = Mockery::mock(OpenAIFunctionCaller::class);
    $mockLLM->shouldReceive('callFunction')->andReturnUsing(
        fn() => '{"firstName":"Jason"}',
    );

    $text = "His name is Jason, he is 28 years old.";
    $value = (new Instructor([CanCallFunction::class => $mockLLM]))->respond(
        messages: [
            ['role' => 'system', 'content' => $text],
            ['role' => 'user', 'content' => 'What is his name?'],
        ],
        responseModel: Scalar::string(name: 'firstName'),
    );
    expect($value)->toBeString();
    expect($value)->toBe("Jason");
})->only();

it('extracts float type', function () {
    $mockLLM = Mockery::mock(OpenAIFunctionCaller::class);
    $mockLLM->shouldReceive('callFunction')->andReturnUsing(
        fn() => '{"recordTime":11.6}',
    );

    $text = "His name is Jason, he is 28 years old and his 100m sprint record is 11.6 seconds.";
    $value = (new Instructor([CanCallFunction::class => $mockLLM]))->respond(
        messages: [
            ['role' => 'system', 'content' => $text],
            ['role' => 'user', 'content' => 'What is Jason\'s best 100m run time?'],
        ],
        responseModel: Scalar::float(name: 'recordTime'),
    );
    expect($value)->toBeFloat();
    expect($value)->toBe(11.6);
});

it('extracts bool type', function () {
    $mockLLM = Mockery::mock(OpenAIFunctionCaller::class);
    $mockLLM->shouldReceive('callFunction')->andReturnUsing(
        fn() => '{"isAdult":true}',
    );

    $text = "His name is Jason, he is 28 years old.";
    $age = (new Instructor([CanCallFunction::class => $mockLLM]))->respond(
        messages: [
            ['role' => 'system', 'content' => $text],
            ['role' => 'user', 'content' => 'Is he adult?'],
        ],
        responseModel: Scalar::boolean(name: 'isAdult'),
    );
    expect($age)->toBeBool();
    expect($age)->toBe(true);
});


it('extracts enum type', function () {
    $mockLLM = Mockery::mock(OpenAIFunctionCaller::class);
    $mockLLM->shouldReceive('callFunction')->andReturnUsing(
        fn() => '{"citizenshipGroup":"other"}',
    );

    $text = "His name is Jason, he is 28 years old and he lives in Germany.";
    $age = (new Instructor([CanCallFunction::class => $mockLLM]))->respond(
        messages: [
            ['role' => 'system', 'content' => $text],
            ['role' => 'user', 'content' => 'What is Jason\'s citizenship?'],
        ],
        responseModel: Scalar::enum(\Tests\Examples\Scalars\CitizenshipGroup::class, name: 'citizenshipGroup'),
    );
    expect($age)->toBeString();
    expect($age)->toBe('other');
});
