<?php
/**
 * Красивая форма авторизации для личного кабинета
 * с логотипом и премиум дизайном
 */
session_start();
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/components/Components.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function normalizeRole($role) {
    $allowed = ['admin', 'editor', 'viewer'];
    return in_array($role, $allowed, true) ? $role : 'viewer';
}

$dataDir = dirname(__DIR__) . '/data';
$usersPath = $dataDir . '/users.json';

// Создаём пользователей при первом запуске
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
    @mkdir(dirname($usersPath), 0755, true);
    file_put_contents($usersPath, json_encode($bootstrapUsers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$users = [];
if (is_file($usersPath) && is_readable($usersPath)) {
    $raw = file_get_contents($usersPath);
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $users = $decoded;
        }
    }
}

$message = '';
$error = '';

// Rate-limiting: не более 5 попыток с одного IP за 10 минут
function checkLoginRateLimit($ip) {
    $ratePath = sys_get_temp_dir() . '/bioinmed_login_rl_' . md5($ip) . '.json';
    $now = time();
    $window = 600; // 10 минут
    $maxAttempts = 5;
    $attempts = [];
    if (is_file($ratePath)) {
        $decoded = json_decode((string)file_get_contents($ratePath), true);
        if (is_array($decoded)) {
            $attempts = array_filter($decoded, fn($t) => $t > $now - $window);
        }
    }
    if (count($attempts) >= $maxAttempts) {
        return false;
    }
    $attempts[] = $now;
    @file_put_contents($ratePath, json_encode(array_values($attempts)));
    return true;
}

// Обработка логина
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $remoteIp = (string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    if (!checkLoginRateLimit($remoteIp)) {
        $error = 'Слишком много попыток входа. Подождите 10 минут и повторите.';
    }

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $found = null;

    foreach ($users as $user) {
        if (($user['username'] ?? '') === $username && !empty($user['active'])) {
            $found = $user;
            break;
        }
    }

    if ($error === '' && $found && password_verify($password, (string)($found['password_hash'] ?? ''))) {
        $_SESSION['cabinet_user'] = [
            'username' => $found['username'],
            'role' => normalizeRole((string)($found['role'] ?? 'viewer')),
        ];

        $auditPath = dirname(__DIR__) . '/data/audit-log.json';
        $auditRows = [];
        if (is_file($auditPath) && is_readable($auditPath)) {
            $decodedAudit = json_decode((string)file_get_contents($auditPath), true);
            if (is_array($decodedAudit)) {
                $auditRows = $decodedAudit;
            }
        }
        $auditRows[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user' => $found['username'],
            'role' => normalizeRole((string)($found['role'] ?? 'viewer')),
            'action' => 'login',
            'entity_type' => 'session',
            'entity_id' => 'current',
            'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            'meta' => ['uri' => (string)($_SERVER['REQUEST_URI'] ?? '')],
        ];
        if (count($auditRows) > 1000) {
            $auditRows = array_slice($auditRows, -1000);
        }
        @mkdir(dirname($auditPath), 0755, true);
        file_put_contents($auditPath, json_encode($auditRows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        header('Location: /admin');
        exit;
    }

    if ($error === '') {
        $error = 'Неверный логин или пароль. Проверьте учетные данные и попробуйте снова.';
    }
}

// Если уже авторизован, перенаправляем в кабинет
if (isset($_SESSION['cabinet_user']) && is_array($_SESSION['cabinet_user'])) {
    header('Location: /admin');
    exit;
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в кабинет | БИОИНМЕД</title>
    <meta name="robots" content="noindex,nofollow,noarchive,nosnippet">
    <link rel="icon" type="image/png" href="<?php echo e(CLINIC_ICON_PATH); ?>">
    <link rel="apple-touch-icon" href="<?php echo e(CLINIC_ICON_PATH); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body {
            background: linear-gradient(to bottom, #f9fcff 0%, #f3f8fd 45%, #eef4fb 100%);
        }
        .input-focus {
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .input-focus:focus {
            border-color: #2fbdef;
            box-shadow: 0 0 0 3px rgba(47, 189, 239, 0.1);
        }
    </style>
</head>
<body class="flex min-h-screen items-center justify-center bg-[linear-gradient(to_bottom,#f9fcff_0%,#f3f8fd_45%,#eef4fb_100%)] px-4 py-8 text-[#0f2749] antialiased">
    <div class="w-full max-w-md">
        <!-- Логотип и заголовок -->
        <div class="mb-10 text-center">
            <a href="/" class="inline-flex items-center justify-center" aria-label="На главную">
                <img src="/public/images/brand/main-logotype.png" alt="<?php echo e(CLINIC_NAME); ?>" class="mx-auto h-12 w-auto mb-4" />
            </a>
            <h1 class="text-[1.2rem] font-bold text-[#0f2749]">Личный кабинет</h1>
            <p class="mt-1 text-[0.82rem] text-[#4a6f96]">Вход для администраторов</p>
        </div>

        <!-- Форма входа -->
        <form method="post" class="rounded-2xl border border-[#dce8f5] bg-white p-6 shadow-[0_10px_28px_rgba(10,43,80,0.07)] space-y-5 md:p-8">
            <input type="hidden" name="action" value="login">

            <!-- Сообщение об ошибке -->
            <?php if ($error !== ''): ?>
                <div class="rounded-xl border border-[#f5c6c6] bg-[#fff0f0] p-3.5 flex gap-3">
                    <svg class="h-5 w-5 shrink-0 text-[#d94f4f] mt-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                    <div>
                        <p class="text-[0.78rem] font-semibold text-[#a63c3c]">Ошибка входа</p>
                        <p class="text-[0.75rem] text-[#8b3a3a] mt-0.5"><?php echo e($error); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Поле логина -->
            <div>
                <label for="username" class="block text-[0.8rem] font-semibold text-[#0f2749] mb-1.5">
                    Логин
                </label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    required
                    autofocus
                    placeholder="Введите логин"
                    class="input-focus w-full rounded-lg border border-[#d6e4f2] bg-[#f8fbff] px-4 py-2.5 text-[0.9rem] text-[#173f73] outline-none placeholder:text-[#7fa3c6]"
                />
            </div>

            <!-- Поле пароля -->
            <div>
                <label for="password" class="block text-[0.8rem] font-semibold text-[#0f2749] mb-1.5">
                    Пароль
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    placeholder="Введите пароль"
                    class="input-focus w-full rounded-lg border border-[#d6e4f2] bg-[#f8fbff] px-4 py-2.5 text-[0.9rem] text-[#173f73] outline-none placeholder:text-[#7fa3c6]"
                />
            </div>

            <!-- Кнопка входа -->
            <button
                type="submit"
                class="w-full rounded-lg bg-[#2fbdef] px-5 py-2.5 text-[0.9rem] font-semibold text-white hover:bg-[#1fb3d8] transition-colors mt-6"
            >
                Войти в кабинет
            </button>

            <!-- Информация -->
            <div class="rounded-lg border border-[#dce8f5] bg-[#f8fcff] p-3">
                <p class="text-[0.75rem] text-[#2a5a94] leading-relaxed">
                    <svg class="inline h-3.5 w-3.5 mr-1.5 text-[#2fbdef]" fill="currentColor" viewBox="0 0 24 24"><path d="M12 1C6.48 1 2 5.48 2 11s4.48 10 10 10 10-4.48 10-10S17.52 1 12 1zm0 19c-4.98 0-9-4.02-9-9s4.02-9 9-9 9 4.02 9 9-4.02 9-9 9zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg>
                    Используйте учетные данные администратора
                </p>
            </div>

            <!-- Ссылка на главную -->
            <div class="pt-3 text-center border-t border-[#e6eef7]">
                <a href="<?php echo e(CLINIC_SITE_URL); ?>" class="text-[0.8rem] font-semibold text-[#2fbdef] hover:text-[#0f2749] transition">
                    ← Вернуться на сайт
                </a>
            </div>
        </form>

        <!-- Копирайт -->
        <p class="mt-8 text-center text-[0.7rem] text-[#4a6f96]">
            © 2026 БИОИНМЕД<br/>Все права защищены
        </p>
    </div>

</body>
</html>
