<?php
declare(strict_types=1);

namespace App\Models;

class Article
{
    public int $id;
    public ?string $title;
    public ?string $description;
    public ?string $module_title;
    /** @var array<string,mixed>|null */
    public ?array $module_type;
    public ?int $test_id;
    public ?string $test_title;
    public ?int $task_id;
    public ?string $task_title;
    public ?string $file_path;
    public ?string $created_at;

    public static function fromArray(array $row): self
    {
        $a = new self();
        $a->id           = (int)($row['id'] ?? 0);
        $a->title        = isset($row['title']) ? (string)$row['title'] : null;
        $a->description  = isset($row['description']) ? (string)$row['description'] : null;
        $a->module_title = isset($row['module_title']) ? (string)$row['module_title'] : null;
        $a->module_type  = ModuleType::embeddedFromPrefixedRow($row);
        $a->test_id      = isset($row['test_id']) && $row['test_id'] !== null ? (int)$row['test_id'] : null;
        $a->test_title   = isset($row['test_title']) ? (string)$row['test_title'] : null;
        $a->task_id      = isset($row['task_id']) && $row['task_id'] !== null ? (int)$row['task_id'] : null;
        $a->task_title   = isset($row['task_title']) ? (string)$row['task_title'] : null;
        $a->file_path    = isset($row['file_path']) ? (string)$row['file_path'] : null;
        $a->created_at   = isset($row['created_at']) ? (string)$row['created_at'] : null;
        return $a;
    }

    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'description'  => $this->description,
            'module_title' => $this->module_title,
            'module_type'  => $this->module_type,
            'test_id'      => $this->test_id,
            'test_title'   => $this->test_title,
            'task_id'      => $this->task_id,
            'task_title'   => $this->task_title,
            'file_path'    => $this->file_path,
            'created_at'   => $this->created_at,
        ];
    }
}
