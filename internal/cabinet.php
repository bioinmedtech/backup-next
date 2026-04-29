<?php
session_start();
require_once dirname(__DIR__) . '/config.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function loadJsonOrDefault($path, $default) {
    if (is_file($path) && is_readable($path)) {
        $raw = file_get_contents($path);
        $decoded = json_decode((string)$raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }
    return $default;
}

function saveJson($path, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($json)) {
        return false;
    }
    return file_put_contents($path, $json) !== false;
}

function normalizeRole($role) {
    $allowed = ['admin', 'editor', 'viewer'];
    return in_array($role, $allowed, true) ? $role : 'viewer';
}

function canEditContent($role) {
    return in_array($role, ['admin', 'editor'], true);
}

function canManageUsers($role) {
    return $role === 'admin';
}

$dataDir = dirname(__DIR__) . '/data';
$servicesPath = $dataDir . '/services.json';
$faqPath = $dataDir . '/faqs.json';
$reviewsPath = $dataDir . '/reviews.json';
$usersPath = $dataDir . '/users.json';
$serviceAliasesPath = $dataDir . '/service_aliases.json';

if (!is_file($usersPath)) {
    $bootstrapUsers = [];
    if (ADMIN_CABINET_PASSWORD !== '') {
        $bootstrapUsers[] = [
            'username' => ADMIN_CABINET_DEFAULT_USER,
            'password_hash' => password_hash(ADMIN_CABINET_PASSWORD, PASSWORD_DEFAULT),
            'role' => 'admin',
            'active' => true,
        ];
    }
    saveJson($usersPath, $bootstrapUsers);
}

$users = loadJsonOrDefault($usersPath, []);
$serviceRows = loadJsonOrDefault($servicesPath, $services);
$serviceAliases = loadJsonOrDefault($serviceAliasesPath, []);
$faqRows = loadJsonOrDefault($faqPath, $faq_items);
$reviewRows = loadJsonOrDefault($reviewsPath, $cases);

$message = '';
$error = '';

if (isset($_POST['action']) && $_POST['action'] === 'logout') {
    unset($_SESSION['cabinet_user']);
    header('Location: /admin');
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $found = null;

    foreach ($users as $user) {
        if (($user['username'] ?? '') === $username && !empty($user['active'])) {
            $found = $user;
            break;
        }
    }

    if ($found && password_verify($password, (string)($found['password_hash'] ?? ''))) {
        $_SESSION['cabinet_user'] = [
            'username' => $found['username'],
            'role' => normalizeRole((string)($found['role'] ?? 'viewer')),
        ];
        header('Location: /admin');
        exit;
    }

    $error = 'Неверный логин или пароль.';
}

$authUser = $_SESSION['cabinet_user'] ?? null;
$isAuthenticated = is_array($authUser) && isset($authUser['username'], $authUser['role']);
$currentRole = $isAuthenticated ? normalizeRole((string)$authUser['role']) : 'viewer';

// Если не авторизован, перенаправляем на красивую форму входа
if (!$isAuthenticated) {
    header('Location: /admin/login');
    exit;
}

if ($isAuthenticated && isset($_POST['action'])) {
    $action = (string)$_POST['action'];

    if ($action === 'save_services' && canEditContent($currentRole)) {
        $ids = $_POST['id'] ?? [];
        $names = $_POST['name'] ?? [];
        $subtitles = $_POST['subtitle'] ?? [];
        $categories = $_POST['category'] ?? [];
        $prices = $_POST['price'] ?? [];
        $priceNotes = $_POST['price_note'] ?? [];
        $descriptions = $_POST['description'] ?? [];
        $details = $_POST['details'] ?? [];
        $targets = $_POST['target'] ?? [];
        $orders = $_POST['order'] ?? [];

        $newServices = [];
        $count = count($ids);
        for ($i = 0; $i < $count; $i++) {
            $id = trim((string)($ids[$i] ?? ''));
            if ($id === '') {
                continue;
            }
            $newServices[] = [
                'id' => $id,
                'name' => trim((string)($names[$i] ?? '')),
                'subtitle' => trim((string)($subtitles[$i] ?? '')),
                'category' => trim((string)($categories[$i] ?? '')),
                'price' => trim((string)($prices[$i] ?? '')),
                'price_note' => trim((string)($priceNotes[$i] ?? '')),
                'description' => trim((string)($descriptions[$i] ?? '')),
                'details' => trim((string)($details[$i] ?? '')),
                'target' => trim((string)($targets[$i] ?? '')),
                'order' => intval($orders[$i] ?? ($i + 1)),
            ];
        }

        usort($newServices, function ($a, $b) {
            return intval($a['order'] ?? 999) <=> intval($b['order'] ?? 999);
        });

        if (saveJson($servicesPath, $newServices)) {
            $serviceRows = $newServices;
            $message = 'Услуги сохранены.';
        } else {
            $error = 'Не удалось сохранить услуги.';
        }
    }

    if ($action === 'save_faq' && canEditContent($currentRole)) {
        $questions = $_POST['faq_question'] ?? [];
        $answers = $_POST['faq_answer'] ?? [];

        $newFaq = [];
        $count = max(count($questions), count($answers));
        for ($i = 0; $i < $count; $i++) {
            $q = trim((string)($questions[$i] ?? ''));
            $a = trim((string)($answers[$i] ?? ''));
            if ($q === '' && $a === '') {
                continue;
            }
            $newFaq[] = [
                'question' => $q,
                'answer' => $a,
            ];
        }

        if (saveJson($faqPath, $newFaq)) {
            $faqRows = $newFaq;
            $message = 'FAQ сохранен.';
        } else {
            $error = 'Не удалось сохранить FAQ.';
        }
    }

    if ($action === 'save_reviews' && canEditContent($currentRole)) {
        $patients = $_POST['review_patient'] ?? [];
        $problems = $_POST['review_problem'] ?? [];
        $treatments = $_POST['review_treatment'] ?? [];
        $results = $_POST['review_result'] ?? [];
        $ratings = $_POST['review_rating'] ?? [];

        $newReviews = [];
        $count = max(count($patients), count($problems), count($treatments), count($results), count($ratings));
        for ($i = 0; $i < $count; $i++) {
            $patient = trim((string)($patients[$i] ?? ''));
            $problem = trim((string)($problems[$i] ?? ''));
            $treatment = trim((string)($treatments[$i] ?? ''));
            $result = trim((string)($results[$i] ?? ''));
            $rating = intval($ratings[$i] ?? 5);

            if ($patient === '' && $problem === '' && $treatment === '' && $result === '') {
                continue;
            }

            $newReviews[] = [
                'patient' => $patient,
                'problem' => $problem,
                'treatment' => $treatment,
                'result' => $result,
                'rating' => max(1, min(5, $rating)),
            ];
        }

        if (saveJson($reviewsPath, $newReviews)) {
            $reviewRows = $newReviews;
            $message = 'Отзывы сохранены.';
        } else {
            $error = 'Не удалось сохранить отзывы.';
        }
    }

    if ($action === 'create_user' && canManageUsers($currentRole)) {
        $newUsername = trim((string)($_POST['new_username'] ?? ''));
        $newPassword = (string)($_POST['new_password'] ?? '');
        $newRole = normalizeRole((string)($_POST['new_role'] ?? 'viewer'));

        $exists = false;
        foreach ($users as $user) {
            if (($user['username'] ?? '') === $newUsername) {
                $exists = true;
                break;
            }
        }

        if ($newUsername === '' || $newPassword === '') {
            $error = 'Для создания пользователя нужны логин и пароль.';
        } elseif ($exists) {
            $error = 'Пользователь с таким логином уже существует.';
        } else {
            $users[] = [
                'username' => $newUsername,
                'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                'role' => $newRole,
                'active' => true,
            ];
            if (saveJson($usersPath, $users)) {
                $message = 'Пользователь создан.';
            } else {
                $error = 'Не удалось сохранить пользователей.';
            }
        }
    }

    if ($action === 'update_user' && canManageUsers($currentRole)) {
        $username = trim((string)($_POST['edit_username'] ?? ''));
        $newRole = normalizeRole((string)($_POST['edit_role'] ?? 'viewer'));
        $newPassword = (string)($_POST['edit_password'] ?? '');
        $isActive = isset($_POST['edit_active']) && $_POST['edit_active'] === '1';

        foreach ($users as &$user) {
            if (($user['username'] ?? '') === $username) {
                $user['role'] = $newRole;
                $user['active'] = $isActive;
                if ($newPassword !== '') {
                    $user['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
                }
            }
        }
        unset($user);

        if (saveJson($usersPath, $users)) {
            if ($authUser['username'] === $username) {
                $_SESSION['cabinet_user']['role'] = $newRole;
                $currentRole = $newRole;
            }
            $message = 'Пользователь обновлен.';
        } else {
            $error = 'Не удалось обновить пользователя.';
        }
    }
}

$serviceRows = array_values($serviceRows);
$faqRows = array_values($faqRows);
$reviewRows = array_values($reviewRows);

if (count($serviceRows) < 8) {
    for ($i = count($serviceRows); $i < 8; $i++) {
        $serviceRows[] = [
            'id' => '',
            'name' => '',
            'subtitle' => '',
            'category' => '',
            'price' => '',
            'price_note' => '',
            'description' => '',
            'details' => '',
            'target' => '',
            'order' => $i + 1,
        ];
    }
}

for ($i = 0; $i < 2; $i++) {
    $faqRows[] = ['question' => '', 'answer' => ''];
    $reviewRows[] = ['patient' => '', 'problem' => '', 'treatment' => '', 'result' => '', 'rating' => 5];
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кабинет управления | БИОИНМЕД</title>
    <meta name="robots" content="noindex,nofollow,noarchive,nosnippet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-[#f3f8fd] text-[#0f2749] antialiased">
    <main class="mx-auto max-w-7xl px-6 py-10 md:px-10">
        <div class="rounded-3xl border border-[#d8e6f3] bg-white p-6 shadow-[0_16px_40px_rgba(8,36,70,0.08)] md:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-[#0f3463]">Кабинет управления</h1>
                    <p class="mt-2 text-sm text-[#355b89]">URL: /cabinet или /internal/cabinet.php</p>
                </div>
                <?php if ($isAuthenticated): ?>
                    <div class="text-right">
                        <p class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94]">Пользователь: <?php echo e($authUser['username']); ?></p>
                        <p class="mt-1 text-xs uppercase tracking-[0.08em] text-[#5f7fa3]">Роль: <?php echo e($currentRole); ?></p>
                        <form method="post" class="mt-2">
                            <input type="hidden" name="action" value="logout">
                            <button type="submit" class="rounded-full border border-[#c8ddee] bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94]">Выйти</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($error !== ''): ?>
                <div class="mt-4 rounded-xl border border-[#f3c2c2] bg-[#fff4f4] px-4 py-3 text-sm text-[#8a2d2d]"><?php echo e($error); ?></div>
            <?php endif; ?>

            <?php if ($message !== ''): ?>
                <div class="mt-4 rounded-xl border border-[#c8eacb] bg-[#f3fff4] px-4 py-3 text-sm text-[#216a2b]"><?php echo e($message); ?></div>
            <?php endif; ?>

            <?php if (canEditContent($currentRole)): ?>
                    <section class="mt-8">
                        <h2 class="text-lg font-bold text-[#0f3463]">Услуги</h2>
                        <?php if (!empty($serviceAliases) && is_array($serviceAliases)): ?>
                            <div class="mt-3 rounded-2xl border border-[#dce8f5] bg-[#f3f9ff] p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94]">Alias mapping (дубли → канонический ID)</p>
                                <div class="mt-2 grid gap-1 md:grid-cols-2">
                                    <?php foreach ($serviceAliases as $aliasId => $canonicalId): ?>
                                        <p class="text-xs text-[#355b89]"><strong><?php echo e((string)$aliasId); ?></strong> → <?php echo e((string)$canonicalId); ?></p>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <form method="post" class="mt-3 space-y-4">
                            <input type="hidden" name="action" value="save_services">
                            <?php foreach ($serviceRows as $index => $row): ?>
                                <article class="rounded-2xl border border-[#dce8f5] bg-[#f8fcff] p-4">
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <div><label class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94]">ID</label><input name="id[]" value="<?php echo e($row['id'] ?? ''); ?>" class="mt-1 w-full rounded-lg border border-[#d6e4f2] px-3 py-2 text-sm"></div>
                                        <div><label class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94]">Порядок</label><input name="order[]" value="<?php echo e((string)($row['order'] ?? ($index + 1))); ?>" class="mt-1 w-full rounded-lg border border-[#d6e4f2] px-3 py-2 text-sm"></div>
                                        <div><label class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94]">Название</label><input name="name[]" value="<?php echo e($row['name'] ?? ''); ?>" class="mt-1 w-full rounded-lg border border-[#d6e4f2] px-3 py-2 text-sm"></div>
                                        <div><label class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94]">Подзаголовок</label><input name="subtitle[]" value="<?php echo e($row['subtitle'] ?? ''); ?>" class="mt-1 w-full rounded-lg border border-[#d6e4f2] px-3 py-2 text-sm"></div>
                                        <div><label class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94]">Категория</label><input name="category[]" value="<?php echo e($row['category'] ?? ''); ?>" class="mt-1 w-full rounded-lg border border-[#d6e4f2] px-3 py-2 text-sm"></div>
                                        <div><label class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94]">Цена</label><input name="price[]" value="<?php echo e($row['price'] ?? ''); ?>" class="mt-1 w-full rounded-lg border border-[#d6e4f2] px-3 py-2 text-sm"></div>
                                        <div><label class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94]">Примечание к цене</label><input name="price_note[]" value="<?php echo e($row['price_note'] ?? ''); ?>" class="mt-1 w-full rounded-lg border border-[#d6e4f2] px-3 py-2 text-sm"></div>
                                        <div><label class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94]">Короткое описание</label><textarea name="description[]" rows="2" class="mt-1 w-full rounded-lg border border-[#d6e4f2] px-3 py-2 text-sm"><?php echo e($row['description'] ?? ''); ?></textarea></div>
                                        <div><label class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94]">Как проходит</label><textarea name="details[]" rows="2" class="mt-1 w-full rounded-lg border border-[#d6e4f2] px-3 py-2 text-sm"><?php echo e($row['details'] ?? ''); ?></textarea></div>
                                        <div class="md:col-span-2"><label class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94]">Для кого подходит</label><textarea name="target[]" rows="2" class="mt-1 w-full rounded-lg border border-[#d6e4f2] px-3 py-2 text-sm"><?php echo e($row['target'] ?? ''); ?></textarea></div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                            <button type="submit" class="rounded-full bg-[#1f7fbe] px-6 py-2.5 text-xs font-semibold uppercase tracking-[0.08em] text-white">Сохранить услуги</button>
                        </form>
                    </section>

                    <section class="mt-10">
                        <h2 class="text-lg font-bold text-[#0f3463]">FAQ</h2>
                        <form method="post" class="mt-3 space-y-3">
                            <input type="hidden" name="action" value="save_faq">
                            <?php foreach ($faqRows as $row): ?>
                                <article class="rounded-2xl border border-[#dce8f5] bg-[#f8fcff] p-4">
                                    <label class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94]">Вопрос</label>
                                    <input name="faq_question[]" value="<?php echo e($row['question'] ?? ''); ?>" class="mt-1 w-full rounded-lg border border-[#d6e4f2] px-3 py-2 text-sm">
                                    <label class="mt-3 block text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94]">Ответ</label>
                                    <textarea name="faq_answer[]" rows="2" class="mt-1 w-full rounded-lg border border-[#d6e4f2] px-3 py-2 text-sm"><?php echo e($row['answer'] ?? ''); ?></textarea>
                                </article>
                            <?php endforeach; ?>
                            <button type="submit" class="rounded-full bg-[#1f7fbe] px-6 py-2.5 text-xs font-semibold uppercase tracking-[0.08em] text-white">Сохранить FAQ</button>
                        </form>
                    </section>

                    <section class="mt-10">
                        <h2 class="text-lg font-bold text-[#0f3463]">Отзывы</h2>
                        <form method="post" class="mt-3 space-y-3">
                            <input type="hidden" name="action" value="save_reviews">
                            <?php foreach ($reviewRows as $row): ?>
                                <article class="rounded-2xl border border-[#dce8f5] bg-[#f8fcff] p-4">
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <div><label class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94]">Пациент</label><input name="review_patient[]" value="<?php echo e($row['patient'] ?? ''); ?>" class="mt-1 w-full rounded-lg border border-[#d6e4f2] px-3 py-2 text-sm"></div>
                                        <div><label class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94]">Оценка (1-5)</label><input name="review_rating[]" value="<?php echo e((string)($row['rating'] ?? 5)); ?>" class="mt-1 w-full rounded-lg border border-[#d6e4f2] px-3 py-2 text-sm"></div>
                                        <div><label class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94]">Проблема</label><textarea name="review_problem[]" rows="2" class="mt-1 w-full rounded-lg border border-[#d6e4f2] px-3 py-2 text-sm"><?php echo e($row['problem'] ?? ''); ?></textarea></div>
                                        <div><label class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94]">Лечение</label><textarea name="review_treatment[]" rows="2" class="mt-1 w-full rounded-lg border border-[#d6e4f2] px-3 py-2 text-sm"><?php echo e($row['treatment'] ?? ''); ?></textarea></div>
                                        <div class="md:col-span-2"><label class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94]">Результат</label><textarea name="review_result[]" rows="2" class="mt-1 w-full rounded-lg border border-[#d6e4f2] px-3 py-2 text-sm"><?php echo e($row['result'] ?? ''); ?></textarea></div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                            <button type="submit" class="rounded-full bg-[#1f7fbe] px-6 py-2.5 text-xs font-semibold uppercase tracking-[0.08em] text-white">Сохранить отзывы</button>
                        </form>
                    </section>
                <?php else: ?>
                    <div class="mt-8 rounded-xl border border-[#dce8f5] bg-[#f8fcff] px-4 py-3 text-sm text-[#355b89]">Роль viewer: доступ только на просмотр. Для редактирования назначьте роль editor или admin.</div>
                <?php endif; ?>

                <?php if (canManageUsers($currentRole)): ?>
                    <section class="mt-10 border-t border-[#e3edf6] pt-8">
                        <h2 class="text-lg font-bold text-[#0f3463]">Пользователи и уровни доступа</h2>

                        <form method="post" class="mt-4 rounded-2xl border border-[#dce8f5] bg-[#f8fcff] p-4">
                            <input type="hidden" name="action" value="create_user">
                            <div class="grid gap-3 md:grid-cols-4">
                                <div><label class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94]">Логин</label><input name="new_username" class="mt-1 w-full rounded-lg border border-[#d6e4f2] px-3 py-2 text-sm" required></div>
                                <div><label class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94]">Пароль</label><input type="password" name="new_password" class="mt-1 w-full rounded-lg border border-[#d6e4f2] px-3 py-2 text-sm" required></div>
                                <div><label class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94]">Роль</label><select name="new_role" class="mt-1 w-full rounded-lg border border-[#d6e4f2] px-3 py-2 text-sm"><option value="viewer">viewer</option><option value="editor">editor</option><option value="admin">admin</option></select></div>
                                <div class="flex items-end"><button type="submit" class="w-full rounded-full bg-[#1f7fbe] px-4 py-2.5 text-xs font-semibold uppercase tracking-[0.08em] text-white">Создать</button></div>
                            </div>
                        </form>

                        <div class="mt-4 space-y-3">
                            <?php foreach ($users as $user): ?>
                                <form method="post" class="rounded-2xl border border-[#dce8f5] bg-white p-4">
                                    <input type="hidden" name="action" value="update_user">
                                    <input type="hidden" name="edit_username" value="<?php echo e($user['username'] ?? ''); ?>">
                                    <div class="grid gap-3 md:grid-cols-5">
                                        <div><label class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94]">Логин</label><input value="<?php echo e($user['username'] ?? ''); ?>" class="mt-1 w-full rounded-lg border border-[#d6e4f2] bg-[#f5f9ff] px-3 py-2 text-sm" disabled></div>
                                        <div><label class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94]">Роль</label><select name="edit_role" class="mt-1 w-full rounded-lg border border-[#d6e4f2] px-3 py-2 text-sm"><option value="viewer" <?php echo (($user['role'] ?? '') === 'viewer') ? 'selected' : ''; ?>>viewer</option><option value="editor" <?php echo (($user['role'] ?? '') === 'editor') ? 'selected' : ''; ?>>editor</option><option value="admin" <?php echo (($user['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>admin</option></select></div>
                                        <div><label class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94]">Новый пароль</label><input type="password" name="edit_password" placeholder="оставьте пустым" class="mt-1 w-full rounded-lg border border-[#d6e4f2] px-3 py-2 text-sm"></div>
                                        <div><label class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2a5a94]">Статус</label><select name="edit_active" class="mt-1 w-full rounded-lg border border-[#d6e4f2] px-3 py-2 text-sm"><option value="1" <?php echo !empty($user['active']) ? 'selected' : ''; ?>>active</option><option value="0" <?php echo empty($user['active']) ? 'selected' : ''; ?>>disabled</option></select></div>
                                        <div class="flex items-end"><button type="submit" class="w-full rounded-full border border-[#c6ddee] bg-[#f3f9ff] px-4 py-2.5 text-xs font-semibold uppercase tracking-[0.08em] text-[#1f4f83]">Обновить</button></div>
                                    </div>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        </main>
    </body>
</html>
