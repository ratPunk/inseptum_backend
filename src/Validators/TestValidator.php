<?php
declare(strict_types=1);

namespace App\Validators;

class TestValidator extends AbstractValidator
{
    /**
     * @return array{title:string, description:string, time_limit:int, topic_id:int}
     */
    public function validate(array $data, ?array $file, bool $requireFile): array
    {
        $title       = trim((string)($data['title'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));
        $timeLimit   = (int)($data['time_limit'] ?? 20);
        $topicId     = (int)($data['topic_id'] ?? 0);

        if ($title === '') {
            $this->addError('title', 'Название теста обязательно');
        }

        if ($requireFile && (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK)) {
            $this->addError('file', 'Файл не загружен или поврежден');
        }

        $this->failIfErrors('Заполните обязательные поля');

        if ($timeLimit <= 0) {
            $timeLimit = 20;
        }

        return [
            'title'       => $title,
            'description' => $description,
            'time_limit'  => $timeLimit,
            'topic_id'    => $topicId,
        ];
    }
}
