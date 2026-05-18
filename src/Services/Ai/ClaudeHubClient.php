<?php
declare(strict_types=1);

namespace App\Services\Ai;

use App\Core\Logger;

/**
 * Тонкий cURL-клиент к Anthropic-совместимому прокси (claudehub.fun).
 *
 * Возвращает:
 *   ['ok' => bool, 'text' => string, 'usage' => ['in'=>int,'out'=>int],
 *    'latency_ms' => int, 'error' => ?string]
 */
final class ClaudeHubClient
{
    /** @var array<string,mixed> */
    private array $cfg;
    private Logger $logger;

    public function __construct(array $cfg, Logger $logger)
    {
        $this->cfg    = $cfg;
        $this->logger = $logger;
    }

    public function isConfigured(): bool
    {
        return is_string($this->cfg['api_key'] ?? null) && $this->cfg['api_key'] !== '';
    }

    public function complete(string $systemPrompt, string $userMessage, ?float $temperatureOverride = null): array
    {
        $started = microtime(true);

        if (!$this->isConfigured()) {
            return $this->fail('API key is not configured', 0);
        }

        $url = $this->cfg['base_url'] . '/v1/messages';
        $payload = [
            'model'       => $this->cfg['model'],
            'max_tokens'  => (int)$this->cfg['max_tokens'],
            'temperature' => $temperatureOverride !== null
                ? (float)$temperatureOverride
                : (float)$this->cfg['temperature'],
            'system'      => $systemPrompt,
            'messages'    => [
                ['role' => 'user', 'content' => $userMessage],
            ],
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . (string)$this->cfg['api_key'],
                'anthropic-version: ' . (string)$this->cfg['anthropic_version'],
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => (int)$this->cfg['timeout_sec'],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $raw  = curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $latency = (int)round((microtime(true) - $started) * 1000);

        if ($raw === false || $raw === '') {
            return $this->fail('cURL error: ' . ($err ?: 'empty response'), $latency);
        }
        if ($code < 200 || $code >= 300) {
            $this->logger->error('ClaudeHub HTTP error', ['code' => $code, 'body' => substr((string)$raw, 0, 500)]);
            return $this->fail("HTTP $code", $latency);
        }

        $decoded = json_decode((string)$raw, true);
        if (!is_array($decoded)) {
            return $this->fail('Invalid JSON envelope', $latency);
        }

        // Формат Anthropic Messages API: content = [{type: 'text', text: '...'}]
        $text = '';
        if (isset($decoded['content']) && is_array($decoded['content'])) {
            foreach ($decoded['content'] as $block) {
                if (isset($block['type'], $block['text']) && $block['type'] === 'text') {
                    $text .= (string)$block['text'];
                }
            }
        }
        if ($text === '') {
            // Иногда прокси оборачивают по-другому — пробуем fallback.
            $text = (string)($decoded['completion'] ?? $decoded['message'] ?? '');
        }

        $usage = [
            'in'  => (int)($decoded['usage']['input_tokens']  ?? 0),
            'out' => (int)($decoded['usage']['output_tokens'] ?? 0),
        ];

        if ($text === '') {
            return $this->fail('Empty assistant text', $latency, $usage);
        }

        return [
            'ok'         => true,
            'text'       => $text,
            'usage'      => $usage,
            'latency_ms' => $latency,
            'error'      => null,
        ];
    }

    /** @param array{in:int,out:int} $usage */
    private function fail(string $err, int $latency, array $usage = ['in' => 0, 'out' => 0]): array
    {
        return [
            'ok'         => false,
            'text'       => '',
            'usage'      => $usage,
            'latency_ms' => $latency,
            'error'      => $err,
        ];
    }
}
