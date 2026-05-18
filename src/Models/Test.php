<?php
declare(strict_types=1);

namespace App\Models;

class Test
{
    public int $id;
    public ?string $title;
    public ?string $description;
    public ?int $time_limit;
    public ?int $question_count;
    public ?string $file_path;
    public ?string $created_at;
    public ?int $module_id;
    public ?string $module_title;
    /** @var array<string,mixed>|null */
    public ?array $module_type;
    public ?int $article_id;
    public ?string $article_title;
    public ?int $task_id;
    public ?string $task_title;

    public static function fromArray(array $row): self
    {
        $t = new self();
        $t->id             = (int)($row['id'] ?? 0);
        $t->title          = isset($row['title']) ? (string)$row['title'] : null;
        $t->description    = isset($row['description']) ? (string)$row['description'] : null;
        $t->time_limit     = isset($row['time_limit']) ? (int)$row['time_limit'] : null;
        $t->question_count = isset($row['question_count']) ? (int)$row['question_count'] : null;
        $t->file_path      = isset($row['file_path']) ? (string)$row['file_path'] : null;
        $t->created_at     = isset($row['created_at']) ? (string)$row['created_at'] : null;
        $t->module_id      = isset($row['module_id']) && $row['module_id'] !== null ? (int)$row['module_id'] : null;
        $t->module_title   = isset($row['module_title']) && $row['module_title'] !== null ? (string)$row['module_title'] : null;
        $t->module_type    = ModuleType::embeddedFromPrefixedRow($row);
        $t->article_id     = isset($row['article_id']) && $row['article_id'] !== null ? (int)$row['article_id'] : null;
        $t->article_title  = isset($row['article_title']) && $row['article_title'] !== null ? (string)$row['article_title'] : null;
        $t->task_id        = isset($row['task_id']) && $row['task_id'] !== null ? (int)$row['task_id'] : null;
        $t->task_title     = isset($row['task_title']) && $row['task_title'] !== null ? (string)$row['task_title'] : null;
        return $t;
    }

    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'title'          => $this->title,
            'description'    => $this->description,
            'time_limit'     => $this->time_limit,
            'question_count' => $this->question_count,
            'file_path'      => $this->file_path,
            'created_at'     => $this->created_at,
            'module_id'      => $this->module_id,
            'module_title'   => $this->module_title,
            'module_type'    => $this->module_type,
            'article_id'     => $this->article_id,
            'article_title'  => $this->article_title,
            'task_id'        => $this->task_id,
            'task_title'     => $this->task_title,
        ];
    }
}
