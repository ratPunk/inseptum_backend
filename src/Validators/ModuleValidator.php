<?php
declare(strict_types=1);

namespace App\Validators;

class ModuleValidator extends AbstractValidator
{
    /**
     * @param array $data Expected keys: title (required), description (required),
     *                    module_type_id (required, int)
     * @return array{title:string, description:string, module_type_id:int}
     */
    public function validateCreate(array $data): array
    {
        return $this->validate($data);
    }

    /**
     * Update accepts the same fields; both required to mirror legacy behaviour.
     */
    public function validateUpdate(array $data): array
    {
        return $this->validate($data);
    }

    private function validate(array $data): array
    {
        $title       = trim((string)($data['title'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));
        $rawType     = $data['module_type_id'] ?? null;
        $moduleTypeId = is_numeric($rawType) ? (int)$rawType : 0;

        if ($title === '') {
            $this->addError('title', 'Название модуля обязательно');
        } elseif (mb_strlen($title) > 20) {
            $this->addError('title', 'Название модуля не должно превышать 20 символов');
        }

        if ($description === '') {
            $this->addError('description', 'Описание модуля обязательно');
        }

        if ($moduleTypeId <= 0) {
            $this->addError('module_type_id', 'Не указан тип модуля');
        }

        $this->failIfErrors('Не все поля заполнены');

        return [
            'title'          => $title,
            'description'    => $description,
            'module_type_id' => $moduleTypeId,
        ];
    }
}
