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
    public ?string $article_title;
    public ?string $module_title;
    /** @var array<string,mixed>|null */
    public ?array $module_type;

    public static function fromArray(array $row): self
    {
        $t = new self();
        $t->id             = (int)($row['id'] ?? 0);
        $t->title          = isset($row['title']) ? (string)$row['title'] : null;
        $t->description    = isset($row['description']) ? (string)$row['description'] : null;
        $t->time_limit     = isset($row['time_limit']) ? (int)$row['time_limit'] : null;
        $t->question_count = isset($row['question_count']) ? (int)$row['question_count'] : null;
        $t->file_path      = isset($row['file_path']) ? (string)$row['file_path'] : null;
        $t->article_title  = isset($row['article_title']) ? (string)$row['article_title'] : null;
        $t->module_title   = isset($row['module_title']) ? (string)$row['module_title'] : null;
        $t->module_type    = ModuleType::embeddedFromPrefixedRow($row);
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
            'article_title'  => $this->article_title,
            'module_title'   => $this->module_title,
            'module_type'    => $this->module_type,
        ];
    }
}
