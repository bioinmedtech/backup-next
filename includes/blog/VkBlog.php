<?php

function bioinmed_blog_cache_path(): string {
    return __DIR__ . '/../../data/blog/posts.json';
}

function bioinmed_blog_empty_cache(): array {
    return [
        'source' => [
            'provider' => 'vk',
            'owner_id' => -232692344,
            'url' => 'https://vk.ru/bioinmed',
        ],
        'updated_at' => null,
        'posts' => [],
    ];
}

function bioinmed_blog_read_cache(): array {
    $path = bioinmed_blog_cache_path();
    if (!is_file($path)) {
        return bioinmed_blog_empty_cache();
    }

    $raw = @file_get_contents($path);
    if (!is_string($raw) || trim($raw) === '') {
        return bioinmed_blog_empty_cache();
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return bioinmed_blog_empty_cache();
    }

    $cache = array_replace_recursive(bioinmed_blog_empty_cache(), $decoded);
    $cache['posts'] = is_array($cache['posts'] ?? null) ? $cache['posts'] : [];

    return $cache;
}

function bioinmed_blog_post_url(array $post, bool $absolute = false): string {
    $id = (int)($post['id'] ?? 0);
    $slug = $id > 0 ? bioinmed_blog_slug($post) : '';
    $local = $id > 0 ? '/blog/post/' . $id . ($slug !== '' ? '-' . $slug : '') . '/' : '/blog';

    if ($absolute && function_exists('bioinmed_absolute_url')) {
        return bioinmed_absolute_url($local);
    }

    return $local;
}

function bioinmed_blog_slug(array $post): string {
    $title = bioinmed_blog_title($post);
    $map = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
        'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
        'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
        'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '',
        'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
    ];
    $slug = strtr(mb_strtolower($title, 'UTF-8'), $map);
    $slug = preg_replace('/[^a-z0-9]+/u', '-', $slug) ?? '';
    $slug = trim($slug, '-');
    $slug = preg_replace('/-+/', '-', $slug) ?? '';

    if ($slug === '') {
        return 'post';
    }

    return substr($slug, 0, 90);
}

function bioinmed_blog_post_id_from_identifier($identifier): int {
    $value = trim((string)$identifier);
    if ($value === '') {
        return 0;
    }

    if (preg_match('/^(\d+)/', $value, $matches)) {
        return (int)$matches[1];
    }

    return 0;
}

function bioinmed_blog_current_post_identifier(): string {
    $slug = trim((string)($_GET['slug'] ?? ''));
    if ($slug !== '') {
        return $slug;
    }

    $id = trim((string)($_GET['id'] ?? ''));
    if ($id !== '') {
        return $id;
    }

    $path = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    if (is_string($path) && preg_match('#/blog/post/([^/]+)/?#', $path, $matches)) {
        return (string)$matches[1];
    }

    return '';
}

function bioinmed_blog_find_post($id): ?array {
    $postId = bioinmed_blog_post_id_from_identifier($id);
    if ($postId <= 0) {
        return null;
    }

    $cache = bioinmed_blog_read_cache();
    $posts = is_array($cache['posts'] ?? null) ? $cache['posts'] : [];
    foreach ($posts as $post) {
        if (is_array($post) && (int)($post['id'] ?? 0) === $postId) {
            return $post;
        }
    }

    return null;
}

function bioinmed_blog_primary_image(array $post): string {
    $attachments = is_array($post['attachments'] ?? null) ? $post['attachments'] : [];
    foreach ($attachments as $attachment) {
        if (!is_array($attachment)) {
            continue;
        }
        if (($attachment['type'] ?? '') === 'photo' && !empty($attachment['url'])) {
            return (string)$attachment['url'];
        }
        if (($attachment['type'] ?? '') === 'video' && !empty($attachment['preview_url'])) {
            return (string)$attachment['preview_url'];
        }
    }

    return '';
}

function bioinmed_blog_comments(array $post): array {
    $comments = is_array($post['comment_items'] ?? null) ? $post['comment_items'] : [];
    return array_values(array_filter($comments, static function ($comment): bool {
        return is_array($comment) && trim((string)($comment['text'] ?? '')) !== '';
    }));
}

function bioinmed_blog_related_posts(array $currentPost = [], int $limit = 4): array {
    $currentId = (int)($currentPost['id'] ?? 0);
    $cache = bioinmed_blog_read_cache();
    $posts = is_array($cache['posts'] ?? null) ? $cache['posts'] : [];
    $related = [];

    foreach ($posts as $post) {
        if (!is_array($post) || (int)($post['id'] ?? 0) === $currentId) {
            continue;
        }
        $related[] = $post;
        if (count($related) >= $limit) {
            break;
        }
    }

    return $related;
}

function bioinmed_blog_vk_post_url(array $post): string {
    $ownerId = (int)($post['owner_id'] ?? -232692344);
    $id = (int)($post['id'] ?? 0);
    if ($id <= 0) {
        return 'https://vk.ru/bioinmed';
    }

    return 'https://vk.ru/wall' . $ownerId . '_' . $id;
}

function bioinmed_blog_format_date($timestamp, string $format = 'd.m.Y H:i'): string {
    $time = (int)$timestamp;
    if ($time <= 0) {
        return '';
    }

    return date($format, $time);
}

function bioinmed_blog_excerpt(string $text, int $limit = 220): string {
    $normalized = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    if ($normalized === '' || mb_strlen($normalized, 'UTF-8') <= $limit) {
        return $normalized;
    }

    $slice = mb_substr($normalized, 0, max(1, $limit - 1), 'UTF-8');
    $lastSpace = mb_strrpos($slice, ' ', 0, 'UTF-8');
    if ($lastSpace !== false && $lastSpace > 80) {
        $slice = mb_substr($slice, 0, $lastSpace, 'UTF-8');
    }

    return rtrim($slice, " \t\n\r\0\x0B.,;:!-") . '.';
}

function bioinmed_blog_title(array $post): string {
    $text = trim((string)($post['text'] ?? ''));
    if ($text === '') {
        return 'Публикация БИОИНМЕД';
    }

    $firstLine = trim((string)preg_split('/\R/u', $text, 2)[0]);
    $title = bioinmed_blog_excerpt($firstLine !== '' ? $firstLine : $text, 86);

    return $title !== '' ? $title : 'Публикация БИОИНМЕД';
}

function bioinmed_blog_body_text(array $post): string {
    $text = trim((string)($post['text'] ?? ''));
    if ($text === '') {
        return '';
    }

    $parts = preg_split('/\R/u', $text, 2);
    $firstLine = trim((string)($parts[0] ?? ''));
    $rest = trim((string)($parts[1] ?? ''));
    $title = bioinmed_blog_title($post);

    $normalize = static function (string $value): string {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
        $value = rtrim($value, " \t\n\r\0\x0B.,;:!-");
        return mb_strtolower($value, 'UTF-8');
    };

    if ($rest !== '' && $normalize($firstLine) === $normalize($title)) {
        return $rest;
    }

    return $text;
}
