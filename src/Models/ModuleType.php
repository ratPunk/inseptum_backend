<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Module type (bootstrap, html, php, ...).
 *
 * Stored in `module_types`. Frontend uses {@see icon} as a key for its
 * react-icons map and {@see highlightLanguage} for syntax highlighting in
 * task editors.
 */
class ModuleType
{
    public int $id;
    public string $slug;
    public string $name;
    public string $icon;
    public ?string $highlight_language;
    public ?string $color;
    public ?string $created_at;
    public ?string $updated_at;

    public static function fromArray(array $row): self
    {
        $t = new self();
        $t->id                 = (int)($row['id'] ?? 0);
        $t->slug               = (string)($row['slug'] ?? '');
        $t->name               = (string)($row['name'] ?? '');
        $t->icon               = (string)($row['icon'] ?? '');
        $t->highlight_language = isset($row['highlight_language']) && $row['highlight_language'] !== null
            ? (string)$row['highlight_language']
            : null;
        $t->color              = isset($row['color']) && $row['color'] !== null
            ? (string)$row['color']
            : null;
        $t->created_at         = isset($row['created_at']) ? (string)$row['created_at'] : null;
        $t->updated_at         = isset($row['updated_at']) ? (string)$row['updated_at'] : null;
        return $t;
    }

    public function toArray(): array
    {
        return [
            'id'                 => $this->id,
            'slug'               => $this->slug,
            'name'               => $this->name,
            'icon'               => $this->icon,
            'highlight_language' => $this->highlight_language,
            'color'              => $this->color,
            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,
        ];
    }

    /**
     * Compact representation suitable for embedding into related entities
     * (Module, Article, Task, Test). Omits timestamps.
     */
    public function toEmbeddedArray(): array
    {
        return [
            'id'                 => $this->id,
            'slug'               => $this->slug,
            'name'               => $this->name,
            'icon'               => $this->icon,
            'highlight_language' => $this->highlight_language,
            'color'              => $this->color,
        ];
    }

    /**
     * Build embedded payload from a flat row containing prefixed columns
     * (mt_id, mt_slug, mt_name, mt_icon, mt_highlight_language, mt_color).
     * Returns null when no module type is associated.
     *
     * @return array<string,mixed>|null
     */
    public static function embeddedFromPrefixedRow(array $row, string $prefix = 'mt_'): ?array
    {
        $id = $row[$prefix . 'id'] ?? null;
        if ($id === null || $id === '') {
            return null;
        }
        return [
            'id'                 => (int)$id,
            'slug'               => isset($row[$prefix . 'slug']) ? (string)$row[$prefix . 'slug'] : '',
            'name'               => isset($row[$prefix . 'name']) ? (string)$row[$prefix . 'name'] : '',
            'icon'               => isset($row[$prefix . 'icon']) ? (string)$row[$prefix . 'icon'] : '',
            'highlight_language' => isset($row[$prefix . 'highlight_language']) && $row[$prefix . 'highlight_language'] !== null
                ? (string)$row[$prefix . 'highlight_language']
                : null,
            'color'              => isset($row[$prefix . 'color']) && $row[$prefix . 'color'] !== null
                ? (string)$row[$prefix . 'color']
                : null,
        ];
    }
}
