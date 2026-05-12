<?php
declare(strict_types=1);

namespace App\Models;

class Topic
{
    public int $id;
    public int $module_id;
    public string $title;
    public ?string $description;
    public ?string $module_title;

    public function __construct(int $id, int $moduleId, string $title, ?string $description, ?string $moduleTitle = null)
    {
        $this->id           = $id;
        $this->module_id    = $moduleId;
        $this->title        = $title;
        $this->description  = $description;
        $this->module_title = $moduleTitle;
    }

    public static function fromArray(array $row): self
    {
        return new self(
            (int)($row['id'] ?? 0),
            (int)($row['module_id'] ?? 0),
            (string)($row['title'] ?? ''),
            isset($row['description']) ? (string)$row['description'] : null,
            isset($row['module_title']) ? (string)$row['module_title'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'module_id'    => $this->module_id,
            'title'        => $this->title,
            'description'  => $this->description,
            'module_title' => $this->module_title,
        ];
    }
}
