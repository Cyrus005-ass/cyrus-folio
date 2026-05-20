<?php

namespace App\Models;

use App\Core\Model;

class Theme extends Model
{
    protected string $table = 'themes';
    protected array $fillable = ['nom', 'primary_color', 'secondary_color', 'accent_color', 'background_color', 'text_color', 'display_font_family', 'body_font_family', 'font_family', 'animations_enabled', 'is_active'];

    public function active(): ?array
    {
        return $this->first('is_active = 1');
    }
}
