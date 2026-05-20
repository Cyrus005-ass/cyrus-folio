<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Notification extends Model
{
    protected string $table = 'notifications';
    protected array $fillable = ['type', 'unique_key', 'titre', 'message', 'lien', 'is_read'];

    public function unread(): array
    {
        return $this->all('created_at DESC', 'is_read = 0');
    }

    public function unreadCount(): int
    {
        return $this->count('is_read = 0');
    }

    public function findByUniqueKey(string $key): ?array
    {
        return $this->first('unique_key = ?', [$key]);
    }

    public function markRead(int $id): void
    {
        Database::query('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ?', [$id]);
    }

    public function markAllRead(): void
    {
        Database::query('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE is_read = 0');
    }
}
