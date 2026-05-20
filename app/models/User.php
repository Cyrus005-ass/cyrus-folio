<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = ['name', 'email', 'password', 'role', 'is_active', 'last_login_at'];

    public function findByEmail(string $email): ?array
    {
        return $this->first('email = ?', [$email]);
    }

    public function updateLastLogin(int $id): void
    {
        Database::query('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$id]);
    }
}
