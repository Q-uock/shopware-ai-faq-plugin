<?php

declare(strict_types=1);

namespace DIW\AiFaq\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiClientService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly SystemConfigService $systemConfigService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Sends the prompt to the AI endpoint and returns the decoded FAQ array or null on error.
     *
     * @return array<int, array<string, string>>|null
     */
    public function requestFaq(string $prompt): ?array
    {
        $url   = (string) $this->systemConfigService->get('AiFaq.config.FaqUrl');
        $model = (string) $this->systemConfigService->get('AiFaq.config.FaqModel');
        $apiKey = (string) $this->systemConfigService->get('AiFaq.config.FaqApiKey');

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ],
            ]);

            $data = $response->toArray(false);
            $assistantContent = $data['choices'][0]['message']['content'] ?? '';

            // Remove markdown code block if present
            $cleanResponse = preg_replace('/```[a-zA-Z]*\n?(.*?)```/s', '$1', $assistantContent);
            $cleanResponse = str_replace(['\n', '\"'], ['', '"'], $cleanResponse);

            return json_decode($cleanResponse, true);
        } catch (\Throwable $e) {
            $this->logger->error('AI request failed: ' . $e->getMessage());
            return null;
        }
    }
}
