<?php

declare(strict_types=1);

function bioinmed_editable_list_resolve_texts(array $pageData, string $basePath, array $fallback): array {
    $overrides = $pageData;
    foreach (explode('.', $basePath) as $part) {
        if (!is_array($overrides) || !array_key_exists($part, $overrides)) {
            $overrides = [];
            break;
        }
        $overrides = $overrides[$part];
    }
    $overrides = is_array($overrides) ? $overrides : [];
    $indices = array_unique(array_merge(array_keys($fallback), array_keys($overrides)));
    usort($indices, static function ($left, $right): int {
        $leftNumber = (int)$left;
        $rightNumber = (int)$right;
        if ($leftNumber !== $rightNumber) return $leftNumber <=> $rightNumber;
        $leftIsNumber = ctype_digit((string)$left);
        $rightIsNumber = ctype_digit((string)$right);
        if ($leftIsNumber !== $rightIsNumber) return $leftIsNumber ? -1 : 1;
        return strcmp((string)$left, (string)$right);
    });
    $resolved = [];
    foreach ($indices as $index) {
        $entry = $fallback[$index] ?? '';
        $defaultText = is_array($entry) ? (string)($entry['text'] ?? $entry['title'] ?? '') : (string)$entry;
        $current = array_key_exists($index, $overrides) ? $overrides[$index] : $defaultText;
        $text = is_string($current) ? $current : $defaultText;
        if (is_array($entry)) {
            $entry['text'] = $text;
            $resolved[] = $entry;
        } else {
            $resolved[] = $text;
        }
    }
    return $resolved;
}

function bioinmed_editable_list_items(array $pageData, string $listKey, array $fallback, string $defaultIcon = ''): array {
    $stored = $pageData['editable_lists'][$listKey] ?? null;
    $source = is_array($stored) ? $stored : $fallback;
    $items = [];
    $usedIds = [];

    foreach ($source as $index => $entry) {
        if (is_array($entry)) {
            $text = trim((string)($entry['text'] ?? $entry['title'] ?? ''));
            $secondary = trim((string)($entry['secondary'] ?? $entry['description'] ?? ''));
            $url = trim((string)($entry['url'] ?? $entry['href'] ?? ''));
            $icon = trim((string)($entry['icon'] ?? $defaultIcon));
            $hidden = !empty($entry['hidden']);
            $rawId = trim((string)($entry['id'] ?? ''));
        } else {
            $text = trim((string)$entry);
            $secondary = '';
            $url = '';
            $icon = $defaultIcon;
            $hidden = false;
            $rawId = '';
        }
        if ($text === '') continue;

        $id = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $rawId) ?? '';
        $id = trim($id, '-_');
        if ($id === '') $id = 'item-' . substr(hash('sha256', $listKey . '|' . $index . '|' . $text), 0, 12);
        $baseId = $id;
        $counter = 2;
        while (isset($usedIds[$id])) $id = $baseId . '-' . $counter++;
        $usedIds[$id] = true;

        $items[] = ['id' => $id, 'text' => $text, 'secondary' => $secondary, 'url' => $url, 'icon' => $icon, 'hidden' => $hidden];
    }

    return $items;
}

function bioinmed_editable_list_attrs(string $page, string $listKey, string $title, bool $allowIcon = true, string $secondaryLabel = '', string $urlLabel = ''): string {
    $e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    return ' data-admin-list-root="1" data-admin-list-page="' . $e($page) . '" data-admin-list-key="' . $e($listKey) . '" data-admin-list-title="' . $e($title) . '" data-admin-list-icons="' . ($allowIcon ? '1' : '0') . '" data-admin-list-secondary-label="' . $e($secondaryLabel) . '" data-admin-list-url-label="' . $e($urlLabel) . '"';
}

function bioinmed_editable_list_item_attrs(array $item): string {
    $e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    return ' data-admin-list-item="1" data-admin-list-item-id="' . $e((string)$item['id']) . '" data-admin-list-item-text="' . $e((string)$item['text']) . '" data-admin-list-item-secondary="' . $e((string)($item['secondary'] ?? '')) . '" data-admin-list-item-url="' . $e((string)($item['url'] ?? '')) . '" data-admin-list-item-icon="' . $e((string)$item['icon']) . '" data-admin-list-item-hidden="' . (!empty($item['hidden']) ? '1' : '0') . '"';
}

function bioinmed_editable_list_item_class(array $item): string {
    return !empty($item['hidden']) ? ' bioinmed-editable-list-item-hidden' : '';
}

function bioinmed_editable_list_toolbar(string $tag = 'li'): string {
    $tag = $tag === 'div' ? 'div' : 'li';
    return '<' . $tag . ' class="bioinmed-editable-list-toolbar" data-admin-disable-block-edit="1"><button type="button" class="price-admin-inline-btn" data-admin-list-action="add"><i class="fa-solid fa-plus" aria-hidden="true"></i><span>Добавить первый элемент</span></button></' . $tag . '>';
}

function bioinmed_editable_list_actions(array $item): string {
    $hiddenLabel = !empty($item['hidden']) ? 'Показать' : 'Скрыть';
    return '<div class="bioinmed-editable-list-actions" data-admin-disable-block-edit="1">'
        . '<button type="button" class="price-admin-inline-btn" data-admin-list-action="move-up" title="Поднять выше"><span aria-hidden="true">↑</span><span>Выше</span></button>'
        . '<button type="button" class="price-admin-inline-btn" data-admin-list-action="move-down" title="Опустить ниже"><span aria-hidden="true">↓</span><span>Ниже</span></button>'
        . '<button type="button" class="price-admin-inline-btn" data-admin-list-action="add-after">Добавить ниже</button>'
        . '<button type="button" class="price-admin-inline-btn" data-admin-list-action="edit">Редактировать</button>'
        . '<button type="button" class="price-admin-inline-btn" data-admin-list-action="toggle-hidden">' . $hiddenLabel . '</button>'
        . '<button type="button" class="price-admin-inline-btn price-admin-inline-btn-danger" data-admin-list-action="delete">Удалить</button>'
        . '</div>';
}

function bioinmed_render_editable_icon_list(
    array $pageData,
    string $page,
    string $listKey,
    array $fallback,
    string $title,
    string $ulClass,
    string $liClass,
    string $defaultIcon = 'fa-solid fa-check'
): void {
    $items = bioinmed_editable_list_items($pageData, $listKey, $fallback, $defaultIcon);
    $e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    echo '<ul class="' . $e($ulClass) . '"' . bioinmed_editable_list_attrs($page, $listKey, $title) . '>';
    echo bioinmed_editable_list_toolbar();
    foreach ($items as $item) {
        echo '<li class="' . $e($liClass) . bioinmed_editable_list_item_class($item) . '"' . bioinmed_editable_list_item_attrs($item) . '>';
        echo '<i class="' . $e((string)$item['icon']) . ' mt-0.5 shrink-0 text-[#1977b2]" data-admin-list-icon-view aria-hidden="true"></i>';
        echo '<span data-admin-list-text-view>' . $e((string)$item['text']) . '</span>';
        echo bioinmed_editable_list_actions($item) . '</li>';
    }
    echo '</ul>';
}
