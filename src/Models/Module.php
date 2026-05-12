<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Plain Module model. Constructable from a DB row.
 */
class Module
{
    public int $id;
    public string $title;
    public string $description;

    public function __construct(int $id, string $title, string $description)
    {
        $this->id          = $id;
        $this->title       = $title;
        $this->description = $description;
    }

    public static function fromArray(array $row): self
    {
        return new self(
            (int)($row['id'] ?? 0),
            (string)($row['title'] ?? ''),
            (string)($row['description'] ?? '')
        );
    }

    /**
     * Convert to API representation (matching legacy /api/modules.php output).
     */
    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'slug'        => mb_strtolower($this->title),
            'description' => $this->description,
        ];
    }
}
