<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Post extends Model
{
    protected string $table = 'posts';
    protected array $fillable = ['titre', 'category', 'slug', 'extrait', 'contenu', 'tags', 'image_url', 'statut', 'published_at', 'view_count'];

    public function published(): array
    {
        return $this->all('published_at DESC, created_at DESC', "statut = 'publie'");
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->first('slug = ?', [$slug]);
    }

    public function incrementViews(int $id): void
    {
        Database::query('UPDATE posts SET view_count = view_count + 1 WHERE id = ?', [$id]);
    }
}
