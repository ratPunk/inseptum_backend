<?php
declare(strict_types=1);

namespace App\Validators;

class ModuleValidator extends AbstractValidator
{
    /**
     * @param array $data Expected keys: title (required), description (required)
     * @return array{title:string, description:string}
     */
    public function validateCreate(array $data): array
    {
        $title       = trim((string)($data['title'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));

        if ($title === '') {
            $this->addError('title', 'Название модуля обязательно');
        } elseif (mb_strlen($title) > 20) {
            $this->addError('title', 'Название модуля не должно превышать 20 символов');
        }

        if ($description === '') {
            $this->addError('description', 'Описание модуля обязательно');
        }

        $this->failIfErrors('Не все поля заполнены');

        return ['title' => $title, 'description' => $description];
    }

    /**
     * Update accepts the same fields; both required to mirror legacy behaviour.
     */
    public function validateUpdate(array $data): array
    {
        return $this->validateCreate($data);
    }
}
