<?php
declare(strict_types=1);

namespace App\Validators;

class ModuleTypeValidator extends AbstractValidator
{
    private const SLUG_PATTERN = '/^[a-z0-9_-]+$/';

    /**
     * @return array{slug:string,name:string,icon:string,highlight_language:?string,color:?string}
     */
    public function validateCreate(array $data): array
    {
        return $this->validate($data, true);
    }

    /**
     * Update accepts the same fields and requires them all.
     */
    public function validateUpdate(array $data): array
    {
        return $this->validate($data, false);
    }

    private function validate(array $data, bool $isCreate): array
    {
        $slug = trim((string)($data['slug'] ?? ''));
        $name = trim((string)($data['name'] ?? ''));
        $icon = trim((string)($data['icon'] ?? ''));
        $highlight = isset($data['highlight_language']) && $data['highlight_language'] !== ''
            ? trim((string)$data['highlight_language'])
            : null;
        $color = isset($data['color']) && $data['color'] !== ''
            ? trim((string)$data['color'])
            : null;

        if ($slug === '') {
            $this->addError('slug', 'Slug обязателен');
        } elseif (!preg_match(self::SLUG_PATTERN, $slug)) {
            $this->addError('slug', 'Slug может содержать только латинские буквы в нижнем регистре, цифры, дефис и подчёркивание');
        } elseif (mb_strlen($slug) > 64) {
            $this->addError('slug', 'Slug не должен превышать 64 символа');
        }

        if ($name === '') {
            $this->addError('name', 'Название обязательно');
        } elseif (mb_strlen($name) > 100) {
            $this->addError('name', 'Название не должно превышать 100 символов');
        }

        if ($icon === '') {
            $this->addError('icon', 'Иконка обязательна');
        } elseif (mb_strlen($icon) > 80) {
            $this->addError('icon', 'Имя иконки не должно превышать 80 символов');
        }

        if ($highlight !== null && mb_strlen($highlight) > 40) {
            $this->addError('highlight_language', 'Язык подсветки не должен превышать 40 символов');
        }

        if ($color !== null && mb_strlen($color) > 20) {
            $this->addError('color', 'Цвет не должен превышать 20 символов');
        }

        $this->failIfErrors($isCreate ? 'Не удалось создать тип модуля' : 'Не удалось обновить тип модуля');

        return [
            'slug'               => $slug,
            'name'               => $name,
            'icon'               => $icon,
            'highlight_language' => $highlight,
            'color'              => $color,
        ];
    }
}
