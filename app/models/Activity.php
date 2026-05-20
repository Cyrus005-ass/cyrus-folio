<?php

namespace App\Models;

use App\Core\Model;

class Activity extends Model
{
    protected string $table = 'activities';
    protected array $fillable = ['user_id', 'action', 'description', 'ip_address'];
}
