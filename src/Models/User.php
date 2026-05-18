<?php
declare(strict_types=1);

namespace App\Models;

/**
 * User DTO. Holds the password hash internally but never exposes it via toPublicArray().
 *
 * `role` is one of: 'user', 'spectator', 'moderator', 'admin'.
 * Admin-class roles ('admin', 'moderator', 'spectator') unlock access to the admin panel.
 */
class User
{
    public int $id;
    public string $username;
    public string $passwordHash;
    public string $role;
    public ?string $createdAt;

    public function __construct(
        int $id,
        string $username,
        string $passwordHash,
        string $role = 'user',
        ?string $createdAt = null
    ) {
        $this->id           = $id;
        $this->username     = $username;
        $this->passwordHash = $passwordHash;
        $this->role         = $role;
        $this->createdAt    = $createdAt;
    }

    public static function fromArray(array $row): self
    {
        return new self(
            (int)($row['id'] ?? 0),
            (string)($row['username'] ?? ''),
            (string)($row['password'] ?? ''),
            (string)($row['role'] ?? 'user'),
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
            'role'       => $this->role,
            'created_at' => $this->createdAt,
        ];
    }
}
