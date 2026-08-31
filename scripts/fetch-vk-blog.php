<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$cachePath = $root . '/data/blog/posts.json';
$logPath = $root . '/data/logs/vk-blog-cron.log';
$logMaxBytes = max(65536, (int)(getenv('BIOINMED_VK_LOG_MAX_BYTES') ?: 524288));
$ownerId = (int)(getenv('BIOINMED_VK_OWNER_ID') ?: -232692344);
$count = max(1, min(100, (int)(getenv('BIOINMED_VK_POST_COUNT') ?: 100)));
$apiVersion = getenv('BIOINMED_VK_API_VERSION') ?: '5.199';
$token = getenv('BIOINMED_VK_SERVICE_TOKEN') ?: '';
$fetchComments = (string)(getenv('BIOINMED_VK_FETCH_COMMENTS') ?: '1') !== '0';
$commentsPostLimit = max(0, min($count, (int)(getenv('BIOINMED_VK_COMMENTS_POST_LIMIT') ?: 20)));
$commentsCount = max(0, min(20, (int)(getenv('BIOINMED_VK_COMMENTS_COUNT') ?: 5)));

if ($token === '') {
    $envPath = $root . '/.env';
    if (is_file($envPath)) {
        foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            if (trim($key) === 'BIOINMED_VK_SERVICE_TOKEN') {
                $token = trim($value, " \t\n\r\0\x0B\"'");
                break;
            }
        }
    }
}

if ($token === '') {
    fwrite(STDERR, "BIOINMED_VK_SERVICE_TOKEN is not set\n");
    exit(1);
}

function bioinmed_vk_trim_log(string $logPath, int $maxBytes): void {
    if (!is_file($logPath)) {
        return;
    }

    $size = @filesize($logPath);
    if (!is_int($size) || $size <= $maxBytes) {
        return;
    }

    $keepBytes = max(32768, (int)floor($maxBytes * 0.6));
    $handle = @fopen($logPath, 'rb');
    if (!is_resource($handle)) {
        return;
    }

    if ($size > $keepBytes) {
        @fseek($handle, -$keepBytes, SEEK_END);
    }
    $tail = stream_get_contents($handle);
    fclose($handle);

    if (!is_string($tail) || $tail === '') {
        return;
    }

    $lineStart = strpos($tail, "\n");
    if ($lineStart !== false) {
        $tail = substr($tail, $lineStart + 1);
    }

    $header = '[' . date(DATE_ATOM) . '] Log trimmed from ' . $size . ' bytes' . PHP_EOL;
    @file_put_contents($logPath, $header . $tail, LOCK_EX);
}

bioinmed_vk_trim_log($logPath, $logMaxBytes);

function bioinmed_vk_request(string $method, array $params): array {
    $url = 'https://api.vk.com/method/' . $method . '?' . http_build_query($params);
    $context = stream_context_create([
        'http' => [
            'timeout' => 20,
            'ignore_errors' => true,
            'header' => "Accept: application/json\r\n",
        ],
    ]);
    $raw = @file_get_contents($url, false, $context);
    if (!is_string($raw) || $raw === '') {
        throw new RuntimeException('VK API returned an empty response');
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('VK API returned invalid JSON');
    }
    if (isset($decoded['error'])) {
        $message = (string)($decoded['error']['error_msg'] ?? 'VK API error');
        throw new RuntimeException($message);
    }

    return $decoded['response'] ?? [];
}

function bioinmed_vk_largest_photo(array $photo): ?array {
    $sizes = is_array($photo['sizes'] ?? null) ? $photo['sizes'] : [];
    if (!$sizes) {
        return null;
    }

    usort($sizes, static function (array $left, array $right): int {
        $leftArea = (int)($left['width'] ?? 0) * (int)($left['height'] ?? 0);
        $rightArea = (int)($right['width'] ?? 0) * (int)($right['height'] ?? 0);
        return $rightArea <=> $leftArea;
    });

    $best = $sizes[0] ?? [];
    $url = trim((string)($best['url'] ?? ''));
    if ($url === '') {
        return null;
    }

    return [
        'type' => 'photo',
        'url' => $url,
        'width' => (int)($best['width'] ?? 0),
        'height' => (int)($best['height'] ?? 0),
        'alt' => trim((string)($photo['text'] ?? '')),
    ];
}

function bioinmed_vk_normalize_attachments(array $attachments): array {
    $items = [];

    foreach ($attachments as $attachment) {
        if (!is_array($attachment)) {
            continue;
        }

        $type = (string)($attachment['type'] ?? '');
        if ($type === 'photo' && is_array($attachment['photo'] ?? null)) {
            $photo = bioinmed_vk_largest_photo($attachment['photo']);
            if ($photo) {
                $items[] = $photo;
            }
            continue;
        }

        if ($type === 'video' && is_array($attachment['video'] ?? null)) {
            $video = $attachment['video'];
            $preview = bioinmed_vk_largest_photo(['sizes' => $video['image'] ?? []]);
            $items[] = [
                'type' => 'video',
                'title' => trim((string)($video['title'] ?? 'Видео VK')),
                'url' => 'https://vk.ru/video' . (int)($video['owner_id'] ?? 0) . '_' . (int)($video['id'] ?? 0),
                'preview_url' => $preview['url'] ?? '',
            ];
            continue;
        }

        if ($type === 'link' && is_array($attachment['link'] ?? null)) {
            $link = $attachment['link'];
            $items[] = [
                'type' => 'link',
                'url' => trim((string)($link['url'] ?? '')),
                'title' => trim((string)($link['title'] ?? '')),
                'caption' => trim((string)($link['caption'] ?? '')),
            ];
        }
    }

    return $items;
}

function bioinmed_vk_normalize_comments(array $comments): array {
    $items = [];

    foreach ($comments as $comment) {
        if (!is_array($comment) || !empty($comment['deleted'])) {
            continue;
        }

        $text = trim((string)($comment['text'] ?? ''));
        if ($text === '') {
            continue;
        }

        $items[] = [
            'id' => (int)($comment['id'] ?? 0),
            'from_id' => (int)($comment['from_id'] ?? 0),
            'date' => (int)($comment['date'] ?? 0),
            'text' => $text,
            'likes' => (int)($comment['likes']['count'] ?? 0),
        ];
    }

    return $items;
}

function bioinmed_vk_post_key(array $post): string {
    return (int)($post['owner_id'] ?? 0) . '_' . (int)($post['id'] ?? 0);
}

function bioinmed_vk_read_existing_posts(string $cachePath): array {
    if (!is_file($cachePath)) {
        return [];
    }

    $raw = @file_get_contents($cachePath);
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded) || !is_array($decoded['posts'] ?? null)) {
        return [];
    }

    return array_values(array_filter($decoded['posts'], static function ($post): bool {
        return is_array($post) && (int)($post['id'] ?? 0) > 0;
    }));
}

$response = bioinmed_vk_request('wall.get', [
    'owner_id' => $ownerId,
    'count' => $count,
    'filter' => 'owner',
    'access_token' => $token,
    'v' => $apiVersion,
]);

$posts = [];
foreach (($response['items'] ?? []) as $item) {
    if (!is_array($item)) {
        continue;
    }
    if (($item['post_type'] ?? 'post') !== 'post' || !empty($item['marked_as_ads'])) {
        continue;
    }

    $id = (int)($item['id'] ?? 0);
    if ($id <= 0) {
        continue;
    }

    $posts[] = [
        'id' => $id,
        'owner_id' => (int)($item['owner_id'] ?? $ownerId),
        'date' => (int)($item['date'] ?? 0),
        'text' => trim((string)($item['text'] ?? '')),
        'attachments' => bioinmed_vk_normalize_attachments(is_array($item['attachments'] ?? null) ? $item['attachments'] : []),
        'likes' => (int)($item['likes']['count'] ?? 0),
        'reposts' => (int)($item['reposts']['count'] ?? 0),
        'comments' => (int)($item['comments']['count'] ?? 0),
    ];
}
$fetchedCount = count($posts);

if ($fetchComments && $commentsPostLimit > 0 && $commentsCount > 0) {
    foreach ($posts as $index => $post) {
        if ($index >= $commentsPostLimit) {
            break;
        }
        if ((int)($post['comments'] ?? 0) <= 0) {
            $posts[$index]['comment_items'] = [];
            continue;
        }

        try {
            $commentsResponse = bioinmed_vk_request('wall.getComments', [
                'owner_id' => (int)($post['owner_id'] ?? $ownerId),
                'post_id' => (int)($post['id'] ?? 0),
                'count' => $commentsCount,
                'sort' => 'desc',
                'need_likes' => 1,
                'access_token' => $token,
                'v' => $apiVersion,
            ]);
            $posts[$index]['comment_items'] = bioinmed_vk_normalize_comments(
                is_array($commentsResponse['items'] ?? null) ? $commentsResponse['items'] : []
            );
        } catch (Throwable $exception) {
            $posts[$index]['comment_items'] = [];
            $posts[$index]['comments_error'] = $exception->getMessage();
        }

        usleep(350000);
    }
}

$existingPosts = bioinmed_vk_read_existing_posts($cachePath);
if ($existingPosts) {
    $merged = [];
    $seen = [];

    foreach ($posts as $post) {
        $key = bioinmed_vk_post_key($post);
        if ($key === '0_0') {
            continue;
        }
        $merged[] = $post;
        $seen[$key] = true;
    }

    foreach ($existingPosts as $post) {
        $key = bioinmed_vk_post_key($post);
        if ($key === '0_0' || isset($seen[$key])) {
            continue;
        }
        $merged[] = $post;
        $seen[$key] = true;
    }

    usort($merged, static function (array $left, array $right): int {
        return (int)($right['date'] ?? 0) <=> (int)($left['date'] ?? 0);
    });

    $posts = $merged;
}

$payload = [
    'source' => [
        'provider' => 'vk',
        'owner_id' => $ownerId,
        'url' => 'https://vk.ru/bioinmed',
    ],
    'updated_at' => date(DATE_ATOM),
    'posts' => $posts,
];

$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
if (!is_string($json)) {
    throw new RuntimeException('Failed to encode blog cache');
}

$tmpPath = $cachePath . '.tmp';
if (@file_put_contents($tmpPath, $json . PHP_EOL, LOCK_EX) === false) {
    throw new RuntimeException('Failed to write temporary cache file');
}
if (!@rename($tmpPath, $cachePath)) {
    @unlink($tmpPath);
    throw new RuntimeException('Failed to replace blog cache');
}

echo 'Fetched ' . $fetchedCount . ' VK posts; archive now has ' . count($posts) . ' posts in ' . $cachePath . PHP_EOL;
