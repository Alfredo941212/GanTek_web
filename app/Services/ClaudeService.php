<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ClaudeService
{
    public function ask(string $prompt): string
    {
        $apiKey = config('services.anthropic.key');

        if (!$apiKey) {
            throw new \Exception(
                'No se encontró ANTHROPIC_API_KEY.'
            );
        }

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])
            ->timeout(120)
            ->post(
                'https://api.anthropic.com/v1/messages',
                [
                    'model' => 'claude-sonnet-5-5',
                    'max_tokens' => 500,

                    'thinking' => [
                        'type' => 'between_tools',
                    ],
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ]
            );

        if ($response->failed()) {
            throw new \Exception(
                'Error Claude [' .
                    $response->status() .
                    ']: ' .
                    $response->body()
            );
        }

        $content = $response->json('content', []);

        foreach ($content as $block) {
            if (
                isset($block['type'], $block['text'])
                && $block['type'] === 'text'
            ) {
                return $block['text'];
            }
        }

        return 'Sin respuesta';
    }
}
