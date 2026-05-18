<?php
declare(strict_types=1);

namespace App\Services\Ai;

/**
 * Извлечение и валидация JSON-ответа Claude.
 * Claude иногда оборачивает в ```json ... ```, иногда добавляет текст до/после — режем.
 *
 * Контракт ответа:
 *   {"verdict":"passed|failed|off_topic|invalid_code","is_on_topic":bool,"feedback":string}
 */
final class ResponseParser
{
    private const ALLOWED_VERDICTS = ['passed', 'failed', 'off_topic', 'invalid_code'];

    /**
     * @return array{ok:bool, verdict?:string, is_on_topic?:bool, feedback?:string, error?:string}
     */
    public function parse(string $raw): array
    {
        $json = self::extractJsonBlock($raw);
        if ($json === null) {
            return ['ok' => false, 'error' => 'NO_JSON'];
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            return ['ok' => false, 'error' => 'INVALID_JSON'];
        }

        $verdict = isset($data['verdict']) ? strtolower((string)$data['verdict']) : '';
        if (!in_array($verdict, self::ALLOWED_VERDICTS, true)) {
            return ['ok' => false, 'error' => 'BAD_VERDICT'];
        }

        $feedback = isset($data['feedback']) ? (string)$data['feedback'] : '';
        $feedback = trim($feedback);
        if ($feedback === '') {
            switch ($verdict) {
                case 'passed':       $feedback = 'Решение принято.'; break;
                case 'failed':       $feedback = 'Решение не проходит проверку.'; break;
                case 'off_topic':    $feedback = 'Похоже, это не относится к задаче.'; break;
                case 'invalid_code': $feedback = 'Код синтаксически некорректен.'; break;
                default:             $feedback = ''; break;
            }
        }
        // Обрезаем feedback, чтобы не разнесло UI.
        if (mb_strlen($feedback) > 500) {
            $feedback = mb_substr($feedback, 0, 497) . '...';
        }

        $isOnTopic = isset($data['is_on_topic'])
            ? (bool)$data['is_on_topic']
            : ($verdict !== 'off_topic');

        return [
            'ok'          => true,
            'verdict'     => $verdict,
            'is_on_topic' => $isOnTopic,
            'feedback'    => $feedback,
        ];
    }

    /** Достаём JSON-объект из текста: ищем первую { и парную }. */
    private static function extractJsonBlock(string $text): ?string
    {
        // Убираем ```json ... ``` и ``` ... ```
        $text = preg_replace('/```(?:json)?\s*([\s\S]*?)```/i', '$1', $text) ?? $text;

        $start = strpos($text, '{');
        $end   = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) {
            return null;
        }
        return substr($text, $start, $end - $start + 1);
    }
}
