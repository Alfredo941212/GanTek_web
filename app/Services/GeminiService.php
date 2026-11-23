<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    public function ask(string $prompt): string
    {
        $apiKey = config('services.gemini.key');

        if (!$apiKey) {
            throw new \Exception('No se encontró GEMINI_API_KEY.');
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent';

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        [
                            'text' => $prompt,
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'maxOutputTokens' => 500,
                'temperature' => 0.2,
                'thinkingConfig' => [
                    'thinkingLevel' => 'MINIMAL',
                ],
            ],
        ];

        $attempts = 3;

        for ($i = 1; $i <= $attempts; $i++) {
            try {
                $response = Http::withHeaders([
                    'x-goog-api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                    ->connectTimeout(15)
                    ->timeout(120)
                    ->post($url, $payload);

                if ($response->successful()) {
                    return $response->json(
                        'candidates.0.content.parts.0.text',
                        'Sin respuesta'
                    );
                }

                if ($response->status() === 503 && $i < $attempts) {
                    sleep(3);
                    continue;
                }

                throw new \Exception(
                    'Error Gemini [' .
                        $response->status() .
                        ']: ' .
                        $response->body()
                );
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                if ($i < $attempts) {
                    sleep(3);
                    continue;
                }

                throw new \Exception(
                    'Error de conexión con Gemini: ' . $e->getMessage()
                );
            }
        }

        throw new \Exception(
            'No se pudo obtener respuesta de Gemini.'
        );
    }
}
