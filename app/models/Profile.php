<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Profile extends Model
{
    protected string $table = 'profiles';
    protected array $fillable = ['user_id', 'full_name', 'title', 'bio', 'email', 'phone', 'location', 'availability', 'avatar_url', 'cv_url', 'presentation_video_url', 'github_url', 'linkedin_url', 'twitter_url', 'instagram_url', 'whatsapp_url', 'facebook_url', 'website_url', 'other_links'];

    public function forUser(int $userId): ?array
    {
        return $this->first('user_id = ?', [$userId]);
    }

    public function current(): ?array
    {
        if (auth_check()) {
            $profile = $this->forUser((int) (auth_user()['id'] ?? 0));
            if ($profile) {
                return $profile;
            }
        }

        $stmt = Database::query('SELECT * FROM profiles ORDER BY id ASC LIMIT 1');
        return $stmt->fetch() ?: null;
    }
}
