<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class GeminiClient
{
    /** @return array<string, mixed> */
    public function generateJson(string $prompt, int $maxOutputTokens = 8192): array
    {
        if (! config('vagaflow.gemini_key')) {
            throw new RuntimeException('GEMINI_API_KEY não configurada.');
        }

        $model = config('vagaflow.gemini_model');

        try {
            return $this->request($model, $prompt, $maxOutputTokens);
        } catch (RequestException $exception) {
            $fallback = config('vagaflow.gemini_fallback_model');

            if (! $fallback || $fallback === $model || ! in_array($exception->response->status(), [429, 500, 502, 503, 504], true)) {
                throw $exception;
            }

            $result = $this->request($fallback, $prompt, $maxOutputTokens);
            $result['_meta'] = ['model' => $fallback, 'fallback' => true];

            return $result;
        }
    }

    /** @return array<string, mixed> */
    private function request(string $model, string $prompt, int $maxOutputTokens): array
    {
        $response = Http::baseUrl('https://generativelanguage.googleapis.com/v1beta/')
            ->withHeaders(['x-goog-api-key' => config('vagaflow.gemini_key')])
            ->acceptJson()
            ->timeout(90)
            ->post('models/'.rawurlencode($model).':generateContent', [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature' => 0.1,
                    'maxOutputTokens' => $maxOutputTokens,
                ],
            ])->throw();

        $text = $response->json('candidates.0.content.parts.0.text');
        $decoded = json_decode(
            Str::of((string) $text)->trim()->replaceMatches('/^```(?:json)?\s*|\s*```$/i', ''),
            true,
        );

        if (! is_array($decoded)) {
            throw new RuntimeException('Gemini retornou JSON inválido.');
        }

        return $decoded;
    }
}
