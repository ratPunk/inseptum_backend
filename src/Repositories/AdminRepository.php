<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;

/**
 * Repository for the `admins` table. Mirrors UserRepository's API so the
 * AuthService can treat both flows uniformly.
 */
class AdminRepository extends AbstractRepository
{
    protected string $table = 'admins';

    public function findByUsername(string $username): ?User
    {
        $row = $this->db->fetch(
            'SELECT * FROM `admins` WHERE username = :username LIMIT 1',
            ['username' => $username]
        );
        return $row === null ? null : User::fromArray($row);
    }
}
