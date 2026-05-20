<?php

namespace App\Models;

use App\Core\Model;

class Collaboration extends Model
{
    protected string $table = 'collaborations';
    protected array $fillable = ['project_id', 'nom_membre', 'role', 'email', 'linkedin_url', 'portfolio_url', 'github_url', 'contribution'];
}
