<?php
// Улучшенная админ-панель с sidebar

require_once '../config.php';

session_start();

// Проверка авторизации
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header('Location: /admin/login');
    exit;
}

$user = $_SESSION['user'];
$is_admin = isset($user['role']) && $user['role'] === 'admin';
$is_editor = isset($user['role']) && in_array($user['role'], ['admin', 'editor']);

if (!$is_editor) {
    die('Access Denied');
}

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Обработка выхода
if ($action === 'logout') {
    session_destroy();
    header('Location: /');
    exit;
}

// Функция для загрузки JSON файла
function load_json($file) {
    if (!file_exists($file)) return [];
    $content = file_get_contents($file);
    return json_decode($content, true) ?? [];
}

// Функция для сохранения JSON файла
function save_json($file, $data) {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Загружаем данные
$data_dir = __DIR__ . '/../data';
$services = load_json($data_dir . '/services.json');
$faqs = load_json($data_dir . '/faqs.json');
$reviews = load_json($data_dir . '/reviews.json');
$users = load_json($data_dir . '/users.json');

// Обработка POST запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'edit_service':
            $id = $_POST['id'] ?? '';
            $key = array_search($id, array_column($services, 'id'));
            if ($key !== false) {
                $services[$key] = array_merge($services[$key], [
                    'name' => $_POST['name'] ?? '',
                    'price' => $_POST['price'] ?? '',
                    'description' => $_POST['description'] ?? '',
                ]);
                save_json($data_dir . '/services.json', $services);
            }
            break;
    }
    
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ панель | <?php echo CLINIC_NAME; ?></title>
    <meta name="robots" content="noindex,nofollow,noarchive,nosnippet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto; }
        .sidebar-nav { max-height: calc(100vh - 100px); overflow-y: auto; }
        .nav-item { transition: all 0.2s; }
        .nav-item:hover { background: #f0f7fc; }
        .nav-item.active { background: #2fbdef; color: white; border-left: 4px solid #2fbdef; }
        .content-area { height: calc(100vh - 70px); overflow-y: auto; }
    </style>
</head>
<body class="bg-[#f4f9ff]">
    <!-- Header -->
    <header class="sticky top-0 z-50 bg-gradient-to-r from-[#2fbdef] to-[#2fbdef] text-white border-b-4 border-[#004b7a] shadow-lg">
        <div class="flex items-center justify-between px-6 py-4">
            <div class="flex items-center gap-3">
                <i class="fas fa-cog text-2xl"></i>
                <div>
                    <h1 class="font-bold text-lg"><?php echo CLINIC_NAME; ?></h1>
                    <p class="text-xs opacity-90">Панель администратора</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-sm"><?php echo htmlspecialchars($user['email']); ?></span>
                <span class="px-3 py-1 bg-[rgba(255,255,255,0.2)] rounded text-xs font-bold">
                    <?php echo htmlspecialchars($user['role']); ?>
                </span>
                <a href="?action=logout" class="px-4 py-2 bg-red-500 hover:bg-red-600 rounded font-semibold text-sm transition">
                    <i class="fas fa-sign-out"></i> Выход
                </a>
            </div>
        </div>
    </header>

    <div class="flex h-screen" style="margin-top: -70px; padding-top: 70px;">
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-[#dce8f5] shadow-sm sidebar-nav">
            <nav class="space-y-1 p-4">
                <a href="?tab=dashboard" class="nav-item flex items-center gap-3 px-4 py-3 rounded <?php echo $tab === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-home w-5"></i>
                    <span>Панель управления</span>
                </a>
                <a href="?tab=services" class="nav-item flex items-center gap-3 px-4 py-3 rounded <?php echo $tab === 'services' ? 'active' : ''; ?>">
                    <i class="fas fa-stethoscope w-5"></i>
                    <span>Услуги (<?php echo count($services); ?>)</span>
                </a>
                <a href="?tab=faq" class="nav-item flex items-center gap-3 px-4 py-3 rounded <?php echo $tab === 'faq' ? 'active' : ''; ?>">
                    <i class="fas fa-question-circle w-5"></i>
                    <span>FAQ (<?php echo count($faqs); ?>)</span>
                </a>
                <a href="?tab=reviews" class="nav-item flex items-center gap-3 px-4 py-3 rounded <?php echo $tab === 'reviews' ? 'active' : ''; ?>">
                    <i class="fas fa-star w-5"></i>
                    <span>Отзывы (<?php echo count($reviews); ?>)</span>
                </a>
                <?php if ($is_admin): ?>
                <a href="?tab=users" class="nav-item flex items-center gap-3 px-4 py-3 rounded <?php echo $tab === 'users' ? 'active' : ''; ?>">
                    <i class="fas fa-users w-5"></i>
                    <span>Пользователи (<?php echo count($users); ?>)</span>
                </a>
                <?php endif; ?>
                <div class="border-t border-[#dce8f5] my-4 pt-4">
                    <a href="/" class="nav-item flex items-center gap-3 px-4 py-3 rounded text-[#2fbdef] hover:bg-[#f0f7fc]">
                        <i class="fas fa-arrow-left w-5"></i>
                        <span>На сайт</span>
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 content-area p-8">
            <?php
            switch ($tab) {
                case 'services':
                    echo '<div class="bg-white rounded-lg shadow p-6">';
                    echo '<h2 class="text-2xl font-bold text-[#2fbdef] mb-6">Управление услугами</h2>';
                    echo '<div class="space-y-4">';
                    foreach ($services as $service) {
                        echo '<div class="border border-[#dce8f5] rounded p-4">';
                        echo '<h3 class="font-bold text-lg text-[#0f2749]">' . htmlspecialchars($service['name'] ?? '') . '</h3>';
                        echo '<p class="text-[#2fbdef] font-bold mt-1">' . htmlspecialchars($service['price'] ?? 'Цена не указана') . '</p>';
                        echo '<p class="text-sm text-[#214a7f] mt-2">' . substr(htmlspecialchars($service['description'] ?? ''), 0, 100) . '...</p>';
                        echo '<a href="#" class="mt-3 inline-block px-3 py-1 bg-[#2fbdef] text-white rounded text-sm hover:bg-[#2fbdef]">Редактировать</a>';
                        echo '</div>';
                    }
                    echo '</div>';
                    echo '</div>';
                    break;
                    
                case 'faq':
                    echo '<div class="bg-white rounded-lg shadow p-6">';
                    echo '<h2 class="text-2xl font-bold text-[#2fbdef] mb-6">Управление FAQ</h2>';
                    echo '<div class="space-y-4">';
                    foreach ($faqs as $faq) {
                        echo '<div class="border border-[#dce8f5] rounded p-4">';
                        echo '<h3 class="font-bold text-[#0f2749]">' . htmlspecialchars($faq['question'] ?? '') . '</h3>';
                        echo '<p class="text-sm text-[#214a7f] mt-2">' . htmlspecialchars(substr($faq['answer'] ?? '', 0, 100)) . '...</p>';
                        echo '<a href="#" class="mt-3 inline-block px-3 py-1 bg-[#2fbdef] text-white rounded text-sm hover:bg-[#2fbdef]">Редактировать</a>';
                        echo '</div>';
                    }
                    echo '</div>';
                    echo '</div>';
                    break;

                default:
                    echo '<div class="bg-white rounded-lg shadow p-8">';
                    echo '<h2 class="text-3xl font-bold text-[#2fbdef] mb-6">Добро пожаловать!</h2>';
                    echo '<div class="grid grid-cols-4 gap-4 mb-8">';
                    echo '<div class="bg-gradient-to-br from-[#2fbdef] to-[#2fbdef] text-white rounded-lg p-6">';
                    echo '<i class="fas fa-stethoscope text-3xl mb-2"></i>';
                    echo '<p class="font-bold">' . count($services) . ' услуг</p>';
                    echo '<p class="text-sm opacity-90">В системе</p>';
                    echo '</div>';
                    echo '<div class="bg-gradient-to-br from-[#5fb5c0] to-[#2fbdef] text-white rounded-lg p-6">';
                    echo '<i class="fas fa-question-circle text-3xl mb-2"></i>';
                    echo '<p class="font-bold">' . count($faqs) . ' вопросов</p>';
                    echo '<p class="text-sm opacity-90">В FAQ</p>';
                    echo '</div>';
                    echo '<div class="bg-gradient-to-br from-[#5fb5c0] to-[#2fbdef] text-white rounded-lg p-6">';
                    echo '<i class="fas fa-star text-3xl mb-2"></i>';
                    echo '<p class="font-bold">' . count($reviews) . ' отзывов</p>';
                    echo '<p class="text-sm opacity-90">От пациентов</p>';
                    echo '</div>';
                    echo '<div class="bg-gradient-to-br from-[#2fbdef] to-[#5fb5c0] text-white rounded-lg p-6">';
                    echo '<i class="fas fa-users text-3xl mb-2"></i>';
                    echo '<p class="font-bold">' . count($users) . ' пользователей</p>';
                    echo '<p class="text-sm opacity-90">В системе</p>';
                    echo '</div>';
                    echo '</div>';
                    echo '<div class="bg-[#f9f0e6] border-l-4 border-[#ff9800] p-4 rounded">';
                    echo '<p class="text-sm text-[#e65100]"><strong>💡 Совет:</strong> Используйте меню слева для управления контентом сайта.</p>';
                    echo '</div>';
                    echo '</div>';
            }
            ?>
        </main>
    </div>
</body>
</html>
