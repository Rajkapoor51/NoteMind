<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

class SummaryService
{
    /** @return array{summary:string, provider:string, provider_status:?int, warning:?string} */
    public function summarize(string $content): array
    {
        if ($apiKey = config('services.openai.key')) {
            try {
                $response = Http::withToken($apiKey)->acceptJson()->timeout(25)->post('https://api.openai.com/v1/responses', [
                    'model' => config('services.openai.model', 'gpt-4.1-mini'),
                    'store' => false,
                    'instructions' => 'Summarize the supplied note in 2–3 concise sentences. Preserve concrete actions, dates, decisions, and names. Return only the summary.',
                    'input' => mb_substr($content, 0, 20000),
                ])->throw();

                $summary = trim((string) data_get($response->json(), 'output_text'));
                if ($summary !== '') {
                    return ['summary' => $summary, 'provider' => 'openai', 'provider_status' => 200, 'warning' => null];
                }
            } catch (Throwable $exception) {
                // The local extractive fallback keeps the endpoint reliable if the provider is unavailable.
                report($exception);

                return [
                    'summary' => $this->extractiveSummary($content),
                    'provider' => 'local-fallback',
                    'provider_status' => $exception instanceof RequestException ? $exception->response->status() : null,
                    'warning' => 'The OpenAI request failed. Check the API key, model access, billing, and server internet connection.',
                ];
            }

            return ['summary' => $this->extractiveSummary($content), 'provider' => 'local-fallback', 'provider_status' => null, 'warning' => 'OpenAI returned no summary.'];
        }

        return ['summary' => $this->extractiveSummary($content), 'provider' => 'local-fallback', 'provider_status' => null, 'warning' => 'OPENAI_API_KEY is not available to Laravel.'];
    }

    private function extractiveSummary(string $content): string
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', trim(preg_replace('/\s+/', ' ', $content)));
        $sentences = array_values(array_filter($sentences));
        if (count($sentences) <= 2) {
            return implode(' ', $sentences);
        }
        $words = array_filter(preg_split('/[^a-z0-9]+/i', strtolower($content)), fn ($w) => strlen($w) > 3);
        $frequency = array_count_values($words);
        $scored = [];
        foreach ($sentences as $i => $sentence) {
            $tokens = preg_split('/[^a-z0-9]+/i', strtolower($sentence));
            $scored[$i] = array_sum(array_map(fn ($word) => $frequency[$word] ?? 0, $tokens));
        }
        arsort($scored);
        $best = array_slice(array_keys($scored), 0, min(3, count($sentences)));
        sort($best);

        return implode(' ', array_map(fn ($i) => $sentences[$i], $best));
    }
}
