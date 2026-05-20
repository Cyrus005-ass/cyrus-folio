<?php

namespace App\Models;

use App\Core\Model;

class Certification extends Model
{
    protected string $table = 'certifications';
    protected array $fillable = ['titre', 'organisme', 'date_obtention', 'date_expiration', 'credential_id', 'badge_url', 'lien_verification', 'est_active', 'ordre'];

    public function active(): array
    {
        return $this->all('ordre ASC, date_obtention DESC', 'est_active = 1');
    }

    public function expiringSoon(int $days = 30): array
    {
        return $this->all('date_expiration ASC', 'est_active = 1 AND date_expiration IS NOT NULL AND date_expiration BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)', [$days]);
    }
}
