<?php
declare(strict_types=1);

namespace App\Validators;

class TopicValidator extends AbstractValidator
{
    /**
     * @return array{module_id:int, title:string, description:string}
     */
    public function validate(array $data): array
    {
        $title       = trim((string)($data['title'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));
        $moduleId    = (int)($data['module_id'] ?? 0);

        if ($title === '') {
            $this->addError('title', 'Название темы обязательно');
        }
        if ($description === '') {
            $this->addError('description', 'Описание темы обязательно');
        }
        if ($moduleId <= 0) {
            $this->addError('module_id', 'Необходимо выбрать модуль');
        }

        $this->failIfErrors('Не все поля заполнены');

        return ['module_id' => $moduleId, 'title' => $title, 'description' => $description];
    }
}
