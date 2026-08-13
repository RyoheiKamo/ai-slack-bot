<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OpenAIService
{
    private const ENDPOINT = 'https://api.openai.com/v1/responses';

    /**
     * 会話履歴をOpenAIへ送信し、生成されたテキストを返す。
     *
     * @param array<int, array{role: string, content: string}> $messages
     */
    public function generateReply(array $messages): string
    {
        if ($messages === []) {
            throw new RuntimeException('OpenAIへ送信する会話履歴が空です。');
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
                    'input' => $messages,
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
            $errorCode = $response->json('error.code');

            Log::error('OpenAI API request failed', [
                'status' => $response->status(),
                'error_code' => $errorCode,
                'error' => $response->json('error'),
            ]);

            throw new RuntimeException(
                $this->createExceptionMessage($errorCode)
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

    /**
     * OpenAI APIエラーコードに対応するユーザー向けメッセージを返却する。
     */
    private function createExceptionMessage(?string $errorCode): string
    {
        return match ($errorCode) {
            'insufficient_quota',
            'credit_balance_exhausted'
            => '現在OpenAI APIの利用枠が不足しています。管理者へお問い合わせください。',

            'invalid_api_key'
            => 'OpenAI APIキーが正しく設定されていません。管理者へお問い合わせください。',

            'rate_limit_exceeded'
            => '現在アクセスが集中しています。しばらくしてから再度お試しください。',

            'context_length_exceeded'
            => '送信内容が長すぎます。入力内容を短くして再度お試しください。',

            'model_not_found'
            => '指定されたAIモデルが利用できません。管理者へお問い合わせください。',

            default
            => 'OpenAI APIで予期しないエラーが発生しました。しばらくしてから再度お試しください。',
        };
    }
}
