<?php
/**
 * Улучшенная админ-панель v3.0
 * - Полноценная система управления
 * - Версионность контента
 * - Real-time редактор
 * - Множественные роли
 */
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
    @mkdir(dirname($path), 0755, true);
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (!is_string($json)) return false;
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

function sanitizeSlug($value) {
    $slug = strtolower(trim((string)$value));
    $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug) ?? '';
    return trim($slug, '-');
}

function countFilledRows($rows, $key) {
    $count = 0;
    foreach ($rows as $row) {
        if (trim((string)($row[$key] ?? '')) !== '') {
            $count++;
        }
    }
    return $count;
}

function appendAudit($path, $action, $entityType, $entityId, $meta = []) {
    $auditRows = loadJsonOrDefault($path, []);
    $auditRows[] = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user' => $_SESSION['cabinet_user']['username'] ?? 'unknown',
        'role' => $_SESSION['cabinet_user']['role'] ?? 'unknown',
        'action' => $action,
        'entity_type' => $entityType,
        'entity_id' => (string)$entityId,
        'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
        'meta' => $meta,
    ];

    if (count($auditRows) > 1000) {
        $auditRows = array_slice($auditRows, -1000);
    }

    return saveJson($path, $auditRows);
}

// Сохранить версию контента (для истории изменений)
function saveVersion($entityType, $entityId, $data) {
    $versionsDir = dirname(__DIR__) . '/data/versions';
    @mkdir($versionsDir, 0755, true);
    
    $versionFile = "$versionsDir/{$entityType}_{$entityId}.json";
    $versions = loadJsonOrDefault($versionFile, []);
    
    $versions[] = [
        'timestamp' => date('Y-m-d H:i:s'),
        'data' => $data,
        'user' => $_SESSION['cabinet_user']['username'] ?? 'unknown',
    ];
    
    // Хранить только последние 50 версий
    if (count($versions) > 50) {
        $versions = array_slice($versions, -50);
    }
    
    return saveJson($versionFile, $versions);
}

$dataDir = dirname(__DIR__) . '/data';
$servicesPath = $dataDir . '/services.json';
$doctorsPath = $dataDir . '/doctors.json';
$faqPath = $dataDir . '/faqs.json';
$reviewsPath = $dataDir . '/reviews.json';
$usersPath = $dataDir . '/users.json';
$auditPath = $dataDir . '/audit-log.json';

// Создать users.json если не существует
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
$doctorRows = loadJsonOrDefault($doctorsPath, $doctors);
$faqRows = loadJsonOrDefault($faqPath, $faq_items);
$reviewRows = loadJsonOrDefault($reviewsPath, $cases);
$auditRows = loadJsonOrDefault($auditPath, []);

$message = '';
$error = '';
$tab = $_GET['tab'] ?? 'dashboard';

// Проверка авторизации
$authUser = $_SESSION['cabinet_user'] ?? null;
$isAuthenticated = is_array($authUser) && isset($authUser['username'], $authUser['role']);
$currentRole = $isAuthenticated ? normalizeRole((string)$authUser['role']) : 'viewer';

if (!$isAuthenticated) {
    header('Location: /admin/login');
    exit;
}

// Обработка выхода
if (isset($_POST['action']) && $_POST['action'] === 'logout') {
    appendAudit($auditPath, 'logout', 'session', 'current', []);
    unset($_SESSION['cabinet_user']);
    header('Location: /admin/login');
    exit;
}

// CRUD операции
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
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
        $seenIds = [];
        for ($i = 0; $i < count($ids); $i++) {
            $id = sanitizeSlug((string)($ids[$i] ?? ''));
            if ($id === '') continue;

            if (isset($seenIds[$id])) {
                $error = '✗ Дублирующийся ID услуги: ' . $id;
                continue;
            }
            $seenIds[$id] = true;
            
            $service = [
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
            $newServices[] = $service;
            saveVersion('service', $id, $service);
        }

        usort($newServices, function ($a, $b) {
            return intval($a['order'] ?? 999) <=> intval($b['order'] ?? 999);
        });

        if (saveJson($servicesPath, $newServices)) {
            $serviceRows = $newServices;
            appendAudit($auditPath, 'save', 'services', 'all', ['count' => count($newServices)]);
            $message = '✓ Услуги сохранены. Версионность включена.';
        } else {
            $error = '✗ Ошибка при сохранении услуг.';
        }
    }

    if ($action === 'save_doctors' && canEditContent($currentRole)) {
        $slugs = $_POST['doctor_slug'] ?? [];
        $names = $_POST['doctor_name'] ?? [];
        $titles = $_POST['doctor_title'] ?? [];
        $specialties = $_POST['doctor_specialty'] ?? [];
        $experiences = $_POST['doctor_experience'] ?? [];
        $bios = $_POST['doctor_bio'] ?? [];
        $images = $_POST['doctor_image'] ?? [];

        $newDoctors = [];
        $seenSlugs = [];
        for ($i = 0; $i < count($slugs); $i++) {
            $slug = sanitizeSlug((string)($slugs[$i] ?? ''));
            $name = trim((string)($names[$i] ?? ''));
            if ($slug === '' || $name === '') {
                continue;
            }

            if (isset($seenSlugs[$slug])) {
                $error = '✗ Дублирующийся slug врача: ' . $slug;
                continue;
            }
            $seenSlugs[$slug] = true;

            $doctor = [
                'id' => $i + 1,
                'slug' => $slug,
                'name' => $name,
                'title' => trim((string)($titles[$i] ?? '')),
                'specialty' => trim((string)($specialties[$i] ?? '')),
                'experience' => trim((string)($experiences[$i] ?? '')),
                'bio' => trim((string)($bios[$i] ?? '')),
                'image' => trim((string)($images[$i] ?? '')),
            ];

            if (!isset($doctorRows[$i]['specializations']) || !is_array($doctorRows[$i]['specializations'])) {
                $doctor['specializations'] = [];
            } else {
                $doctor['specializations'] = $doctorRows[$i]['specializations'];
            }
            if (!isset($doctorRows[$i]['education'])) {
                $doctor['education'] = '';
            } else {
                $doctor['education'] = (string)$doctorRows[$i]['education'];
            }
            if (!isset($doctorRows[$i]['focus']) || !is_array($doctorRows[$i]['focus'])) {
                $doctor['focus'] = [];
            } else {
                $doctor['focus'] = $doctorRows[$i]['focus'];
            }

            $newDoctors[] = $doctor;
            saveVersion('doctor', $slug, $doctor);
        }

        if (saveJson($doctorsPath, $newDoctors)) {
            $doctorRows = $newDoctors;
            appendAudit($auditPath, 'save', 'doctors', 'all', ['count' => count($newDoctors)]);
            $message = '✓ Врачи сохранены. Версионность включена.';
        } else {
            $error = '✗ Ошибка при сохранении врачей.';
        }
    }

    if ($action === 'save_faq' && canEditContent($currentRole)) {
        $questions = $_POST['faq_question'] ?? [];
        $answers = $_POST['faq_answer'] ?? [];

        $newFaq = [];
        for ($i = 0; $i < count($questions); $i++) {
            $q = trim((string)($questions[$i] ?? ''));
            $a = trim((string)($answers[$i] ?? ''));
            if ($q === '' || $a === '') continue;
            
            $faqItem = [
                'question' => $q,
                'answer' => $a,
            ];
            $newFaq[] = $faqItem;
            saveVersion('faq', md5($q), $faqItem);
        }

        if (saveJson($faqPath, $newFaq)) {
            $faqRows = $newFaq;
            appendAudit($auditPath, 'save', 'faq', 'all', ['count' => count($newFaq)]);
            $message = '✓ FAQ сохранены.';
        } else {
            $error = '✗ Ошибка при сохранении FAQ.';
        }
    }

    if ($action === 'save_reviews' && canEditContent($currentRole)) {
        $patients = $_POST['review_patient'] ?? [];
        $problems = $_POST['review_problem'] ?? [];
        $treatments = $_POST['review_treatment'] ?? [];
        $results = $_POST['review_result'] ?? [];
        $ratings = $_POST['review_rating'] ?? [];

        $newReviews = [];
        for ($i = 0; $i < count($patients); $i++) {
            $p = trim((string)($patients[$i] ?? ''));
            if ($p === '') continue;
            
            $review = [
                'patient' => $p,
                'problem' => trim((string)($problems[$i] ?? '')),
                'treatment' => trim((string)($treatments[$i] ?? '')),
                'result' => trim((string)($results[$i] ?? '')),
                'rating' => max(1, min(5, intval($ratings[$i] ?? 5))),
            ];
            $newReviews[] = $review;
            saveVersion('review', md5($p), $review);
        }

        if (saveJson($reviewsPath, $newReviews)) {
            $reviewRows = $newReviews;
            appendAudit($auditPath, 'save', 'reviews', 'all', ['count' => count($newReviews)]);
            $message = '✓ Отзывы сохранены.';
        } else {
            $error = '✗ Ошибка при сохранении отзывов.';
        }
    }

    if ($action === 'create_user' && canManageUsers($currentRole)) {
        $newUsername = trim((string)($_POST['new_username'] ?? ''));
        $newPassword = (string)($_POST['new_password'] ?? '');
        $newRole = normalizeRole((string)($_POST['new_role'] ?? 'viewer'));

        if ($newUsername === '' || $newPassword === '') {
            $error = '✗ Для создания пользователя нужны логин и пароль.';
        } else {
            $exists = false;
            foreach ($users as $user) {
                if (($user['username'] ?? '') === $newUsername) {
                    $exists = true;
                    break;
                }
            }

            if ($exists) {
                $error = '✗ Пользователь с таким логином уже существует.';
            } else {
                $users[] = [
                    'username' => $newUsername,
                    'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                    'role' => $newRole,
                    'active' => true,
                ];
                if (saveJson($usersPath, $users)) {
                    appendAudit($auditPath, 'create', 'user', $newUsername, ['role' => $newRole]);
                    $message = '✓ Пользователь создан.';
                } else {
                    $error = '✗ Ошибка при сохранении пользователей.';
                }
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
            appendAudit($auditPath, 'update', 'user', $username, ['role' => $newRole, 'active' => $isActive]);
            if ($authUser['username'] === $username) {
                $_SESSION['cabinet_user']['role'] = $newRole;
                $currentRole = $newRole;
            }
            $message = '✓ Пользователь обновлен.';
        } else {
            $error = '✗ Ошибка при обновлении пользователя.';
        }
    }
}

// Для удобства создания тестовых данных
$serviceRows = array_values($serviceRows);
$doctorRows = array_values($doctorRows);
$faqRows = array_values($faqRows);
$reviewRows = array_values($reviewRows);
$auditRows = array_reverse(array_values($auditRows));

// Добавить пустые поля для создания
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

if (count($doctorRows) < 8) {
    for ($i = count($doctorRows); $i < 8; $i++) {
        $doctorRows[] = [
            'slug' => '',
            'name' => '',
            'title' => '',
            'specialty' => '',
            'experience' => '',
            'bio' => '',
            'image' => '',
        ];
    }
}

$serviceCount = countFilledRows($serviceRows, 'id');
$doctorCount = countFilledRows($doctorRows, 'slug');
$faqCount = countFilledRows($faqRows, 'question');
$reviewCount = countFilledRows($reviewRows, 'patient');
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ панель | <?php echo CLINIC_NAME; ?></title>
    <meta name="robots" content="noindex,nofollow,noarchive,nosnippet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .sidebar { position: fixed; left: 0; top: 70px; width: 280px; height: calc(100vh - 70px); overflow-y: auto; background: #f8fcff; border-right: 1px solid #dce8f5; }
        .main { margin-left: 280px; margin-top: 70px; padding: 30px; }
        .nav-item { padding: 12px 20px; display: flex; align-items: center; gap: 10px; cursor: pointer; transition: all 0.2s; border-left: 3px solid transparent; }
        .nav-item:hover { background: #e8f1fa; }
        .nav-item.active { background: #d9e8f5; border-left-color: #2fbdef; color: #0077bd; font-weight: 600; }
        .stat-card { background: linear-gradient(135deg, #2fbdef 0%, #0077bd 100%); color: white; padding: 20px; border-radius: 12px; text-align: center; }
        .stat-number { font-size: 32px; font-weight: bold; }
        .stat-label { font-size: 12px; opacity: 0.9; }
        @media (max-width: 1024px) {
            .sidebar { width: 200px; }
            .main { margin-left: 200px; }
        }
        @media (max-width: 768px) {
            .sidebar { width: 100%; position: relative; top: 0; height: auto; }
            .main { margin-left: 0; margin-top: 0; }
        }
    </style>
</head>
<body class="bg-[#f4f9ff]">
    <!-- Фиксированный header -->
    <header class="fixed top-0 left-0 right-0 h-[70px] bg-gradient-to-r from-[#2fbdef] to-[#0077bd] text-white shadow-lg z-50 flex items-center justify-between px-6">
        <div class="flex items-center gap-3">
            <i class="fas fa-cog text-2xl"></i>
            <h1 class="font-bold text-xl"><?php echo CLINIC_NAME; ?></h1>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm">Пользователь: <strong><?php echo e($authUser['username']); ?></strong></span>
            <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-semibold"><?php echo strtoupper($currentRole); ?></span>
            <form method="post" class="inline">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition">Выход</button>
            </form>
        </div>
    </header>

    <div class="flex">
        <!-- Боковое меню -->
        <aside class="sidebar">
            <nav class="mt-0">
                <a href="/admin" class="nav-item <?php echo $tab === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line w-5"></i> Статистика
                </a>
                <?php if (canEditContent($currentRole)): ?>
                    <a href="?tab=services" class="nav-item <?php echo $tab === 'services' ? 'active' : ''; ?>">
                        <i class="fas fa-stethoscope w-5"></i> Услуги (<?php echo $serviceCount; ?>)
                    </a>
                    <a href="?tab=doctors" class="nav-item <?php echo $tab === 'doctors' ? 'active' : ''; ?>">
                        <i class="fas fa-user-md w-5"></i> Врачи (<?php echo $doctorCount; ?>)
                    </a>
                    <a href="?tab=faq" class="nav-item <?php echo $tab === 'faq' ? 'active' : ''; ?>">
                        <i class="fas fa-question-circle w-5"></i> FAQ (<?php echo $faqCount; ?>)
                    </a>
                    <a href="?tab=reviews" class="nav-item <?php echo $tab === 'reviews' ? 'active' : ''; ?>">
                        <i class="fas fa-star w-5"></i> Отзывы (<?php echo $reviewCount; ?>)
                    </a>
                <?php endif; ?>
                <?php if (canManageUsers($currentRole)): ?>
                    <a href="?tab=users" class="nav-item <?php echo $tab === 'users' ? 'active' : ''; ?>">
                        <i class="fas fa-users w-5"></i> Пользователи (<?php echo count($users); ?>)
                    </a>
                    <a href="?tab=audit" class="nav-item <?php echo $tab === 'audit' ? 'active' : ''; ?>">
                        <i class="fas fa-clipboard-list w-5"></i> Журнал изменений
                    </a>
                <?php endif; ?>
            </nav>
        </aside>

        <!-- Основной контент -->
        <main class="main flex-1">
            <!-- Сообщения об ошибках/успехе -->
            <?php if ($error !== ''): ?>
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo e($error); ?>
                </div>
            <?php endif; ?>
            <?php if ($message !== ''): ?>
                <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700">
                    <i class="fas fa-check-circle mr-2"></i><?php echo e($message); ?>
                </div>
            <?php endif; ?>

            <!-- Главная страница -->
            <?php if ($tab === 'dashboard'): ?>
                <div>
                    <h2 class="text-3xl font-bold text-[#0077bd] mb-8">Статистика клиники</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $serviceCount; ?></div>
                            <div class="stat-label">Услуг</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $doctorCount; ?></div>
                            <div class="stat-label">Врачей</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $faqCount; ?></div>
                            <div class="stat-label">Вопросов FAQ</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $reviewCount; ?></div>
                            <div class="stat-label">Отзывов</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo count($users); ?></div>
                            <div class="stat-label">Пользователей</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Услуги -->
            <?php if ($tab === 'services' && canEditContent($currentRole)): ?>
                <h2 class="text-2xl font-bold text-[#0077bd] mb-6">Управление услугами</h2>
                <form method="post" class="space-y-4 js-autosave" data-autosave-key="services-form-v1">
                    <input type="hidden" name="action" value="save_services">
                    <?php foreach ($serviceRows as $index => $row): ?>
                        <div class="bg-white rounded-lg p-6 border border-[#dce8f5] hover:border-[#2fbdef] transition">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-[#0077bd] mb-2">ID услуги</label>
                                    <input name="id[]" value="<?php echo e($row['id'] ?? ''); ?>" class="w-full px-3 py-2 border border-[#dce8f5] rounded-lg focus:border-[#2fbdef] outline-none" placeholder="service-slug">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-[#0077bd] mb-2">Название</label>
                                    <input name="name[]" value="<?php echo e($row['name'] ?? ''); ?>" class="w-full px-3 py-2 border border-[#dce8f5] rounded-lg focus:border-[#2fbdef] outline-none" placeholder="Название услуги">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-[#0077bd] mb-2">Цена</label>
                                    <input name="price[]" value="<?php echo e($row['price'] ?? ''); ?>" class="w-full px-3 py-2 border border-[#dce8f5] rounded-lg focus:border-[#2fbdef] outline-none" placeholder="от 5 000 ₽">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="block text-sm font-semibold text-[#0077bd] mb-2">Описание</label>
                                <textarea name="description[]" rows="2" class="w-full px-3 py-2 border border-[#dce8f5] rounded-lg focus:border-[#2fbdef] outline-none" placeholder="Краткое описание услуги"><?php echo e($row['description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="bg-gradient-to-r from-[#2fbdef] to-[#0077bd] text-white px-8 py-3 rounded-lg font-semibold hover:shadow-lg transition">
                        <i class="fas fa-save mr-2"></i>Сохранить услуги
                    </button>
                </form>
            <?php endif; ?>

            <!-- Врачи -->
            <?php if ($tab === 'doctors' && canEditContent($currentRole)): ?>
                <h2 class="text-2xl font-bold text-[#0077bd] mb-6">Управление врачами</h2>
                <form method="post" class="space-y-4 js-autosave" data-autosave-key="doctors-form-v1">
                    <input type="hidden" name="action" value="save_doctors">
                    <?php foreach ($doctorRows as $row): ?>
                        <div class="bg-white rounded-lg p-6 border border-[#dce8f5] hover:border-[#2fbdef] transition">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-[#0077bd] mb-2">Slug врача</label>
                                    <input name="doctor_slug[]" value="<?php echo e($row['slug'] ?? ''); ?>" class="w-full px-3 py-2 border border-[#dce8f5] rounded-lg focus:border-[#2fbdef] outline-none" placeholder="ivanov-ivan-ivanovich">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-[#0077bd] mb-2">Имя врача</label>
                                    <input name="doctor_name[]" value="<?php echo e($row['name'] ?? ''); ?>" class="w-full px-3 py-2 border border-[#dce8f5] rounded-lg focus:border-[#2fbdef] outline-none" placeholder="ФИО врача">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-[#0077bd] mb-2">Должность</label>
                                    <input name="doctor_title[]" value="<?php echo e($row['title'] ?? ''); ?>" class="w-full px-3 py-2 border border-[#dce8f5] rounded-lg focus:border-[#2fbdef] outline-none" placeholder="Врач-остеопат">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                                <div>
                                    <label class="block text-sm font-semibold text-[#0077bd] mb-2">Специализация</label>
                                    <input name="doctor_specialty[]" value="<?php echo e($row['specialty'] ?? ''); ?>" class="w-full px-3 py-2 border border-[#dce8f5] rounded-lg focus:border-[#2fbdef] outline-none" placeholder="Остеопатия, неврология">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-[#0077bd] mb-2">Опыт</label>
                                    <input name="doctor_experience[]" value="<?php echo e($row['experience'] ?? ''); ?>" class="w-full px-3 py-2 border border-[#dce8f5] rounded-lg focus:border-[#2fbdef] outline-none" placeholder="20 лет практики">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-[#0077bd] mb-2">Фото (имя файла)</label>
                                    <input name="doctor_image[]" value="<?php echo e($row['image'] ?? ''); ?>" class="w-full px-3 py-2 border border-[#dce8f5] rounded-lg focus:border-[#2fbdef] outline-none" placeholder="ivanov.jpg">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="block text-sm font-semibold text-[#0077bd] mb-2">Краткое био</label>
                                <textarea name="doctor_bio[]" rows="2" class="w-full px-3 py-2 border border-[#dce8f5] rounded-lg focus:border-[#2fbdef] outline-none" placeholder="Краткое описание врача"><?php echo e($row['bio'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="bg-gradient-to-r from-[#2fbdef] to-[#0077bd] text-white px-8 py-3 rounded-lg font-semibold hover:shadow-lg transition">
                        <i class="fas fa-save mr-2"></i>Сохранить врачей
                    </button>
                </form>
            <?php endif; ?>

            <!-- FAQ -->
            <?php if ($tab === 'faq' && canEditContent($currentRole)): ?>
                <h2 class="text-2xl font-bold text-[#0077bd] mb-6">Управление FAQ</h2>
                <form method="post" class="space-y-4">
                    <input type="hidden" name="action" value="save_faq">
                    <?php foreach ($faqRows as $row): ?>
                        <div class="bg-white rounded-lg p-6 border border-[#dce8f5]">
                            <label class="block text-sm font-semibold text-[#0077bd] mb-2">Вопрос</label>
                            <input name="faq_question[]" value="<?php echo e($row['question'] ?? ''); ?>" class="w-full px-3 py-2 border border-[#dce8f5] rounded-lg focus:border-[#2fbdef] outline-none mb-3">
                            <label class="block text-sm font-semibold text-[#0077bd] mb-2">Ответ</label>
                            <textarea name="faq_answer[]" rows="3" class="w-full px-3 py-2 border border-[#dce8f5] rounded-lg focus:border-[#2fbdef] outline-none"><?php echo e($row['answer'] ?? ''); ?></textarea>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="bg-gradient-to-r from-[#2fbdef] to-[#0077bd] text-white px-8 py-3 rounded-lg font-semibold hover:shadow-lg transition">
                        <i class="fas fa-save mr-2"></i>Сохранить FAQ
                    </button>
                </form>
            <?php endif; ?>

            <!-- Отзывы -->
            <?php if ($tab === 'reviews' && canEditContent($currentRole)): ?>
                <h2 class="text-2xl font-bold text-[#0077bd] mb-6">Управление отзывами</h2>
                <form method="post" class="space-y-4">
                    <input type="hidden" name="action" value="save_reviews">
                    <?php foreach ($reviewRows as $row): ?>
                        <div class="bg-white rounded-lg p-6 border border-[#dce8f5]">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-[#0077bd] mb-2">Пациент</label>
                                    <input name="review_patient[]" value="<?php echo e($row['patient'] ?? ''); ?>" class="w-full px-3 py-2 border border-[#dce8f5] rounded-lg focus:border-[#2fbdef] outline-none">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-[#0077bd] mb-2">Оценка (1-5)</label>
                                    <select name="review_rating[]" class="w-full px-3 py-2 border border-[#dce8f5] rounded-lg focus:border-[#2fbdef] outline-none">
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                            <option value="<?php echo $i; ?>" <?php echo ($row['rating'] ?? 5) == $i ? 'selected' : ''; ?>><?php echo $i; ?> звезд</option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="block text-sm font-semibold text-[#0077bd] mb-2">Результат</label>
                                <textarea name="review_result[]" rows="2" class="w-full px-3 py-2 border border-[#dce8f5] rounded-lg focus:border-[#2fbdef] outline-none"><?php echo e($row['result'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="bg-gradient-to-r from-[#2fbdef] to-[#0077bd] text-white px-8 py-3 rounded-lg font-semibold hover:shadow-lg transition">
                        <i class="fas fa-save mr-2"></i>Сохранить отзывы
                    </button>
                </form>
            <?php endif; ?>

            <!-- Пользователи -->
            <?php if ($tab === 'users' && canManageUsers($currentRole)): ?>
                <h2 class="text-2xl font-bold text-[#0077bd] mb-6">Управление пользователями</h2>
                
                <div class="bg-white rounded-lg p-6 border border-[#dce8f5] mb-6">
                    <h3 class="text-lg font-semibold text-[#0077bd] mb-4">Создать пользователя</h3>
                    <form method="post" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <input type="hidden" name="action" value="create_user">
                        <div>
                            <input name="new_username" class="w-full px-3 py-2 border border-[#dce8f5] rounded-lg focus:border-[#2fbdef] outline-none" placeholder="Логин" required>
                        </div>
                        <div>
                            <input type="password" name="new_password" class="w-full px-3 py-2 border border-[#dce8f5] rounded-lg focus:border-[#2fbdef] outline-none" placeholder="Пароль" required>
                        </div>
                        <div>
                            <select name="new_role" class="w-full px-3 py-2 border border-[#dce8f5] rounded-lg focus:border-[#2fbdef] outline-none">
                                <option value="viewer">viewer</option>
                                <option value="editor">editor</option>
                                <option value="admin">admin</option>
                            </select>
                        </div>
                        <div></div>
                        <button type="submit" class="bg-gradient-to-r from-[#2fbdef] to-[#0077bd] text-white px-4 py-2 rounded-lg font-semibold hover:shadow-lg transition">
                            <i class="fas fa-plus mr-2"></i>Создать
                        </button>
                    </form>
                </div>

                <div class="space-y-3">
                    <?php foreach ($users as $user): ?>
                        <form method="post" class="bg-white rounded-lg p-6 border border-[#dce8f5] grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                            <input type="hidden" name="action" value="update_user">
                            <input type="hidden" name="edit_username" value="<?php echo e($user['username'] ?? ''); ?>">
                            <div>
                                <label class="text-xs font-semibold text-[#0077bd]">Логин</label>
                                <input value="<?php echo e($user['username'] ?? ''); ?>" class="w-full px-3 py-2 border border-[#dce8f5] rounded-lg bg-gray-50" disabled>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-[#0077bd]">Роль</label>
                                <select name="edit_role" class="w-full px-3 py-2 border border-[#dce8f5] rounded-lg focus:border-[#2fbdef] outline-none">
                                    <option value="viewer" <?php echo (($user['role'] ?? '') === 'viewer') ? 'selected' : ''; ?>>viewer</option>
                                    <option value="editor" <?php echo (($user['role'] ?? '') === 'editor') ? 'selected' : ''; ?>>editor</option>
                                    <option value="admin" <?php echo (($user['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>admin</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-[#0077bd]">Новый пароль</label>
                                <input type="password" name="edit_password" class="w-full px-3 py-2 border border-[#dce8f5] rounded-lg focus:border-[#2fbdef] outline-none" placeholder="оставьте пустым">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-[#0077bd]">Статус</label>
                                <select name="edit_active" class="w-full px-3 py-2 border border-[#dce8f5] rounded-lg focus:border-[#2fbdef] outline-none">
                                    <option value="1" <?php echo !empty($user['active']) ? 'selected' : ''; ?>>active</option>
                                    <option value="0" <?php echo empty($user['active']) ? 'selected' : ''; ?>>disabled</option>
                                </select>
                            </div>
                            <button type="submit" class="bg-gradient-to-r from-[#2fbdef] to-[#0077bd] text-white px-4 py-2 rounded-lg font-semibold hover:shadow-lg transition">
                                Обновить
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Журнал изменений -->
            <?php if ($tab === 'audit' && canManageUsers($currentRole)): ?>
                <h2 class="text-2xl font-bold text-[#0077bd] mb-6">Журнал изменений</h2>
                <div class="bg-white rounded-lg border border-[#dce8f5] overflow-hidden">
                    <div class="grid grid-cols-12 gap-2 px-4 py-3 bg-[#f4f9ff] text-xs font-semibold text-[#2a5a94] uppercase tracking-wide">
                        <div class="col-span-3">Время</div>
                        <div class="col-span-2">Пользователь</div>
                        <div class="col-span-2">Действие</div>
                        <div class="col-span-2">Сущность</div>
                        <div class="col-span-3">ID</div>
                    </div>
                    <?php if (empty($auditRows)): ?>
                        <div class="px-4 py-6 text-sm text-[#355b89]">Журнал пока пуст.</div>
                    <?php else: ?>
                        <?php foreach (array_slice($auditRows, 0, 120) as $audit): ?>
                            <div class="grid grid-cols-12 gap-2 px-4 py-3 border-t border-[#eef4fb] text-sm">
                                <div class="col-span-3 text-[#355b89]"><?php echo e($audit['timestamp'] ?? ''); ?></div>
                                <div class="col-span-2 font-semibold text-[#173f73]"><?php echo e($audit['user'] ?? ''); ?></div>
                                <div class="col-span-2 text-[#173f73]"><?php echo e($audit['action'] ?? ''); ?></div>
                                <div class="col-span-2 text-[#355b89]"><?php echo e($audit['entity_type'] ?? ''); ?></div>
                                <div class="col-span-3 text-[#355b89]"><?php echo e($audit['entity_id'] ?? ''); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        (function () {
            var forms = document.querySelectorAll('.js-autosave');
            forms.forEach(function (form) {
                var key = form.getAttribute('data-autosave-key');
                if (!key) return;

                var saved = localStorage.getItem(key);
                if (saved) {
                    try {
                        var data = JSON.parse(saved);
                        Object.keys(data).forEach(function (name) {
                            var field = form.querySelector('[name="' + name.replace(/"/g, '\\"') + '"]');
                            if (field && !field.value) {
                                field.value = data[name];
                            }
                        });
                    } catch (e) {}
                }

                form.addEventListener('input', function () {
                    var payload = {};
                    var fields = form.querySelectorAll('input[name], textarea[name], select[name]');
                    fields.forEach(function (field) {
                        payload[field.name] = field.value;
                    });
                    localStorage.setItem(key, JSON.stringify(payload));
                });

                form.addEventListener('submit', function () {
                    localStorage.removeItem(key);
                });
            });
        })();
    </script>
</body>
</html>
