<?php

namespace App\Models;

use App\Core\Model;

class Contact extends Model
{
    protected string $table = 'contacts';
    protected array $fillable = ['nom', 'email', 'sujet', 'message', 'ip_address', 'user_agent', 'statut'];

    public function unread(): array
    {
        return $this->all('created_at DESC', 'statut = \'nouveau\'');
    }

    public function unreadCount(): int
    {
        return $this->count('statut = \'nouveau\'');
    }
}
