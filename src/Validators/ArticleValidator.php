<?php
declare(strict_types=1);

namespace App\Validators;

class ArticleValidator extends AbstractValidator
{
    /**
     * Validate article inputs.
     *
     * @param array $data    Form data (title, description, ...).
     * @param array|null $file $_FILES['file'] like array. Required when $requireFile = true.
     * @param bool  $requireFile   Whether a file upload is mandatory (create).
     * @return array{title:string, description:string}
     */
    public function validate(array $data, ?array $file, bool $requireFile): array
    {
        $title       = trim((string)($data['title'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));

        if ($title === '') {
            $this->addError('title', 'Название статьи обязательно');
        }
        if ($description === '') {
            $this->addError('description', 'Описание статьи обязательно');
        }
        if ($requireFile && (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK)) {
            $this->addError('file', 'Файл обязателен');
        }

        $this->failIfErrors('Заполните все поля');

        return ['title' => $title, 'description' => $description];
    }
}
