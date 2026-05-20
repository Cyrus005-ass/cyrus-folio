<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class RememberToken extends Model
{
    protected string $table = 'remember_tokens';
    protected array $fillable = ['user_id', 'selector', 'token_hash', 'expires_at', 'user_agent'];

    public function findBySelector(string $selector): ?array
    {
        return $this->first('selector = ?', [$selector]);
    }

    public function purgeExpired(): void
    {
        Database::query('DELETE FROM remember_tokens WHERE expires_at < NOW()');
    }

    public function deleteForUser(int $userId): void
    {
        Database::query('DELETE FROM remember_tokens WHERE user_id = ?', [$userId]);
    }

    public function deleteBySelector(string $selector): void
    {
        Database::query('DELETE FROM remember_tokens WHERE selector = ?', [$selector]);
    }
}
