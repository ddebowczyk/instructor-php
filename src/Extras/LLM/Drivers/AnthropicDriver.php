<?php
namespace Cognesy\Instructor\Extras\LLM\Drivers;

use Cognesy\Instructor\ApiClient\Responses\ApiResponse;
use Cognesy\Instructor\ApiClient\Responses\PartialApiResponse;
use Cognesy\Instructor\Data\Messages\Messages;
use Cognesy\Instructor\Enums\Mode;
use Cognesy\Instructor\Extras\LLM\Contracts\CanInfer;
use Cognesy\Instructor\Extras\LLM\LLMConfig;
use Cognesy\Instructor\Utils\Json\Json;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class AnthropicDriver implements CanInfer
{
    public function __construct(
        protected Client $client,
        protected LLMConfig $config
    ) {}

    public function infer(
        array $messages = [],
        string $model = '',
        array $tools = [],
        string|array $toolChoice = '',
        array $responseFormat = [],
        array $options = [],
        Mode $mode = Mode::Text,
    ) : ResponseInterface {
        return $this->client->post($this->getEndpointUrl(), [
            'headers' => $this->getRequestHeaders(),
            'json' => $this->getRequestBody(
                $messages, $model, $tools, $toolChoice, $responseFormat, $options, $mode
            ),
            'connect_timeout' => $this->config->connectTimeout ?? 3,
            'timeout' => $this->config->requestTimeout ?? 30,
        ]);
    }

    public function toApiResponse(array $data): ApiResponse {
        return new ApiResponse(
            content: $data['content'][0]['text'] ?? Json::encode($data['content'][0]['input']) ?? '',
            responseData: $data,
            toolName: $data['content'][0]['name'] ?? '',
            finishReason: $data['stop_reason'] ?? '',
            toolCalls: null,
            inputTokens: $data['usage']['input_tokens'] ?? 0,
            outputTokens: $data['usage']['output_tokens'] ?? 0,
            cacheCreationTokens: $data['usage']['cache_creation_input_tokens'] ?? 0,
            cacheReadTokens: $data['usage']['cache_read_input_tokens'] ?? 0,
        );
    }

    public function toPartialApiResponse(array $data) : PartialApiResponse {
        return new PartialApiResponse(
            delta: $data['delta']['text'] ?? $data['delta']['partial_json'] ?? '',
            responseData: $data,
            toolName: $data['content_block']['name'] ?? '',
            finishReason: $data['delta']['stop_reason'] ?? $data['message']['stop_reason'] ?? '',
            inputTokens: $data['message']['usage']['input_tokens'] ?? $data['usage']['input_tokens'] ?? 0,
            outputTokens: $data['message']['usage']['output_tokens'] ?? $data['usage']['output_tokens'] ?? 0,
            cacheCreationTokens: $data['message']['usage']['cache_creation_input_tokens'] ?? $data['usage']['cache_creation_input_tokens'] ?? 0,
            cacheReadTokens: $data['message']['usage']['cache_read_input_tokens'] ?? $data['usage']['cache_read_input_tokens'] ?? 0,
        );
    }

    public function isDone(string $data): bool {
        return $data === 'event: message_stop';
    }

    public function getData(string $data): string {
        if (str_starts_with($data, 'data:')) {
            return trim(substr($data, 5));
        }
        return '';
    }

    // INTERNAL /////////////////////////////////////////////

    protected function getEndpointUrl() : string {
        return "{$this->config->apiUrl}{$this->config->endpoint}";
    }

    private function getRequestHeaders() : array {
        return array_filter([
            'x-api-key' => $this->config->apiKey,
            'content-type' => 'application/json',
            'accept' => 'application/json',
            'anthropic-version' => $this->config->metadata['apiVersion'] ?? '',
            'anthropic-beta' => $this->config->metadata['beta'] ?? '',
        ]);
    }

    /**
     * @throws GuzzleException
     */
    protected function getRequestBody(
        array $messages = [],
        string $model = '',
        array $tools = [],
        string|array $toolChoice = '',
        array $responseFormat = [],
        array $options = [],
        Mode $mode = Mode::Text,
    ) : array {
        $request = array_filter(array_merge([
            'model' => $model ?: $this->config->model,
            'max_tokens' => $options['max_tokens'] ?? $this->config->maxTokens,
            'system' => Messages::fromArray($messages)
                ->forRoles(['system'])
                ->toString(),
            'messages' => Messages::fromArray($messages)
                ->exceptRoles(['system'])
                ->toNativeArray(
                    clientType: $this->config->clientType,
                    mergePerRole: true
                ),
        ], $options));

        switch($mode) {
            case Mode::Tools:
                $request['tools'] = $this->toTools($tools);
                $request['tool_choice'] = $this->toToolChoice($toolChoice, $tools);
                break;
            case Mode::Json:
            case Mode::JsonSchema:
                break;
        }

        return $request;
    }

    protected function toTools(array $tools) : array {
        $result = [];
        foreach ($tools as $tool) {
            $result[] = [
                'name' => $tool['function']['name'],
                'description' => $tool['function']['description'] ?? '',
                'input_schema' => $tool['function']['parameters'],
            ];
        }
        return $result;
    }

    protected function toToolChoice(string|array $toolChoice, array $tools) : array|string {
        return match(true) {
            empty($tools) => '',
            is_array($toolChoice) => [
                'type' => 'tool',
                'name' => $toolChoice['function']['name'],
            ],
            empty($toolChoice) => [
                'type' => 'auto',
            ],
            default => [
                'type' => $toolChoice,
            ],
        };
    }
}
