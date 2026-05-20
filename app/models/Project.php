<?php

namespace App\Models;

use App\Core\Model;

class Project extends Model
{
    protected string $table = 'projects';
    protected array $fillable = ['titre', 'slug', 'description', 'contenu', 'technologies', 'image_url', 'gallery_images', 'github_url', 'demo_url', 'statut', 'est_mis_en_avant', 'ordre'];

    public function publicAll(): array
    {
        return $this->all('ordre ASC, created_at DESC', "statut = 'publie'");
    }

    public function featured(): array
    {
        return $this->all('ordre ASC, created_at DESC', "statut = 'publie' AND est_mis_en_avant = 1");
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->first('slug = ?', [$slug]);
    }
}
