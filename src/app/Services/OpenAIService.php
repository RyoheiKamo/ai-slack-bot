<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OpenAIService
{
    private const ENDPOINT = 'https://api.openai.com/v1/responses';

    /**
     * OpenAIへ質問し、生成されたテキストを返す。
     */
    public function generateReply(string $message): string
    {
        $message = trim($message);

        if ($message === '') {
            throw new RuntimeException('OpenAIへ送信するメッセージが空です。');
        }

        $apiKey = config('services.openai.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException('OPENAI_API_KEYが設定されていません。');
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(config('services.openai.timeout', 30))
                ->retry(2, 500, throw: false)
                ->post(self::ENDPOINT, [
                    'model' => config('services.openai.model'),
                    'instructions' => 'あなたはSlack上で利用されるアシスタントです。簡潔で分かりやすい日本語で回答してください。',
                    'input' => $message,
                ]);
        } catch (ConnectionException $e) {
            Log::error('OpenAI API connection failed', [
                'message' => $e->getMessage(),
            ]);

            throw new RuntimeException(
                'OpenAI APIへの接続に失敗しました。',
                previous: $e
            );
        }

        if (! $response->successful()) {
            Log::error('OpenAI API request failed', [
                'status' => $response->status(),
                'error' => $response->json('error'),
            ]);

            throw new RuntimeException(
                'OpenAI APIからエラーが返されました。'
            );
        }

        $reply = $this->extractOutputText($response->json());

        if ($reply === '') {
            Log::warning('OpenAI API returned no text output', [
                'response_id' => $response->json('id'),
                'status' => $response->json('status'),
            ]);

            throw new RuntimeException(
                'OpenAI APIから回答テキストを取得できませんでした。'
            );
        }

        Log::info('OpenAI response generated', [
            'response_id' => $response->json('id'),
            'model' => $response->json('model'),
        ]);

        return $reply;
    }

    /**
     * Responses APIのoutput配列から全output_textを抽出する。
     *
     * @param array<string, mixed> $response
     */
    private function extractOutputText(array $response): string
    {
        $texts = [];

        foreach ($response['output'] ?? [] as $outputItem) {
            if (($outputItem['type'] ?? null) !== 'message') {
                continue;
            }

            foreach ($outputItem['content'] ?? [] as $contentItem) {
                if (($contentItem['type'] ?? null) !== 'output_text') {
                    continue;
                }

                $text = $contentItem['text'] ?? null;

                if (is_string($text) && $text !== '') {
                    $texts[] = $text;
                }
            }
        }

        return trim(implode("\n", $texts));
    }
}
