<?php

namespace App\Models;

use App\Core\Model;

class ChatbotKnowledge extends Model
{
    protected string $table = 'chatbot_knowledge';
    protected array $fillable = ['question', 'answer', 'keywords', 'is_active'];

    public function active(): array
    {
        return $this->all('created_at DESC', 'is_active = 1');
    }
}
