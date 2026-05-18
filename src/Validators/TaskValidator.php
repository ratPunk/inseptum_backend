<?php
declare(strict_types=1);

namespace App\Validators;

/**
 * Validates admin-panel task CRUD payloads.
 */
class TaskValidator extends AbstractValidator
{
    private const TITLE_MAX = 255;
    private const ALLOWED_DIFFICULTY = ['easy', 'medium', 'hard'];

    /**
     * @return array{title:string, description:?string, difficulty:string}
     */
    public function validateCreate(array $data): array
    {
        $title       = trim((string)($data['title'] ?? ''));
        $description = isset($data['description']) ? (string)$data['description'] : '';
        $difficulty  = (string)($data['difficulty'] ?? 'medium');

        if ($title === '') {
            $this->addError('title', 'Название задачи обязательно');
        } elseif (mb_strlen($title) > self::TITLE_MAX) {
            $this->addError('title', 'Слишком длинное название');
        }

        if (!in_array($difficulty, self::ALLOWED_DIFFICULTY, true)) {
            $this->addError('difficulty', 'Недопустимая сложность');
        }

        $this->failIfErrors('Ошибка валидации задачи');

        return [
            'title'       => $title,
            'description' => $description === '' ? null : $description,
            'difficulty'  => $difficulty,
        ];
    }

    /**
     * Partial update. Returns only fields actually provided.
     *
     * @return array{title?:string, description?:?string, difficulty?:string}
     */
    public function validateUpdate(array $data): array
    {
        $out = [];

        if (array_key_exists('title', $data)) {
            $title = trim((string)$data['title']);
            if ($title === '') {
                $this->addError('title', 'Название задачи обязательно');
            } elseif (mb_strlen($title) > self::TITLE_MAX) {
                $this->addError('title', 'Слишком длинное название');
            } else {
                $out['title'] = $title;
            }
        }

        if (array_key_exists('description', $data)) {
            $description = (string)$data['description'];
            $out['description'] = $description === '' ? null : $description;
        }

        if (array_key_exists('difficulty', $data)) {
            $difficulty = (string)$data['difficulty'];
            if (!in_array($difficulty, self::ALLOWED_DIFFICULTY, true)) {
                $this->addError('difficulty', 'Недопустимая сложность');
            } else {
                $out['difficulty'] = $difficulty;
            }
        }

        $this->failIfErrors('Ошибка валидации задачи');

        return $out;
    }
}
