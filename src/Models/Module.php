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
    public ?int $module_type_id;
    /** @var array<string,mixed>|null Embedded module_type payload (compact form). */
    public ?array $module_type;

    public function __construct(
        int $id,
        string $title,
        string $description,
        ?int $moduleTypeId = null,
        ?array $moduleType = null
    ) {
        $this->id             = $id;
        $this->title          = $title;
        $this->description    = $description;
        $this->module_type_id = $moduleTypeId;
        $this->module_type    = $moduleType;
    }

    public static function fromArray(array $row): self
    {
        $typeId = isset($row['module_type_id']) && $row['module_type_id'] !== null
            ? (int)$row['module_type_id']
            : null;

        $moduleType = ModuleType::embeddedFromPrefixedRow($row);

        return new self(
            (int)($row['id'] ?? 0),
            (string)($row['title'] ?? ''),
            (string)($row['description'] ?? ''),
            $typeId,
            $moduleType
        );
    }

    /**
     * Convert to API representation.
     *
     * The legacy `slug` field is preserved (lowercased title) for backwards
     * compatibility. A nested `module_type` object is added when a type is
     * associated. The `module_type_id` is also exposed for admin forms.
     */
    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'title'          => $this->title,
            'slug'           => mb_strtolower($this->title),
            'description'    => $this->description,
            'module_type_id' => $this->module_type_id,
            'module_type'    => $this->module_type,
        ];
    }
}
