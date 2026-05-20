<?php

namespace App\Models;

use App\Core\Model;

class Skill extends Model
{
    protected string $table = 'skills';
    protected array $fillable = ['nom', 'categorie', 'niveau', 'icone', 'description', 'est_active', 'ordre'];

    public function active(): array
    {
        return $this->all('categorie ASC, ordre ASC, FIELD(niveau, \'Expert\', \'Avance\', \'Intermediaire\', \'Notions\')', 'est_active = 1');
    }

    public function groupedActive(): array
    {
        $groups = [];
        foreach ($this->active() as $skill) {
            $groups[$skill['categorie']][] = $skill;
        }
        return $groups;
    }
}
