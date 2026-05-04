<?php
/**
 * PIN-защищенная заглушка сайта
 * Используется для скрытия сайта от публичного доступа
 */

// Проверяем сессию
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$correct_pin = '0336';
$error_message = '';
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Проверяем отправку формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_pin = isset($_POST['pin']) ? trim($_POST['pin']) : '';
    
    if ($entered_pin === $correct_pin) {
        $_SESSION['site_access_granted'] = true;
        $_SESSION['access_time'] = time();
        
        if ($is_ajax) {
            // Возвращаем JSON для AJAX запроса
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        } else {
            // Перенаправляем на главную страницу для обычного запроса
            $redirect_to = isset($_GET['redirect']) ? $_GET['redirect'] : '/';
            header('Location: ' . $redirect_to);
            exit;
        }
    } else {
        if ($is_ajax) {
            // Возвращаем ошибку JSON для AJAX запроса
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Неверный PIN-код']);
            exit;
        } else {
            $error_message = 'Неверный PIN-код. Попробуйте снова.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>БИОИНМЕД - Доступ к сайту</title>
    <!-- Favicon -->
    <link rel="icon" href="/public/images/brand/bioinmed-icon.png" type="image/png">
    <link rel="shortcut icon" href="/public/images/brand/bioinmed-icon.png" type="image/png">
    <link rel="apple-touch-icon" href="/public/images/brand/bioinmed-icon.png">
    <meta name="theme-color" content="#2fbdef">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root {
            --background: #f3f8fd;
            --foreground: #0f2749;
            --secondary: #355b89;
            --accent: #2fbdef;
        }
        
        * {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', 'SF Pro Display', 'SF Pro Text', sans-serif;
        }

        body {
            background: linear-gradient(to bottom, #f9fcff 0%, #f3f8fd 45%, #eef4fb 100%);
            color: var(--foreground);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .splash-container {
            width: 100%;
            max-width: 520px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 24px;
            animation: slideInDown 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-block {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 24px;
        }

        .logo-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border-radius: 28px;
            padding: 16px;
            border: none;
        }

        .logo-mark img {
            max-width: 240px;
            max-height: 160px;
            width: auto;
            height: auto;
            display: block;
        }

        .logo-text h1 {
            font-size: 36px;
            font-weight: 700;
            color: var(--foreground);
            letter-spacing: -0.5px;
            margin: 0;
        }

        .logo-text p {
            font-size: 14px;
            color: var(--accent);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            margin: 0;
        }

        .form-card {
            background: white;
            border-radius: 24px;
            padding: 40px 36px;
            width: 100%;
            box-shadow: 0 4px 32px rgba(15, 39, 73, 0.08);
            border: 1px solid rgba(47, 187, 239, 0.1);
        }

        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .form-header h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--foreground);
            margin: 0 0 10px 0;
        }

        .form-header p {
            color: var(--secondary);
            font-size: 15px;
            line-height: 1.6;
            margin: 0;
        }

        .form-group {
            margin: 0;
        }

        .error-message {
            color: #dc2626;
            font-size: 14px;
            padding: 12px 16px;
            background-color: #fee2e2;
            border-radius: 10px;
            margin-bottom: 24px;
            display: none;
            border-left: 4px solid #dc2626;
        }

        .error-message.show {
            display: block;
        }

        .success-message {
            color: #059669;
            font-size: 14px;
            padding: 12px 16px;
            background-color: #d1fae5;
            border-radius: 10px;
            margin-bottom: 24px;
            display: none;
            border-left: 4px solid #059669;
        }

        .success-message.show {
            display: block;
        }

        .pin-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            text-align: center;
        }

        .pin-input-wrapper {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-bottom: 32px;
        }

        .pin-input {
            width: 58px;
            height: 58px;
            font-size: 26px;
            font-weight: 700;
            text-align: center;
            border: 2px solid #d6e4f0;
            border-radius: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: var(--foreground);
            background: #f9fcff;
        }

        .pin-input:hover {
            border-color: #b9d7ef;
        }

        .pin-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(47, 187, 239, 0.15);
            background-color: white;
        }

        .pin-input::placeholder {
            color: #cbd5e0;
        }

        .submit-btn {
            width: 100%;
            padding: 14px 32px;
            font-size: 15px;
            font-weight: 600;
            color: white;
            background: linear-gradient(135deg, #2fbdef 0%, #0fa3c8 100%);
            border: none;
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            box-shadow: 0 4px 15px rgba(47, 187, 239, 0.3);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(47, 187, 239, 0.4);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .footer-note {
            text-align: center;
            font-size: 13px;
            color: var(--secondary);
            margin-top: 24px;
            padding: 0 20px;
        }

            @media (max-width: 640px) {
            body {
                padding: 30px 16px;
            }

            .splash-container {
                gap: 32px;
            }

            .logo-block {
                gap: 20px;
            }

            .logo-mark {
                padding: 12px;
                border-radius: 20px;
            }

            .logo-mark img {
                max-width: 190px;
                max-height: 130px;
            }

            .form-card {
                padding: 36px 28px;
                border-radius: 20px;
            }

            .form-header {
                margin-bottom: 32px;
            }

            .form-header h2 {
                font-size: 22px;
            }

            .pin-input-wrapper {
                gap: 10px;
                margin-bottom: 28px;
            }

            .pin-input {
                width: 52px;
                height: 52px;
                font-size: 23px;
                border-radius: 12px;
            }

            .submit-btn {
                padding: 12px 24px;
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .logo-mark {
                padding: 10px;
            }

            .logo-mark img {
                max-width: 160px;
                max-height: 110px;
            }

            .pin-input {
                width: 48px;
                height: 48px;
                font-size: 20px;
            }

            .pin-input-wrapper {
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="splash-container">
        <div class="logo-block">
            <div class="logo-mark">
                <img src="/public/images/brand/main-logotype.png" alt="БИОИНМЕД">
            </div>
        </div>

        <div class="form-card">
            <div class="form-header">
                <h2>Доступ ограничен</h2>
                <p>Введите PIN-код для входа</p>
            </div>

            <form id="pinForm" class="pin-form">
                <div class="error-message" id="errorMsg"></div>
                <div class="success-message" id="successMsg">PIN-код верный! Переход на сайт...</div>

                <div class="form-group">
                    <label class="pin-label">PIN-код</label>
                    <div class="pin-input-wrapper">
                        <input type="text" class="pin-input" maxlength="1" pattern="[0-9]" placeholder="•" autocomplete="off" aria-label="PIN цифра 1">
                        <input type="text" class="pin-input" maxlength="1" pattern="[0-9]" placeholder="•" autocomplete="off" aria-label="PIN цифра 2">
                        <input type="text" class="pin-input" maxlength="1" pattern="[0-9]" placeholder="•" autocomplete="off" aria-label="PIN цифра 3">
                        <input type="text" class="pin-input" maxlength="1" pattern="[0-9]" placeholder="•" autocomplete="off" aria-label="PIN цифра 4">
                    </div>
                    <input type="hidden" name="pin" id="pinInput" value="">
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">Войти</button>
                <p class="footer-note">Доступ к сайту ограничен PIN-кодом</p>
            </form>
        </div>
    </div>

    <script>
        const pinInputs = document.querySelectorAll('.pin-input');
        const pinInput = document.getElementById('pinInput');
        const form = document.getElementById('pinForm');
        const errorMsg = document.getElementById('errorMsg');
        const successMsg = document.getElementById('successMsg');
        const submitBtn = document.getElementById('submitBtn');

        pinInputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
                updatePinField();

                if (e.target.value.length === 1 && index < pinInputs.length - 1) {
                    pinInputs[index + 1].focus();
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
                    pinInputs[index - 1].focus();
                } else if (e.key === 'Enter') {
                    submitPin();
                }
            });

            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text');
                const digits = paste.replace(/[^0-9]/g, '').split('');

                pinInputs.forEach((input, idx) => {
                    input.value = digits[idx] || '';
                });

                updatePinField();
                pinInputs[Math.min(digits.length, pinInputs.length - 1)].focus();

                if (updatePinField() === 4) {
                    submitPin();
                }
            });
        });

        function updatePinField() {
            const pin = Array.from(pinInputs).map(input => input.value).join('');
            pinInput.value = pin;
            return pin.length;
        }

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            submitPin();
        });

        function submitPin() {
            const pin = pinInput.value;
            
            if (pin.length !== 4) {
                showError('Введите 4 цифры');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Проверка...';
            errorMsg.classList.remove('show');
            successMsg.classList.remove('show');

            // AJAX запрос для проверки PIN
            fetch(window.location.pathname, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'pin=' + encodeURIComponent(pin)
            })
            .then(response => response.json())
            .catch(err => {
                // Если ошибка парсинга JSON, значит это редирект (успех)
                return null;
            })
            .then(data => {
                if (data === null) {
                    // Успешно, произошел редирект
                    showSuccess('PIN-код верный!');
                    setTimeout(() => {
                        window.location.reload();
                    }, 800);
                } else if (data.success) {
                    showSuccess('PIN-код верный!');
                    setTimeout(() => {
                        const redirect = new URLSearchParams(window.location.search).get('redirect') || '/';
                        window.location.href = redirect;
                    }, 800);
                } else {
                    showError(data.error || 'Неверный PIN-код');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Войти';
                    clearPinInputs();
                }
            })
            .catch(err => {
                showError('Ошибка при проверке PIN-кода');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Войти';
            });
        }

        function showError(message) {
            errorMsg.textContent = message;
            errorMsg.classList.add('show');
            successMsg.classList.remove('show');
        }

        function showSuccess(message) {
            successMsg.textContent = message;
            successMsg.classList.add('show');
            errorMsg.classList.remove('show');
        }

        function clearPinInputs() {
            pinInputs.forEach(input => {
                input.value = '';
            });
            pinInput.value = '';
            pinInputs[0].focus();
        }

        window.addEventListener('load', () => {
            pinInputs[0].focus();
        });
    </script>
</body>
</html>
