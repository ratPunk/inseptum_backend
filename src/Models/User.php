<?php
declare(strict_types=1);

namespace App\Models;

/**
 * User DTO. Holds the password hash internally but never exposes it via toPublicArray().
 */
class User
{
    public int $id;
    public string $username;
    public string $passwordHash;
    public ?string $createdAt;

    public function __construct(int $id, string $username, string $passwordHash, ?string $createdAt = null)
    {
        $this->id           = $id;
        $this->username     = $username;
        $this->passwordHash = $passwordHash;
        $this->createdAt    = $createdAt;
    }

    public static function fromArray(array $row): self
    {
        return new self(
            (int)($row['id'] ?? 0),
            (string)($row['username'] ?? ''),
            (string)($row['password'] ?? ''),
            isset($row['created_at']) ? (string)$row['created_at'] : null
        );
    }

    /**
     * Public representation for API responses (no password hash).
     */
    public function toPublicArray(): array
    {
        return [
            'user_id'    => $this->id,
            'username'   => $this->username,
            'created_at' => $this->createdAt,
        ];
    }
}
