<?php

namespace Cognesy\Instructor\Extras\Embeddings\Drivers;

use Cognesy\Instructor\Extras\Embeddings\Contracts\CanVectorize;
use Cognesy\Instructor\Extras\Embeddings\EmbeddingsConfig;
use Cognesy\Instructor\Extras\Embeddings\EmbeddingsResponse;
use Cognesy\Instructor\Extras\Embeddings\Vector;
use GuzzleHttp\Client;

class GeminiDriver implements CanVectorize
{
    private int $inputCharacters = 0;

    public function __construct(
        protected Client $client,
        protected EmbeddingsConfig $config
    ) {}

    public function vectorize(array $input, array $options = []): EmbeddingsResponse {
        $this->inputCharacters = $this->countCharacters($input);
        $response = $this->client->post($this->getEndpointUrl(), [
            'headers' => $this->getRequestHeaders(),
            'json' => $this->getRequestBody($input, $options),
        ]);
        return $this->toResponse(json_decode($response->getBody()->getContents(), true));
    }

    // INTERNAL /////////////////////////////////////////////////

    protected function getEndpointUrl(): string {
        return str_replace(
            "{model}",
            $this->config->model,
            "{$this->config->apiUrl}{$this->config->endpoint}?key={$this->config->apiKey}"
        );
    }

    protected function getRequestHeaders(): array {
        return [
            'Content-Type' => 'application/json',
        ];
    }

    protected function getRequestBody(array $input, array $options) : array {
        return array_merge([
            'requests' => array_map(
                fn($item) => [
                    'model' => $this->config->model,
                    'content' => ['parts' => [['text' => $item]]]
                ],
                $input
            ),
        ], $options);
    }

    protected function toResponse(array $response) : EmbeddingsResponse {
        $vectors = [];
        foreach ($response['embeddings'] as $key => $item) {
            $vectors[] = new Vector(values: $item['values'], id: $key);
        }
        return new EmbeddingsResponse(
            vectors: $vectors,
            inputTokens: $this->inputCharacters,
            outputTokens: 0,
        );
    }

    private function countCharacters(array $input) : int {
        return array_sum(array_map(fn($item) => strlen($item), $input));
    }
}
