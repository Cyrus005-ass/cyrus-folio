<?php

if (!function_exists('request_data')) {
    function request_data(): array
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (in_array($method, ['PUT', 'PATCH', 'DELETE'], true) || str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $json = json_decode($raw ?: '', true);
            if (is_array($json)) {
                return $json;
            }
            parse_str($raw ?: '', $parsed);
            return $parsed;
        }
        return $_POST + $_GET;
    }
}

if (!function_exists('validate_required')) {
    function validate_required(array $data, array $fields): array
    {
        $errors = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                $errors[$field] = 'Ce champ est obligatoire.';
            }
        }
        return $errors;
    }
}

if (!function_exists('sanitize_bool')) {
    function sanitize_bool(mixed $value): int
    {
        return in_array($value, [1, '1', true, 'true', 'on', 'yes'], true) ? 1 : 0;
    }
}

if (!function_exists('clean_nullable')) {
    function clean_nullable(mixed $value): mixed
    {
        return trim((string) $value) === '' ? null : trim((string) $value);
    }
}

if (!function_exists('is_valid_email')) {
    function is_valid_email(?string $value): bool
    {
        return is_string($value) && filter_var(trim($value), FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('is_valid_url_or_empty')) {
    function is_valid_url_or_empty(mixed $value): bool
    {
        $value = clean_nullable($value);
        return $value === null || filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
}

if (!function_exists('is_valid_public_asset_url_or_empty')) {
    function is_valid_public_asset_url_or_empty(mixed $value): bool
    {
        $value = clean_nullable($value);
        if ($value === null) {
            return true;
        }

        if (filter_var($value, FILTER_VALIDATE_URL) !== false) {
            return true;
        }

        $normalized = str_replace('\\', '/', trim((string) $value));
        if ($normalized === '' || preg_match('/[\x00-\x1F\x7F]/', $normalized) === 1) {
            return false;
        }

        if (preg_match('/^(javascript|vbscript|data|file):/i', $normalized) === 1) {
            return false;
        }

        return preg_match('#^(?:/?assets/|/?uploads/|\./assets/|\./uploads/)#i', $normalized) === 1;
    }
}

if (!function_exists('is_valid_date_or_empty')) {
    function is_valid_date_or_empty(mixed $value, string $format = 'Y-m-d'): bool
    {
        $value = clean_nullable($value);
        if ($value === null) {
            return true;
        }

        $date = \DateTime::createFromFormat($format, $value);
        return $date !== false && $date->format($format) === $value;
    }
}

if (!function_exists('normalize_datetime_input')) {
    function normalize_datetime_input(mixed $value): ?string
    {
        $value = clean_nullable($value);
        if ($value === null) {
            return null;
        }

        foreach (['Y-m-d H:i:s', 'Y-m-d\\TH:i:s', 'Y-m-d\\TH:i'] as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date !== false) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        $timestamp = strtotime($value);
        return $timestamp !== false ? date('Y-m-d H:i:s', $timestamp) : null;
    }
}

if (!function_exists('sanitize_rich_text')) {
    function sanitize_rich_text(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $allowedTags = [
            'p' => [],
            'br' => [],
            'strong' => [],
            'b' => [],
            'em' => [],
            'i' => [],
            'u' => [],
            'ul' => [],
            'ol' => [],
            'li' => [],
            'a' => ['href', 'target', 'rel', 'title'],
            'img' => ['src', 'alt', 'title'],
            'blockquote' => [],
            'code' => [],
            'pre' => [],
            'h2' => [],
            'h3' => [],
            'h4' => [],
        ];

        $fallback = static function (string $html): ?string {
            $html = trim((string) strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><a><img><blockquote><code><pre><h2><h3><h4>'));
            return $html !== '' ? $html : null;
        };

        if (!class_exists(\DOMDocument::class)) {
            return $fallback($value);
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $flags = 0;
        if (defined('LIBXML_HTML_NOIMPLIED')) {
            $flags |= LIBXML_HTML_NOIMPLIED;
        }
        if (defined('LIBXML_HTML_NODEFDTD')) {
            $flags |= LIBXML_HTML_NODEFDTD;
        }

        $loaded = $dom->loadHTML('<?xml encoding=utf-8 ?><div data-sanitize-root=1>' . $value . '</div>', $flags);
        if (!$loaded) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            return $fallback($value);
        }

        $xpath = new \DOMXPath($dom);
        $wrapper = $xpath->query('//*[@data-sanitize-root=1]')->item(0);
        if (!$wrapper instanceof \DOMElement) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            return $fallback($value);
        }

        sanitize_rich_text_node($wrapper, $allowedTags);
        $wrapper->removeAttribute('data-sanitize-root');

        $sanitized = '';
        foreach (iterator_to_array($wrapper->childNodes) as $child) {
            $sanitized .= $dom->saveHTML($child);
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $sanitized = trim($sanitized);
        return $sanitized !== '' ? $sanitized : null;
    }
}

if (!function_exists('sanitize_rich_text_node')) {
    function sanitize_rich_text_node(\DOMNode $node, array $allowedTags): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMComment) {
                $node->removeChild($child);
                continue;
            }

            if (!$child instanceof \DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);
            if (!array_key_exists($tag, $allowedTags)) {
                if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math', 'link', 'meta'], true)) {
                    $node->removeChild($child);
                    continue;
                }

                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            if ($child->hasAttributes()) {
                $attributes = [];
                foreach ($child->attributes as $attribute) {
                    $attributes[] = $attribute;
                }

                foreach ($attributes as $attribute) {
                    $name = strtolower($attribute->nodeName);
                    if (str_starts_with($name, 'on') || !in_array($name, $allowedTags[$tag], true)) {
                        $child->removeAttribute($attribute->nodeName);
                        continue;
                    }

                    $sanitized = sanitize_rich_text_attr($tag, $name, $attribute->nodeValue);
                    if ($sanitized === null) {
                        $child->removeAttribute($attribute->nodeName);
                        continue;
                    }

                    $child->setAttribute($attribute->nodeName, $sanitized);
                }
            }

            if ($tag === 'img' && !$child->hasAttribute('src')) {
                $node->removeChild($child);
                continue;
            }

            if ($tag === 'a') {
                if (!$child->hasAttribute('href')) {
                    $child->removeAttribute('target');
                    $child->removeAttribute('rel');
                } elseif (strtolower((string) $child->getAttribute('target')) === '_blank') {
                    $tokens = preg_split('/\s+/', strtolower((string) $child->getAttribute('rel')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
                    $tokens = array_values(array_unique(array_merge($tokens, ['noopener', 'noreferrer'])));
                    $child->setAttribute('rel', implode(' ', $tokens));
                }
            }

            if ($tag === 'img' && !$child->hasAttribute('alt')) {
                $child->setAttribute('alt', '');
            }

            sanitize_rich_text_node($child, $allowedTags);
        }
    }
}

if (!function_exists('sanitize_rich_text_attr')) {
    function sanitize_rich_text_attr(string $tag, string $attribute, string $value): ?string
    {
        $value = trim($value);
        if ($attribute === 'alt') {
            return $value;
        }

        if ($value === '') {
            return null;
        }

        if (in_array($attribute, ['href', 'src'], true)) {
            $decoded = trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($decoded === '' || preg_match('/[\x00-\x1F\x7F]/', $decoded) === 1) {
                return null;
            }

            $normalized = strtolower(preg_replace('/\s+/', '', $decoded) ?? $decoded);
            if (preg_match('/^(javascript|vbscript|data|file):/i', $normalized) === 1) {
                return null;
            }

            if (str_starts_with($decoded, '#') || str_starts_with($decoded, '/') || str_starts_with($decoded, './') || str_starts_with($decoded, '../')) {
                return $decoded;
            }

            if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $decoded) === 1) {
                $scheme = strtolower((string) parse_url($decoded, PHP_URL_SCHEME));
                $allowedSchemes = $tag === 'a' ? ['http', 'https', 'mailto', 'tel'] : ['http', 'https'];
                if (!in_array($scheme, $allowedSchemes, true)) {
                    return null;
                }

                if (in_array($scheme, ['http', 'https'], true)) {
                    return absolute_url($decoded) ?? $decoded;
                }

                return $decoded;
            }

            return $tag === 'img' ? (absolute_url($decoded) ?? $decoded) : $decoded;
        }

        if ($attribute === 'target') {
            return in_array($value, ['_blank', '_self'], true) ? $value : null;
        }

        if ($attribute === 'rel') {
            $tokens = preg_split('/\s+/', strtolower($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $tokens = array_values(array_unique(array_filter($tokens, fn (string $token): bool => preg_match('/^[a-z0-9_-]+$/', $token) === 1)));
            return $tokens === [] ? null : implode(' ', $tokens);
        }

        $value = trim(strip_tags($value));
        return $value !== '' ? $value : null;
    }
}
