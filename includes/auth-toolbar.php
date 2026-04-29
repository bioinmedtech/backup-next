<?php
/**
 * Аутентифицированный редакторский toolbar (WordPress-style)
 */
session_start();

function render_auth_toolbar() {
    // Проверяем авторизацию
    if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
        return '';
    }

    $user = $_SESSION['user'];
    $is_admin = isset($user['role']) && in_array($user['role'], ['admin', 'editor']);
    
    if (!$is_admin) {
        return '';
    }

    $current_page = basename($_SERVER['PHP_SELF']);
    $edit_url = '';
    $page_name = 'Главная';

    // Определяем текущую страницу
    if ($current_page === 'index.php') {
        $page_name = 'Главная';
        $edit_url = '/internal/cabinet.php?tab=content';
    } elseif ($current_page === 'service.php') {
        $page_name = 'Услуга';
        $edit_url = '/internal/cabinet.php?tab=services';
    } elseif ($current_page === 'doctor.php') {
        $page_name = 'Профиль врача';
        $edit_url = '/internal/cabinet.php?tab=doctors';
    } elseif ($current_page === 'prices.php') {
        $page_name = 'Прайс-лист';
        $edit_url = '/internal/cabinet.php?tab=services';
    }

    $user_email = htmlspecialchars($user['email'] ?? 'admin@bioinmed.local');
    $user_role = htmlspecialchars($user['role'] ?? 'admin');

    return <<<HTML
    <div class="bioinmed-admin-toolbar" id="bioinmed-toolbar">
        <style>
            #bioinmed-toolbar {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                height: 50px;
                background: linear-gradient(90deg, #2fbdef 0%, #0077bd 100%);
                box-shadow: 0 4px 12px rgba(47, 189, 239, 0.2);
                z-index: 9999;
                display: flex;
                align-items: center;
                padding: 0 20px;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            }

            #bioinmed-toolbar * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            .toolbar-brand {
                color: white;
                font-weight: 700;
                font-size: 14px;
                letter-spacing: 0.5px;
                margin-right: auto;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .toolbar-brand svg {
                width: 24px;
                height: 24px;
            }

            .toolbar-actions {
                display: flex;
                align-items: center;
                gap: 15px;
                margin-right: 20px;
            }

            .toolbar-action-btn {
                background: rgba(255, 255, 255, 0.2);
                border: 1px solid rgba(255, 255, 255, 0.3);
                color: white;
                padding: 6px 12px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }

            .toolbar-action-btn:hover {
                background: rgba(255, 255, 255, 0.3);
                border-color: rgba(255, 255, 255, 0.5);
                color: white;
                text-decoration: none;
            }

            .toolbar-action-btn.primary {
                background: rgba(255, 255, 255, 0.25);
                border-color: rgba(255, 255, 255, 0.4);
            }

            .toolbar-user {
                display: flex;
                align-items: center;
                gap: 12px;
                padding-left: 15px;
                border-left: 1px solid rgba(255, 255, 255, 0.2);
                color: white;
                font-size: 12px;
            }

            .toolbar-user-info {
                text-align: right;
            }

            .toolbar-user-name {
                font-weight: 600;
                font-size: 13px;
            }

            .toolbar-user-role {
                font-size: 10px;
                opacity: 0.9;
            }

            .toolbar-avatar {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.2);
                border: 2px solid rgba(255, 255, 255, 0.3);
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
                font-size: 14px;
            }

            /* Сдвиг контента вниз */
            body.has-admin-toolbar {
                padding-top: 50px;
            }

            @media (max-width: 768px) {
                #bioinmed-toolbar {
                    padding: 0 10px;
                    height: 56px;
                }

                .toolbar-brand {
                    font-size: 12px;
                }

                .toolbar-actions {
                    gap: 8px;
                    margin-right: 10px;
                }

                .toolbar-action-btn {
                    padding: 5px 10px;
                    font-size: 11px;
                }

                .toolbar-user-info {
                    display: none;
                }

                .toolbar-avatar {
                    width: 28px;
                    height: 28px;
                    font-size: 12px;
                }
            }
        </style>

        <div class="toolbar-brand">
            <svg viewBox="0 0 24 24" fill="white" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
            </svg>
            БИОИНМЕД
        </div>

        <div class="toolbar-actions">
            <span style="color: white; font-size: 12px; opacity: 0.8;">Страница: <strong>$page_name</strong></span>
            <a href="$edit_url" class="toolbar-action-btn primary" target="_blank">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                Редактировать
            </a>
            <a href="/internal/cabinet.php" class="toolbar-action-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                Кабинет
            </a>
        </div>

        <div class="toolbar-user">
            <div class="toolbar-user-info">
                <div class="toolbar-user-name">$user_email</div>
                <div class="toolbar-user-role">$user_role</div>
            </div>
            <div class="toolbar-avatar">
                {$user['email'][0]}
            </div>
            <a href="/internal/cabinet.php?action=logout" class="toolbar-action-btn" style="margin-left: 8px;">Выход</a>
        </div>
    </div>

    <script>
        document.body.classList.add('has-admin-toolbar');
    </script>
    HTML;
}

// Рендерим toolbar
echo render_auth_toolbar();
?>
