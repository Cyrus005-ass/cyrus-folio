<?php

namespace App\Services;

use App\Core\Model;

class ProjectService
{
    public static function slugify(string $title): string
    {
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $title);
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $slug ?: $title));
        return trim($slug, '-') ?: 'projet-' . time();
    }

    public static function uniqueSlug(Model $model, string $title, ?string $requestedSlug = null, ?int $ignoreId = null): string
    {
        $base = self::slugify(trim((string) ($requestedSlug ?: $title)));
        $candidate = $base;
        $suffix = 2;

        while ($model->first(
            $ignoreId === null ? 'slug = ?' : 'slug = ? AND id != ?',
            $ignoreId === null ? [$candidate] : [$candidate, $ignoreId]
        )) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }
}
