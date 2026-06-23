<?php
// Базовый класс для всех компонентов.
class Component {
    protected $colors;
    protected $data = [];

    public function __construct($colors = []) {
        global $brand_colors;
        $this->colors = $colors ?: $brand_colors;
    }

    public function render() {
        return '';
    }

    protected function e($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    protected function phoneLink($phone) {
        return preg_replace('/[^\d+]/', '', (string)$phone);
    }

    protected function sectionTitle($eyebrow, $title, $subtitle = '') {
        $subtitle_html = $subtitle !== ''
            ? '<p class="mt-2.5 max-w-2xl text-[1rem] leading-relaxed text-[#0a293c]">' . $this->e($subtitle) . '</p>'
            : '';

        return <<<HTML
        <div class="mb-7">
            <p class="text-[0.8rem] font-semibold uppercase tracking-[0.24em] text-[#1977b2]">{$this->e($eyebrow)}</p>
            <h2 class="mt-1.5 text-[1.5rem] font-bold leading-tight text-[#0f2749] md:text-[1.8rem]">{$this->e($title)}</h2>
            {$subtitle_html}
        </div>
        HTML;
    }
}

class Header extends Component {
    public function render() {
        global $services;

        $phone_1 = $this->e(CLINIC_PHONE);
        $phone_1_link = $this->phoneLink(CLINIC_PHONE);
        $phone_2 = defined('CLINIC_PHONE_2') ? $this->e(CLINIC_PHONE_2) : '';
        $phone_2_link = defined('CLINIC_PHONE_2') ? $this->phoneLink(CLINIC_PHONE_2) : '';
        $booking_url = defined('ONLINE_BOOKING_URL') ? $this->e(ONLINE_BOOKING_URL) : '#contact';
        $map_url = 'https://yandex.com/maps/-/CPGGyEzo';
        $vk_url = defined('CLINIC_VK') ? $this->e(CLINIC_VK) : '#';
        $telegram_url = defined('CLINIC_TELEGRAM') ? $this->e(CLINIC_TELEGRAM) : '#';
        $max_url = 'https://max.ru/id9704215369_bot';
        $max_icon_src = $this->e(bioinmed_versioned_asset_path('/public/images/icons/max-logo.png'));
        $logo_src = $this->e(bioinmed_versioned_asset_path('/public/images/brand/main-logotype.png'));

        $request_uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
        $current_path = parse_url($request_uri, PHP_URL_PATH);
        if (!is_string($current_path) || $current_path === '') {
            $current_path = '/';
        }
        $current_path = rtrim($current_path, '/');
        if ($current_path === '') {
            $current_path = '/';
        }

        $is_home = ($current_path === '/' || $current_path === '/index.php');
        $is_about = ($current_path === '/about' || $current_path === '/about.php');
        $is_services = ($current_path === '/services' || strpos($current_path, '/services/') === 0 || $current_path === '/service.php');
        $is_doctors = ($current_path === '/doctors' || strpos($current_path, '/doctors/') === 0 || $current_path === '/doctor.php');
        $is_prices = ($current_path === '/prices' || $current_path === '/prices.php');
        $is_seasons = (strpos($current_path, '/seasons/') === 0 || $current_path === '/season.php');

        $desktop_link_class = function ($is_active) {
            if ($is_active) {
                return 'is-active text-[#1977b2] border-b-2 border-transparent';
            }
            return 'text-[#0a293c] border-b-2 border-transparent hover:text-[#1977b2]';
        };
        $mobile_link_attr = function ($is_active) {
            return $is_active ? ' class="is-active" aria-current="page"' : '';
        };

        $desktop_about_class = $desktop_link_class($is_about);
        $desktop_services_class = $desktop_link_class($is_services);
        $desktop_doctors_class = $desktop_link_class($is_doctors);
        $desktop_reviews_class = $desktop_link_class(false);
        $desktop_faq_class = $desktop_link_class(false);
        $desktop_prices_class = $desktop_link_class($is_prices);
        $desktop_contacts_class = $desktop_link_class(false);
        $desktop_seasons_class = $desktop_link_class($is_seasons);

        $desktop_about_aria = $is_about ? ' aria-current="page"' : '';
        $desktop_services_aria = $is_services ? ' aria-current="page"' : '';
        $desktop_doctors_aria = $is_doctors ? ' aria-current="page"' : '';
        $desktop_prices_aria = $is_prices ? ' aria-current="page"' : '';
        $desktop_seasons_aria = $is_seasons ? ' aria-current="page"' : '';

        $mobile_about_attr = $mobile_link_attr($is_about);
        $mobile_services_attr = $mobile_link_attr($is_services);
        $mobile_doctors_attr = $mobile_link_attr($is_doctors);
        $mobile_prices_attr = $mobile_link_attr($is_prices);
        $mobile_seasons_summary_attr = $is_seasons ? ' class="is-active"' : '';
        $mobile_seasons_details_open = $is_seasons ? ' open' : '';
        $mobile_services_summary_attr = $is_services ? ' class="is-active"' : '';
        $mobile_services_details_open = $is_services ? ' open' : '';

        $second_phone = $phone_2 !== ''
            ? '<a href="tel:' . $phone_2_link . '" class="mt-0.5 block whitespace-nowrap text-[0.81rem] font-medium leading-tight text-[#0a293c] hover:text-[#1977b2] md:text-[0.84rem]">' . $phone_2 . '</a>'
            : '';

        $category_labels = [
            'diagnostics' => 'Диагностика',
            'musculoskeletal' => 'Опорно-двигательный аппарат',
            'manual_therapy' => 'Остеопатия и мануальные методики',
            'therapy' => 'Терапевтические программы',
            'integrative' => 'Интегративное сопровождение',
            'chief_doctor' => 'Прием главного врача',
            'psychology' => 'Психология',
            'osteopathy' => 'Остеопатия',
            'physiotherapy' => 'Физиотерапия',
            'reflexotherapy' => 'Рефлексотерапия',
            'infusion_therapy' => 'Инфузионная терапия',
            'ozone_therapy' => 'Озонотерапия',
            'injection_therapy' => 'Инъекционная терапия',
            'taping' => 'Тейпирование и банки',
            'other' => 'Другие услуги',
        ];

        $category_icons = [
            'diagnostics' => 'fa-microscope',
            'musculoskeletal' => 'fa-bone',
            'manual_therapy' => 'fa-hand-holding-medical',
            'therapy' => 'fa-heart-pulse',
            'integrative' => 'fa-staff-snake',
            'chief_doctor' => 'fa-user-doctor',
            'psychology' => 'fa-brain',
            'osteopathy' => 'fa-hand-sparkles',
            'physiotherapy' => 'fa-wave-square',
            'reflexotherapy' => 'fa-bullseye',
            'infusion_therapy' => 'fa-droplet',
            'ozone_therapy' => 'fa-wind',
            'injection_therapy' => 'fa-syringe',
            'taping' => 'fa-bandage',
            'other' => 'fa-stethoscope',
        ];

        $menu_services = is_array($services) ? $services : [];
        usort($menu_services, function ($a, $b) {
            return intval($a['order'] ?? 999) <=> intval($b['order'] ?? 999);
        });

        $services_by_category = [];
        foreach ($menu_services as $service) {
            $id = trim((string)($service['id'] ?? ''));
            $name = trim((string)($service['name'] ?? ''));
            if ($id === '' || $name === '') {
                continue;
            }

            $category_key = strtolower(trim((string)($service['category'] ?? 'other')));
            if ($category_key === '') {
                $category_key = 'other';
            }

            if (!isset($services_by_category[$category_key])) {
                $services_by_category[$category_key] = [];
            }

            $services_by_category[$category_key][] = [
                'id' => $id,
                'name' => $name,
                'href' => '/services/' . $id,
            ];
        }

        // Marketing-first order for dropdown categories.
        $category_order = [
            'chief_doctor',
            'diagnostics',
            'physiotherapy',
            'reflexotherapy',
            'osteopathy',
            'manual_therapy',
            'psychology',
            'infusion_therapy',
            'ozone_therapy',
            'injection_therapy',
            'taping',
            'musculoskeletal',
            'therapy',
            'integrative',
            'other',
        ];
        $category_order_index = [];
        foreach ($category_order as $index => $order_key) {
            $category_order_index[$order_key] = $index;
        }
        uksort($services_by_category, function ($left, $right) use ($category_order_index) {
            $left_key = str_replace('-', '_', strtolower((string)$left));
            $right_key = str_replace('-', '_', strtolower((string)$right));
            $left_index = $category_order_index[$left_key] ?? PHP_INT_MAX;
            $right_index = $category_order_index[$right_key] ?? PHP_INT_MAX;
            if ($left_index === $right_index) {
                return strnatcasecmp((string)$left, (string)$right);
            }
            return $left_index <=> $right_index;
        });

        $desktop_services_dropdown = '';
        $mobile_services_dropdown = '';
        if (!empty($services_by_category)) {
            $desktop_level1_tabs = '';
            $desktop_level2_panels = '';
            $mobile_groups = '';
            $initial_category_title = 'Направления';
            $category_counter = 0;

            foreach ($services_by_category as $category_key => $category_items) {
                $fallback_title = ucfirst(str_replace(['_', '-'], ' ', $category_key));
                $category_lookup = str_replace('-', '_', $category_key);
                $category_title = $category_labels[$category_lookup] ?? ($category_labels[$category_key] ?? $fallback_title);
                $category_dom_id = preg_replace('/[^a-z0-9\-]+/', '-', strtolower(str_replace('_', '-', (string)$category_lookup)));
                if ($category_dom_id === '') {
                    $category_dom_id = 'category-' . $category_counter;
                }

                $mobile_items_html = '';
                foreach ($category_items as $item_index => $item) {
                    $service_name = $this->e($item['name']);
                    $service_href = $this->e($item['href']);
                    $mobile_items_html .= '<a href="' . $service_href . '" onclick="closeMobMenu()">' . $service_name . '</a>';
                }

                $mobile_groups .= '<details class="mob-nav-subgroup"><summary>' . $this->e($category_title) . '<i class="fa-solid fa-chevron-down" aria-hidden="true"></i></summary><div class="mob-subsubnav">' . $mobile_items_html . '</div></details>';
                $category_counter++;
            }

            // Десктоп: простая ссылка на услуги
            $desktop_services_dropdown = '<a href="/services" class="' . $desktop_services_class . '"' . $desktop_services_aria . '>Услуги</a>';
            // Мобильный: подменю с категориями
            $mobile_services_dropdown = '<details class="mob-nav-group"' . $mobile_services_details_open . '><summary' . $mobile_services_summary_attr . '><span>Услуги</span><i class="fa-solid fa-chevron-down" aria-hidden="true"></i></summary><div class="mob-subnav"><a href="/services" onclick="closeMobMenu()"' . $mobile_services_attr . '>Все услуги</a>' . $mobile_groups . '</div></details>';
        } else {
            $desktop_services_dropdown = '<a href="/services" class="' . $desktop_services_class . '"' . $desktop_services_aria . '>Услуги</a>';
            $mobile_services_dropdown = '<a href="/services" onclick="closeMobMenu()"' . $mobile_services_attr . '>Услуги</a>';
        }

        // Seasons link (current season by date)
        $seasons_data = [
            'spring' => ['name' => 'Весна', 'icon' => 'fa-seedling'],
            'summer' => ['name' => 'Лето',  'icon' => 'fa-sun'],
            'autumn' => ['name' => 'Осень', 'icon' => 'fa-leaf'],
            'winter' => ['name' => 'Зима',  'icon' => 'fa-snowflake'],
        ];
        $actual_season_slug = bioinmed_current_season_slug();
        if (!isset($seasons_data[$actual_season_slug])) {
            $actual_season_slug = 'spring';
        }
        $desktop_seasons_main_href = '/seasons/' . $actual_season_slug;
        $current_season_slug = '';
        if (strpos($current_path, '/seasons/') === 0) {
            $current_season_slug = (string)substr($current_path, strlen('/seasons/'));
            $current_season_slug = trim($current_season_slug, '/');
        } elseif ($current_path === '/season.php' && isset($_GET['slug'])) {
            $current_season_slug = trim((string)$_GET['slug']);
        }

        $current_season_name = $seasons_data[$actual_season_slug]['name'];
        $seasons_btn_class = $is_seasons
            ? 'is-active text-[#1977b2] border-b-2 border-[#1977b2]'
            : 'text-[#0a293c] border-b-2 border-transparent hover:text-[#1977b2]';
        $desktop_seasons_dropdown = '<a href="' . $desktop_seasons_main_href . '" class="' . $seasons_btn_class . ' inline-flex items-center gap-1 text-[0.86rem] font-semibold"' . $desktop_seasons_aria . '>'
            . 'Сезоны</a>';
        $mobile_seasons_dropdown = '<a href="' . $desktop_seasons_main_href . '" onclick="closeMobMenu()"' . $mobile_seasons_summary_attr . '>Сезоны</a>';

        return <<<HTML
        <style>
            html {
                font-size: clamp(17px, 0.5vw + 15px, 19px);
            }

            body {
                line-height: 1.72;
            }

            .max-w-6xl {
                max-width: 88rem;
            }

            .max-w-5xl {
                max-width: 84rem;
            }

            .max-w-4xl {
                max-width: 76rem;
            }

            * { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', 'SF Pro Display', 'SF Pro Text', sans-serif; }
        </style>
        <header id="site-header" class="z-50 border-b border-[#d7e5f1] bg-[#e4f1fa] lg:bg-[#e4f1fa]/98 lg:backdrop-blur-md">
            <!-- ─── MOBILE HEADER ─── (hidden on lg+ via CSS) -->
            <div id="mob-header-bar">
                <!-- Row 1: logo + phone + burger -->
                <div class="flex items-center justify-between px-4 py-2.5">
                    <a href="/" class="inline-flex items-center mr-3 shrink-0">
                        <img src="{$logo_src}" alt="БИОИНМЕД" class="h-16 w-auto max-w-none" loading="eager">
                    </a>
                    <div class="flex items-center gap-2">
                        <a href="tel:{$phone_1_link}" aria-label="Позвонить" class="flex h-10 w-10 items-center justify-center rounded-full border border-[#b9d7ef] bg-white text-[#1977b2]">
                            <i class="fa-solid fa-phone text-[0.86rem]" aria-hidden="true"></i>
                        </a>
                        <button id="mob-toggle" onclick="toggleMobMenu()" aria-label="Меню" aria-expanded="false" class="flex h-10 w-10 items-center justify-center rounded-full border border-[#c9dcee] bg-white text-[#1977b2]">
                            <i id="mob-icon" class="fa-solid fa-bars text-[0.9rem]" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <!-- Row 2: address — always visible, critical -->
                <div class="border-t border-[#e4eef7] bg-[#f0f7fd] px-4 py-2 text-[#0a293c]">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 leading-tight">
                            <p class="text-[0.88rem] font-semibold">{$this->e(CLINIC_ADDRESS)}</p>
                            <p class="mt-0.5 text-[0.8rem] font-medium text-[#2a5894]">{$this->e(CLINIC_METRO)}</p>
                            <a href="javascript:void(0)" class="jsClientix_openWidget mt-3 inline-flex h-10 items-center justify-center rounded-full bg-[#1977b2] px-4 text-[0.84rem] font-semibold text-white shadow-[0_8px_18px_rgba(25,119,178,0.18)] transition hover:bg-[#16658f]">
                                Онлайн запись
                            </a>
                        </div>
                        <a href="{$map_url}" target="_blank" rel="noreferrer noopener" class="shrink-0 inline-flex items-center gap-1 rounded-full border border-[#c7dbed] bg-white px-2.5 py-1 text-[0.74rem] font-medium text-[#1977b2] hover:text-[#16658f]">
                            <i class="fa-solid fa-location-dot text-[0.66rem] text-[#1977b2]" aria-hidden="true"></i>
                            На карте
                        </a>
                    </div>
                </div>
            </div>

            <!-- ─── DESKTOP HEADER ─── (hidden below lg) -->
            <div class="hidden lg:block">
                <div class="mx-auto max-w-6xl px-6 pt-2 md:px-10">
                    <div class="grid gap-2 pb-2.5 lg:grid-cols-[max-content_1.05fr_0.9fr_0.74fr_168px] lg:items-start">
                        <a href="/" class="inline-flex items-center mr-3 shrink-0">
                            <img src="{$logo_src}" alt="БИОИНМЕД" class="h-16 w-auto max-w-none" loading="eager">
                        </a>

                        <div class="pt-1 leading-tight text-[#0a293c]">
                            <p class="text-[0.92rem] font-medium md:text-[0.96rem]">{$this->e(CLINIC_ADDRESS)}</p>
                            <p class="mt-0.5 text-[0.88rem] font-medium text-[#24588d] md:text-[0.9rem]">{$this->e(CLINIC_METRO)}</p>
                            <div class="mt-1.5">
                                <a href="{$map_url}" target="_blank" rel="noreferrer noopener" class="inline-flex items-center gap-1 rounded-full border border-[#c7dbed] bg-white px-2.5 py-1 text-[0.76rem] font-medium text-[#1977b2] hover:border-[#a8cbe6] hover:text-[#16658f]">
                                    <i class="fa-solid fa-location-dot text-[0.66rem] text-[#1977b2]" aria-hidden="true"></i>
                                    На карте
                                </a>
                            </div>
                        </div>

                        <div class="pt-1 leading-tight text-[#0a293c]">
                            <p class="text-[0.92rem] font-medium">{$this->e(CLINIC_HOURS)}</p>
                            <p class="mt-0.5 text-[0.78rem] font-medium text-[#1977b2]">Приём по предварительной записи</p>
                        </div>

                        <div class="flex items-start gap-2.5 pt-1 text-[#0a293c]">
                            <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#b9d7ef] text-[#1977b2]">
                                <i class="fa-solid fa-phone-volume text-[0.76rem]" aria-hidden="true"></i>
                            </div>
                            <div class="pt-[1px]">
                                <a href="tel:{$phone_1_link}" class="block whitespace-nowrap text-[0.88rem] font-medium leading-tight text-[#0a293c] hover:text-[#1977b2] md:text-[0.92rem]">{$phone_1}</a>
                                {$second_phone}
                                <p class="mt-0.5 text-[0.76rem] font-medium text-[#1977b2]">Запись по телефону ежедневно</p>
                            </div>
                        </div>

                        <div class="pt-1 text-right">
                            <a href="javascript:void(0)" class="jsClientix_openWidget inline-flex h-11 w-auto min-w-[164px] items-center justify-center rounded-full bg-[#1977b2] px-4 text-[0.94rem] font-medium text-white shadow-[0_10px_24px_rgba(25,119,178,0.2)] transition hover:bg-[#16658f]">
                                Онлайн запись
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="desktop-menu-bar hidden lg:block">
            <div class="mx-auto max-w-6xl px-6 md:px-10">
                <div id="desktop-menu-row" class="desktop-menu-row flex items-center justify-between py-2.5">
                    <nav class="menu-strip flex items-center gap-6 overflow-x-auto whitespace-nowrap text-[0.92rem] font-medium text-[#0a293c] lg:overflow-visible">
                        <a href="/about" class="{$desktop_about_class}"{$desktop_about_aria}>О клинике</a>
                        {$desktop_seasons_dropdown}
                        {$desktop_services_dropdown}
                        <a href="/doctors" class="{$desktop_doctors_class}"{$desktop_doctors_aria}>Профессиональная команда</a>
                        <a href="/#reviews" class="{$desktop_reviews_class}">Отзывы</a>
                        <a href="/#faq" class="{$desktop_faq_class}">Вопросы</a>
                        <a href="/prices" class="{$desktop_prices_class}"{$desktop_prices_aria}>Цены</a>
                        <a href="/#contact" class="{$desktop_contacts_class}">Контакты</a>
                    </nav>
                    <div class="ml-1 flex shrink-0 items-center gap-3 -mr-0.5">
                        <a href="{$vk_url}" target="_blank" rel="noreferrer noopener" aria-label="VK" class="group inline-flex items-center justify-center text-[#2787f5] transition hover:text-[#1f6fd0]">
                            <i class="fa-brands fa-vk translate-x-[1px] text-[1.82rem] leading-none" aria-hidden="true"></i>
                        </a>
                        <a href="{$max_url}" target="_blank" rel="noreferrer noopener" aria-label="MAX" class="group inline-flex items-center justify-center transition hover:opacity-85">
                            <img src="{$max_icon_src}" alt="MAX" class="h-[1.72rem] w-auto" loading="lazy" decoding="async">
                        </a>
                        <a href="{$telegram_url}" target="_blank" rel="noreferrer noopener" aria-label="Telegram" class="group inline-flex items-center justify-center text-[#27a7e7] transition hover:text-[#1c8fca]">
                            <i class="fa-brands fa-telegram text-[1.82rem] leading-none" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Backdrop and drawer are OUTSIDE <header> to avoid backdrop-filter stacking context issues -->
        <div id="mob-backdrop" onclick="closeMobMenu()"></div>
        <div id="mob-menu">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #dce8f3;">
                <img src="{$logo_src}" alt="БИОИНМЕД" style="height:48px;width:auto;">
                <button onclick="closeMobMenu()" aria-label="Закрыть меню" style="display:flex;width:32px;height:32px;align-items:center;justify-content:center;border-radius:9999px;border:1px solid #dce8f3;background:transparent;cursor:pointer;color:#0a293c;">
                    <i class="fa-solid fa-xmark" style="font-size:0.9rem;" aria-hidden="true"></i>
                </button>
            </div>
            <div style="padding:12px 20px;background:#eef5fc;border-bottom:1px solid #dce8f3;">
                <p style="font-size:0.88rem;font-weight:600;color:#0a293c;margin:0;">{$this->e(CLINIC_ADDRESS)}</p>
                <p style="font-size:0.8rem;color:#2a5894;margin:4px 0 0;">{$this->e(CLINIC_METRO)}</p>
                <p style="font-size:0.78rem;color:#2d86ca;margin:2px 0 0;">{$this->e(CLINIC_HOURS)}</p>
            </div>
            <nav id="mob-nav">
                <a href="/about" onclick="closeMobMenu()"{$mobile_about_attr}>О клинике</a>
                {$mobile_seasons_dropdown}
                {$mobile_services_dropdown}
                <a href="/doctors" onclick="closeMobMenu()"{$mobile_doctors_attr}>Профессиональная команда</a>
                <a href="/#reviews" onclick="closeMobMenu()">Отзывы</a>
                <a href="/#faq" onclick="closeMobMenu()">Вопросы</a>
                <a href="/prices" onclick="closeMobMenu()"{$mobile_prices_attr}>Цены</a>
                <a href="/#contact" onclick="closeMobMenu()">Контакты</a>
            </nav>
            <div style="margin-top:auto;border-top:1px solid #dce8f3;padding:16px 20px;display:flex;flex-direction:column;gap:12px;">
                <a href="tel:{$phone_1_link}" style="display:flex;align-items:center;gap:10px;font-size:0.94rem;font-weight:600;color:#0a293c;text-decoration:none;">
                    <i class="fa-solid fa-phone-volume" style="color:#1977b2;" aria-hidden="true"></i>
                    {$phone_1}
                </a>
                <a href="javascript:void(0)" class="jsClientix_openWidget" style="display:flex;height:46px;align-items:center;justify-content:center;border-radius:9999px;background:#1977b2;font-size:0.94rem;font-weight:500;color:#fff;text-decoration:none;">
                    Онлайн запись
                </a>
                <div style="display:flex;gap:12px;">
                    <a href="{$vk_url}" target="_blank" rel="noreferrer noopener" aria-label="VK" style="display:flex;align-items:center;justify-content:center;color:#2787f5;text-decoration:none;">
                        <i class="fa-brands fa-vk" style="font-size:1.82rem;line-height:1;transform:translateX(1px);" aria-hidden="true"></i>
                    </a>
                    <a href="{$max_url}" target="_blank" rel="noreferrer noopener" aria-label="MAX" style="display:flex;align-items:center;justify-content:center;text-decoration:none;">
                        <img src="{$max_icon_src}" alt="MAX" style="height:1.72rem;width:auto;" loading="lazy" decoding="async">
                    </a>
                    <a href="{$telegram_url}" target="_blank" rel="noreferrer noopener" aria-label="Telegram" style="display:flex;align-items:center;justify-content:center;color:#27a7e7;text-decoration:none;">
                        <i class="fa-brands fa-telegram" style="font-size:1.82rem;line-height:1;" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
        <style>
            #mob-header-bar{display:block}
            @media(min-width:1024px){#mob-header-bar{display:none}}
            #mob-backdrop{display:none;position:fixed;inset:0;z-index:51;background:rgba(0,0,0,.35)}
            #mob-backdrop.open{display:block}
            #mob-menu{position:fixed;top:0;right:0;bottom:0;z-index:52;width:min(80vw,320px);display:flex;flex-direction:column;background:#e4f1fa;box-shadow:-4px 0 32px rgba(10,30,60,.18);transform:translateX(100%);transition:transform .28s cubic-bezier(.4,0,.2,1)}
            @media(min-width:1024px){#mob-menu{display:none!important}}
            #mob-menu.open{transform:translateX(0)}
            #mob-nav{flex:1;min-height:0;overflow-y:auto;display:flex;flex-direction:column;padding:4px 20px}
            #mob-nav a{display:block;padding:12px 0;font-size:.98rem;font-weight:500;color:#1b3f6e;text-decoration:none;border-bottom:1px solid #e8f0f8}
            #mob-nav a:last-child{border-bottom:none}
            #mob-nav a:hover{color:#1977b2}
            #mob-nav details{border-bottom:1px solid #e8f0f8}
            #mob-nav details>summary{list-style:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:8px;padding:12px 0;font-size:.98rem;font-weight:500;color:#1b3f6e}
            #mob-nav details>summary::-webkit-details-marker{display:none}
            #mob-nav details>summary i{font-size:.76rem;color:#6f95ba;transition:transform .2s ease}
            #mob-nav details[open]>summary i{transform:rotate(180deg)}
            .mob-subnav{margin:0 0 8px;border-left:2px solid #dce8f3;padding-left:10px}
            .mob-nav-subgroup{border-bottom:none!important}
            .mob-nav-subgroup>summary{padding:10px 0;font-size:.9rem!important;font-weight:600!important;color:#0a293c!important}
            .mob-subsubnav a{display:flex!important;align-items:baseline;justify-content:space-between;gap:8px;border-bottom:1px dashed #e3edf8!important;padding:9px 0!important;font-size:.86rem!important;font-weight:500!important;color:#1b3f6e!important;text-decoration:none}
            .mob-subsubnav a:last-child{border-bottom:none!important}
            .services-nav-item{position:static}
            .services-nav-item button{line-height:1}
            .desktop-menu-bar{position:sticky;top:0;z-index:80;border-bottom:1px solid #dbe8f3;background:#e4f1fa}
            .desktop-menu-row{position:relative;background:#e4f1fa}
            .menu-strip{scrollbar-width:none}
            .menu-strip::-webkit-scrollbar{display:none}
            .menu-strip a{display:inline-flex;align-items:center;padding-bottom:2px;transition:color .2s ease,border-color .2s ease}
            .menu-strip a.is-active{}
            #mob-nav a.is-active{color:#1977b2}
            #mob-nav details>summary.is-active{color:#1977b2}
            #mob-nav details>summary.is-active i{color:#1977b2}
            .mob-subnav a.is-active{color:#1977b2!important}
        </style>
        <script>
            function toggleMobMenu(){var m=document.getElementById('mob-menu');if(m.classList.contains('open')){closeMobMenu();}else{m.classList.add('open');document.getElementById('mob-backdrop').classList.add('open');document.getElementById('mob-icon').className='fa-solid fa-xmark';document.getElementById('mob-toggle').setAttribute('aria-expanded','true');document.body.style.overflow='hidden';}}
            function closeMobMenu(){document.getElementById('mob-menu').classList.remove('open');document.getElementById('mob-backdrop').classList.remove('open');document.getElementById('mob-icon').className='fa-solid fa-bars';document.getElementById('mob-toggle').setAttribute('aria-expanded','false');document.body.style.overflow='';}
            document.addEventListener('keydown',function(e){if(e.key==='Escape'){closeMobMenu();}});
            window.addEventListener('resize',function(){if(window.innerWidth>=1024)closeMobMenu();});
            function updateHeaderMetrics(){
                var h=document.getElementById('site-header');
                var menuBar=document.querySelector('.desktop-menu-bar');
                var headerHeight=0;
                if(h){headerHeight=h.offsetHeight;}
                if(menuBar){headerHeight=Math.max(headerHeight,Math.round(menuBar.getBoundingClientRect().bottom));}
                document.documentElement.style.setProperty('--header-height',headerHeight+'px');
            }
            (function syncHeaderMetrics(){updateHeaderMetrics();}());
            window.addEventListener('load',updateHeaderMetrics);
            window.addEventListener('resize',updateHeaderMetrics);
            (function initPhoneMask(){
                if(window.__phoneMaskReady){return;}
                window.__phoneMaskReady=true;

                function formatInternationalByCode(digits,codeLen){
                    if(digits.length===0){return '+';}
                    if(digits.length<=codeLen){return '+'+digits;}
                    var code=digits.slice(0,codeLen);
                    var national=digits.slice(codeLen);
                    var groups=national.match(/.{1,3}/g)||[];
                    return '+'+code+' '+groups.join(' ');
                }

                // Конфиг стран и их форматов. Легко расширяется новыми странами.
                var PHONE_CONFIGS={
                    '7':{
                        countryCode:'+7',
                        countryName:'Россия',
                        minDigits:11,
                        maxDigits:15,
                        format:function(digits){
                            // +7 (999) 999-99-99 (и поддержка более длинных номеров)
                            if(digits.length===0){return '+';}
                            if(digits.length<=3){return '+'+digits;}
                            if(digits.length<=6){return '+'+digits.slice(0,1)+' ('+digits.slice(1);}
                            if(digits.length<=9){return '+'+digits.slice(0,1)+' ('+digits.slice(1,4)+') '+digits.slice(4);}
                            if(digits.length===10){return '+'+digits.slice(0,1)+' ('+digits.slice(1,4)+') '+digits.slice(4,7)+'-'+digits.slice(7);}
                            var base='+'+digits.slice(0,1)+' ('+digits.slice(1,4)+') '+digits.slice(4,7)+'-'+digits.slice(7,9)+'-';
                            return base+digits.slice(9);
                        },
                        placeholder:'+7 (999) 999-99-99'
                    },
                    '49':{
                        countryCode:'+49',
                        countryName:'Германия',
                        minDigits:11,
                        maxDigits:13,
                        format:function(digits){return formatInternationalByCode(digits,2);},
                        placeholder:'+49 151 234 567 89'
                    },
                    '1':{
                        countryCode:'+1',
                        countryName:'США/Канада',
                        minDigits:11,
                        maxDigits:11,
                        format:function(digits){return formatInternationalByCode(digits,1);},
                        placeholder:'+1 555 123 4567'
                    },
                    '44':{
                        countryCode:'+44',
                        countryName:'Великобритания',
                        minDigits:12,
                        maxDigits:12,
                        format:function(digits){return formatInternationalByCode(digits,2);},
                        placeholder:'+44 770 012 3456'
                    },
                    '33':{
                        countryCode:'+33',
                        countryName:'Франция',
                        minDigits:11,
                        maxDigits:11,
                        format:function(digits){return formatInternationalByCode(digits,2);},
                        placeholder:'+33 612 345 678'
                    }
                };
                var COUNTRY_CODES_SORTED=Object.keys(PHONE_CONFIGS).sort(function(a,b){return b.length-a.length;});

                function getDigitsOnly(str){
                    return String(str||'').replace(/\D/g,'');
                }

                function detectCountry(digits){
                    var normalized=String(digits||'');
                    for(var i=0;i<COUNTRY_CODES_SORTED.length;i++){
                        var code=COUNTRY_CODES_SORTED[i];
                        if(normalized.indexOf(code)===0){
                            return PHONE_CONFIGS[code];
                        }
                    }
                    return PHONE_CONFIGS['7']; // Дефолт +7
                }

                function formatPhone(str){
                    var digits=getDigitsOnly(str||'');
                    if(!digits){return '';}
                    var config=detectCountry(digits);
                    // Не обрезаем здесь — даём человеку вводить любую длину
                    // Проверка длины будет только при отправке формы
                    return config.format(digits);
                }

                function setupPhoneInput(input){
                    if(!input || input.dataset.phoneSetup){return;}
                    input.dataset.phoneSetup='1';
                    
                    // Стандартный плейсхолдер для России
                    var config=PHONE_CONFIGS['7'];
                    var defaultPlaceholder=input.getAttribute('data-placeholder-default')||config.placeholder;
                    var activePlaceholder=input.getAttribute('data-placeholder-active')||config.placeholder;
                    input.placeholder=defaultPlaceholder;
                    input.type='tel';
                    input.inputMode='tel';
                    input.autocomplete='tel';

                    function syncPlaceholderByValue(rawValue){
                        var digits=getDigitsOnly(rawValue);
                        if(!digits){
                            input.placeholder=document.activeElement===input ? activePlaceholder : defaultPlaceholder;
                            return;
                        }
                        var currentConfig=detectCountry(digits);
                        input.placeholder=currentConfig.placeholder;
                    }

                    input.addEventListener('focus',function(){
                        if(this.value===''){
                            this.value='+7';
                            this.setSelectionRange(this.value.length,this.value.length);
                        }
                        syncPlaceholderByValue(this.value);
                    });

                    input.addEventListener('input',function(){
                        var currentValue=this.value;
                        var digits=getDigitsOnly(currentValue);
                        
                        if(currentValue===''){
                            this.value='';
                            syncPlaceholderByValue(this.value);
                            return;
                        }

                        if(currentValue==='+'){
                            syncPlaceholderByValue(this.value);
                            return;
                        }
                        
                        // Форматируем текущие цифры
                        this.value=formatPhone(digits);
                        syncPlaceholderByValue(this.value);
                    });

                    input.addEventListener('blur',function(){
                        var currentValue=this.value;
                        var digits=getDigitsOnly(currentValue);
                        
                        // Если осталось только +, очищаем поле
                        if(currentValue==='+'||currentValue===''){
                            this.value='';
                            syncPlaceholderByValue(this.value);
                            return;
                        }
                        
                        // Форматируем и финализируем
                        this.value=formatPhone(digits);
                        syncPlaceholderByValue(this.value);
                    });

                    if(input.form){
                        input.form.addEventListener('submit',function(e){
                            var currentValue=input.value;
                            var digits=getDigitsOnly(currentValue);
                            
                            // Если пусто или только +, не отправляем
                            if(digits.length===0){
                                input.setCustomValidity('Введите номер телефона');
                                e.preventDefault();
                                return false;
                            }
                            
                            var config=detectCountry(digits);
                            var minLen=config.minDigits;
                            var maxLen=config.maxDigits;
                            
                            // Допускаем диапазон от minDigits до maxDigits
                            if(digits.length<minLen){
                                var msg='Минимум '+minLen+' цифр ('+config.countryName+')';
                                input.setCustomValidity(msg);
                                e.preventDefault();
                                return false;
                            }
                            if(digits.length>maxLen){
                                var msg='Максимум '+maxLen+' цифр ('+config.countryName+')';
                                input.setCustomValidity(msg);
                                e.preventDefault();
                                return false;
                            }
                            input.value=digits;
                            input.setCustomValidity('');
                        });
                    }
                }

                function initAll(){
                    document.querySelectorAll('input[type="tel"]').forEach(setupPhoneInput);
                }

                initAll();
                document.addEventListener('DOMContentLoaded',initAll);
            })();
        </script>
        HTML;
    }
}

class HeroSection extends Component {
    public function render() {
        $booking_url = defined('ONLINE_BOOKING_URL') ? $this->e(ONLINE_BOOKING_URL) : '#contact';
        $habilect_logo = $this->e(bioinmed_versioned_asset_path('/public/images/habilect.png'));
        $seasons = require __DIR__ . '/../../config/seasons.php';
        $actual_slug = bioinmed_current_season_slug();
        if (!isset($seasons[$actual_slug])) {
            $actual_slug = 'spring';
        }
        $actual_season_name = $this->e((string)($seasons[$actual_slug]['name'] ?? 'Весна'));
        $actual_season_color = $this->e((string)($seasons[$actual_slug]['color'] ?? '#1977b2'));
        $actual_season_href = $this->e('/seasons/' . $actual_slug);

        $slides_html = '';
        $dots_html = '';
        $thumbs_html = '';
        $mobile_strip_html = '';
        $modal_thumbs_html = '';
        $hero_slides = [
            '/public/images/slider-v2/main-photo-bioinmed-v1.jpg',
        ];
        for ($i = 1; $i <= 20; $i++) {
            $hero_slides[] = '/public/images/slider-v2/slider-' . $i . '.jpg';
        }
        $slide_count = count($hero_slides);

        foreach ($hero_slides as $slide_index => $slide_path) {
            $is_first = ($slide_index === 0);
            $slide_src = bioinmed_versioned_asset_path($slide_path);
            $slide_alt = 'Интерьер клиники БИОИНМЕД ' . ($slide_index + 1);
            $loading = $is_first ? 'eager' : 'lazy';
            $active_class = $is_first ? ' is-active' : '';

            $slides_html .= '<button type="button" class="hero-clinic-open hero-clinic-slide h-full min-w-full' . $active_class . '" data-hero-image-src="' . $slide_src . '" data-hero-image-alt="' . $this->e($slide_alt) . '" aria-label="Открыть фото ' . ($slide_index + 1) . '">'
                . '<img src="' . $slide_src . '" alt="' . $this->e($slide_alt) . '" class="hero-clinic-slide-image h-full w-full object-cover object-top" loading="' . $loading . '" decoding="async">'
                . '</button>';

            $dots_html .= '<button type="button" class="hero-clinic-dot' . $active_class . '" data-slide-index="' . $slide_index . '" aria-label="Слайд ' . ($slide_index + 1) . '"></button>';

            $thumbs_html .= '<button type="button" class="hero-clinic-thumb' . $active_class . '" data-slide-index="' . $slide_index . '" aria-label="Миниатюра ' . ($slide_index + 1) . '">'
                . '<img src="' . $slide_src . '" alt="' . $this->e($slide_alt) . '" class="h-full w-full object-cover" loading="lazy" decoding="async">'
                . '</button>';

            $mobile_strip_html .= '<button type="button" class="hero-clinic-open h-[160px] w-[160px] min-w-[160px] snap-start overflow-hidden rounded-xl border border-[#d6e4f0] bg-[#eaf4fc] shadow-[0_10px_20px_rgba(10,43,80,0.12)] sm:h-[180px] sm:w-[180px] sm:min-w-[180px]" data-hero-image-src="' . $slide_src . '" data-hero-image-alt="' . $this->e($slide_alt) . '" aria-label="Открыть фото ' . ($slide_index + 1) . '">'
                . '<img src="' . $slide_src . '" alt="' . $this->e($slide_alt) . '" class="h-full w-full object-cover" loading="' . $loading . '" decoding="async">'
                . '</button>';

            $modal_thumbs_html .= '<button type="button" class="hero-modal-thumb' . $active_class . '" data-modal-index="' . $slide_index . '" aria-label="Фото ' . ($slide_index + 1) . '">'
                . '<img src="' . $slide_src . '" alt="' . $this->e($slide_alt) . '" class="h-full w-full object-cover" loading="lazy" decoding="async">'
                . '</button>';
        }

        $hero_callback_form = bioinmed_render_callback_form([
            'source_label' => 'Главная — hero',
            'submit_label' => 'Перезвоните мне',
        ]);

        return <<<HTML
        <section class="hero-section relative box-border overflow-hidden border-b border-[#dbe7f2] bg-[#e4f1fa] flex flex-col justify-center min-h-[calc(100svh-var(--header-height,140px))] pb-24 md:pb-0">
            <div class="pointer-events-none absolute -left-20 top-10 h-52 w-52 rounded-full bg-[#1977b224] blur-3xl md:-left-32 md:h-72 md:w-72"></div>
            <div class="pointer-events-none absolute right-0 top-0 h-64 w-64 rounded-full bg-[#0f27490d] blur-3xl md:h-96 md:w-96"></div>

            <div class="relative mx-auto w-full max-w-6xl px-6 py-5 md:px-10 md:py-7 lg:py-10">
                <div class="relative -top-2 flex w-full flex-col md:-top-3 lg:-top-5 lg:grid lg:grid-cols-2 lg:items-center lg:gap-8">
                    <div class="order-2 min-w-0 lg:order-1 lg:pr-2">
                        <a href="{$actual_season_href}" class="mb-3 inline-flex w-fit items-center gap-2 rounded-full px-4 py-2 text-[0.74rem] font-semibold uppercase tracking-[0.1em] text-white shadow-[0_10px_24px_rgba(10,43,80,0.10)] transition hover:-translate-y-0.5 hover:shadow-[0_14px_28px_rgba(10,43,80,0.14)]" style="background:{$actual_season_color};">
                            <span class="inline-block h-1.5 w-1.5 rounded-full" style="background:{$actual_season_color}"></span>
                            Сезон: {$actual_season_name}
                            <i class="fa-solid fa-arrow-right text-[0.66rem]" aria-hidden="true"></i>
                        </a>
                        <link rel="preconnect" href="https://fonts.googleapis.com">
                        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
                        <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@500;700&display=swap" rel="stylesheet">
                        <h1 class="mt-2 max-w-3xl leading-[1.1] text-[#0f2749]" style="font-family:'Caveat',cursive;font-size:clamp(1.95rem,4.25vw,2.85rem);font-weight:700;">
                            С нами выздоравливать легко!
                        </h1>
                        <p class="mt-3 inline-flex w-fit items-center bg-transparent px-0 py-0 leading-none text-[#1977b2]" style="font-family:'Caveat',cursive;font-size:clamp(1.2rem,2.6vw,1.45rem);font-weight:700;">
                            Ваш Биоинмед
                        </p>
                        <div class="mt-3 max-w-2xl space-y-2">
                            <a href="/services/hobilect-diagnostics" class="group flex items-start gap-3 border-l-4 border-[#1977b2]/35 pl-4 transition-colors hover:border-[#1977b2]">
                                <span class="pt-0.5 text-[1.06rem] font-medium leading-snug text-[#17446f] md:text-[1.14rem]">
                                    Экосистема HABILECT
                                </span>
                            </a>
                            <a href="/services/hobilect-diagnostics" class="group flex items-start gap-3 border-l-4 border-[#1977b2]/35 pl-4 transition-colors hover:border-[#1977b2]">
                                <span class="pt-0.5 text-[1.06rem] font-medium leading-snug text-[#17446f] md:text-[1.14rem]">
                                    Ваш эффективный маршрут здоровья
                                </span>
                            </a>
                            <a href="/services/hobilect-diagnostics" class="group flex items-start gap-3 border-l-4 border-[#1977b2]/35 pl-4 transition-colors hover:border-[#1977b2]">
                                <span class="pt-0.5 text-[1.06rem] font-medium leading-snug text-[#17446f] md:text-[1.14rem]">
                                    Где Вы особенный
                                </span>
                            </a>
                        </div>
                        <a href="/services/hobilect-diagnostics" class="mt-4 inline-flex items-center gap-2 rounded-2xl bg-white px-3 py-2 shadow-[0_12px_28px_rgba(15,39,73,0.1)] transition-transform duration-200 hover:-translate-y-0.5 hover:bg-[#f8fbff] hover:shadow-[0_18px_34px_rgba(15,39,73,0.14)] focus:outline-none focus:ring-2 focus:ring-[#1977b2]/30 md:gap-3 md:px-4 md:py-2.5">
                            <img src="{$habilect_logo}" alt="Хабилект" class="h-9 w-auto shrink-0 md:h-10" loading="eager" decoding="async">
                            <div class="min-w-0">
                                <p class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-[#1977b2] md:text-[0.82rem]">Хабилект</p>
                                <p class="hidden text-[0.8rem] font-medium leading-tight text-[#0a293c] sm:block md:text-[0.88rem]">Инновационные медицинские системы</p>
                            </div>
                        </a>

                        <div class="mt-5 w-full max-w-2xl rounded-[1.2rem] border border-[#d6e4f0] bg-white p-3.5 shadow-[0_18px_38px_rgba(10,43,80,0.09)] md:mt-6 md:p-4">
                            <div>
                                <h2 class="text-[1rem] font-bold text-[#0f2749] md:text-[1.08rem]">Записаться на приём</h2>
                                <p id="hero-form-note" class="mt-1 text-[0.82rem] leading-relaxed text-[#0a293c]">Перезвоним в течение 15 минут.</p>
                            </div>

                            <div class="mt-3">
                                {$hero_callback_form}
                            </div>
                        </div>
                    </div>

                    <div class="order-1 mb-4 min-w-0 overflow-hidden lg:order-2 lg:mb-0">
                        <div class="lg:hidden">
                            <div class="hero-clinic-mobile-strip flex snap-x snap-mandatory gap-2.5 overflow-x-auto pb-1">
                                {$mobile_strip_html}
                            </div>
                            <p class="mt-2 flex items-center gap-1.5 text-[0.78rem] font-medium text-[#0a293c]">
                                <i class="fa-solid fa-hand-pointer text-[0.8rem] text-[#1977b2]" aria-hidden="true"></i>
                                Листайте фото вправо
                            </p>
                        </div>

                        <div class="hero-clinic-slider relative hidden overflow-hidden rounded-[1.25rem] border border-[#d6e4f0] bg-[#eaf4fc] shadow-[0_18px_38px_rgba(10,43,80,0.1)] sm:h-[340px] md:h-[420px] lg:block lg:h-[520px] xl:h-[580px] lg:w-full" data-slide-count="{$slide_count}">
                            <div class="hero-clinic-slider-track flex h-full transition-transform duration-500 ease-out">
                                {$slides_html}
                            </div>
                            <div class="pointer-events-none absolute inset-0 bg-gradient-to-tr from-[#0f27490f] via-transparent to-[#1977b214]"></div>
                            <button type="button" class="hero-clinic-prev absolute left-3 top-1/2 z-10 inline-flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-white/80 bg-white/90 text-[#0a293c] shadow-[0_8px_20px_rgba(7,35,68,0.18)] transition hover:bg-white" aria-label="Предыдущее фото">
                                <i class="fa-solid fa-chevron-left text-[0.92rem]" aria-hidden="true"></i>
                            </button>
                            <button type="button" class="hero-clinic-next absolute right-3 top-1/2 z-10 inline-flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-white/80 bg-white/90 text-[#0a293c] shadow-[0_8px_20px_rgba(7,35,68,0.18)] transition hover:bg-white" aria-label="Следующее фото">
                                <i class="fa-solid fa-chevron-right text-[0.92rem]" aria-hidden="true"></i>
                            </button>
                            <div class="absolute bottom-3 left-1/2 z-10 flex -translate-x-1/2 items-center gap-1.5 rounded-full bg-[#0f2749]/25 px-2.5 py-1.5 backdrop-blur-sm">
                                {$dots_html}
                            </div>
                        </div>
                        <div class="mt-3 hidden gap-2 overflow-x-auto pb-1 lg:flex">
                            {$thumbs_html}
                        </div>
                    </div>
                </div>
            </div>

        </section>

        <div id="hero-image-modal" class="fixed inset-0 z-[110] hidden bg-[rgba(7,21,40,0.84)] px-4 py-6">
            <button type="button" id="hero-image-modal-close" class="absolute right-5 top-5 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20" aria-label="Закрыть">
                <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
            </button>
            <button type="button" id="hero-image-modal-prev" class="absolute left-4 top-1/2 inline-flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20" aria-label="Предыдущее фото">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
            </button>
            <button type="button" id="hero-image-modal-next" class="absolute right-4 top-1/2 inline-flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20" aria-label="Следующее фото">
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </button>
            <div class="mx-auto flex h-full max-w-6xl flex-col items-center justify-center gap-4">
                <img id="hero-image-modal-image" src="" alt="Фото клиники" class="max-h-[72vh] max-w-full rounded-3xl border border-white/15 bg-white/5 object-contain shadow-[0_18px_48px_rgba(0,0,0,0.35)]">
                <div id="hero-image-modal-thumbs" class="flex w-full max-w-3xl gap-2 overflow-x-auto pb-1">
                    {$modal_thumbs_html}
                </div>
            </div>
        </div>

        <script>
            (function initHeroClinicSlider() {
                var sliders = document.querySelectorAll('.hero-clinic-slider');
                var desktopThumbs = document.querySelectorAll('.hero-clinic-thumb');

                sliders.forEach(function(slider) {
                    var track = slider.querySelector('.hero-clinic-slider-track');
                    var slides = slider.querySelectorAll('.hero-clinic-slide');
                    var prev = slider.querySelector('.hero-clinic-prev');
                    var next = slider.querySelector('.hero-clinic-next');
                    var dots = slider.querySelectorAll('.hero-clinic-dot');
                    if (!track || !slides.length || !prev || !next) return;

                    var current = 0;
                    var lastIndex = slides.length - 1;

                    function render() {
                        track.style.transform = 'translateX(-' + (current * 100) + '%)';
                        slides.forEach(function(slide, index) {
                            slide.classList.toggle('is-active', index === current);
                        });
                        dots.forEach(function(dot, index) {
                            dot.classList.toggle('is-active', index === current);
                        });
                        desktopThumbs.forEach(function(thumb, index) {
                            thumb.classList.toggle('is-active', index === current);
                        });
                    }

                    prev.addEventListener('click', function() {
                        current = current <= 0 ? lastIndex : current - 1;
                        render();
                    });

                    next.addEventListener('click', function() {
                        current = current >= lastIndex ? 0 : current + 1;
                        render();
                    });

                    dots.forEach(function(dot) {
                        dot.addEventListener('click', function() {
                            var index = parseInt(dot.getAttribute('data-slide-index') || '0', 10);
                            if (!isNaN(index)) {
                                current = Math.max(0, Math.min(lastIndex, index));
                                render();
                            }
                        });
                    });

                    desktopThumbs.forEach(function(thumb) {
                        thumb.addEventListener('click', function() {
                            var index = parseInt(thumb.getAttribute('data-slide-index') || '0', 10);
                            if (!isNaN(index)) {
                                current = Math.max(0, Math.min(lastIndex, index));
                                render();
                            }
                        });
                    });

                    render();
                });

                var modal = document.getElementById('hero-image-modal');
                var modalImage = document.getElementById('hero-image-modal-image');
                var modalClose = document.getElementById('hero-image-modal-close');
                var modalPrev = document.getElementById('hero-image-modal-prev');
                var modalNext = document.getElementById('hero-image-modal-next');
                var modalThumbs = Array.from(document.querySelectorAll('.hero-modal-thumb'));
                var openers = Array.from(document.querySelectorAll('.hero-clinic-open'));
                if (!modal || !modalImage || !openers.length) return;

                var seen = {};
                var gallery = [];
                openers.forEach(function(opener) {
                    var src = opener.getAttribute('data-hero-image-src') || '';
                    var alt = opener.getAttribute('data-hero-image-alt') || 'Фото клиники';
                    if (!src || seen[src]) return;
                    seen[src] = true;
                    gallery.push({ src: src, alt: alt });
                });
                if (!gallery.length) return;

                var currentIndex = 0;

                function renderModalImage() {
                    var item = gallery[currentIndex];
                    if (!item) return;
                    modalImage.src = item.src;
                    modalImage.alt = item.alt;
                    modalThumbs.forEach(function(thumb, index) {
                        thumb.classList.toggle('is-active', index === currentIndex);
                    });
                }

                function openModal(index) {
                    currentIndex = Math.max(0, Math.min(gallery.length - 1, index));
                    renderModalImage();
                    modal.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');
                }

                function closeModal() {
                    modal.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }

                function prevModal() {
                    currentIndex = currentIndex <= 0 ? gallery.length - 1 : currentIndex - 1;
                    renderModalImage();
                }

                function nextModal() {
                    currentIndex = currentIndex >= gallery.length - 1 ? 0 : currentIndex + 1;
                    renderModalImage();
                }

                openers.forEach(function(opener) {
                    opener.addEventListener('click', function() {
                        var src = opener.getAttribute('data-hero-image-src') || '';
                        var matchIndex = gallery.findIndex(function(item) { return item.src === src; });
                        openModal(matchIndex >= 0 ? matchIndex : 0);
                    });
                });

                modalThumbs.forEach(function(thumb) {
                    thumb.addEventListener('click', function() {
                        var index = parseInt(thumb.getAttribute('data-modal-index') || '0', 10);
                        if (!isNaN(index)) {
                            currentIndex = Math.max(0, Math.min(gallery.length - 1, index));
                            renderModalImage();
                        }
                    });
                });

                modalClose && modalClose.addEventListener('click', closeModal);
                modalPrev && modalPrev.addEventListener('click', prevModal);
                modalNext && modalNext.addEventListener('click', nextModal);

                modal.addEventListener('click', function(event) {
                    if (event.target === modal) closeModal();
                });

                document.addEventListener('keydown', function(event) {
                    if (modal.classList.contains('hidden')) return;
                    if (event.key === 'Escape') closeModal();
                    if (event.key === 'ArrowLeft') prevModal();
                    if (event.key === 'ArrowRight') nextModal();
                });
            })();
        </script>

        <style>
            .hero-clinic-open {
                border: 0;
                padding: 0;
                background: transparent;
                cursor: zoom-in;
            }

            .hero-clinic-slide {
                overflow: hidden;
            }

            .hero-clinic-slide-image {
                object-position: center top;
                transform: scale(1.02);
                transition: transform 0.6s ease-out;
                will-change: transform;
            }

            .hero-clinic-slide.is-active .hero-clinic-slide-image {
                animation: heroLivePhotoZoom 6s ease-out forwards;
            }

            @keyframes heroLivePhotoZoom {
                from {
                    transform: scale(1.03);
                }
                to {
                    transform: scale(1);
                }
            }

            @media (prefers-reduced-motion: reduce) {
                .hero-clinic-slide-image {
                    transform: none;
                    transition: none;
                }

                .hero-clinic-slide.is-active .hero-clinic-slide-image {
                    animation: none;
                    transform: none;
                }
            }

            .hero-clinic-thumb,
            .hero-modal-thumb {
                border: 2px solid transparent;
                border-radius: 0.7rem;
                width: 62px;
                height: 62px;
                padding: 0;
                overflow: hidden;
                flex: 0 0 auto;
                opacity: 0.82;
                transition: transform 0.2s ease, opacity 0.2s ease, border-color 0.2s ease;
            }

            .hero-clinic-thumb:hover,
            .hero-modal-thumb:hover {
                opacity: 1;
                transform: translateY(-1px);
            }

            .hero-clinic-thumb.is-active,
            .hero-modal-thumb.is-active {
                opacity: 1;
                border-color: #1977b2;
            }

            .hero-clinic-mobile-strip,
            #hero-image-modal-thumbs {
                scrollbar-width: thin;
                scrollbar-color: rgba(42, 90, 148, 0.45) transparent;
            }

            .hero-clinic-mobile-strip::-webkit-scrollbar,
            #hero-image-modal-thumbs::-webkit-scrollbar {
                height: 6px;
            }

            .hero-clinic-mobile-strip::-webkit-scrollbar-thumb {
                background: rgba(42, 90, 148, 0.35);
                border-radius: 9999px;
            }

            #hero-image-modal-thumbs::-webkit-scrollbar-thumb {
                background: rgba(255, 255, 255, 0.45);
                border-radius: 9999px;
            }

            .hero-clinic-dot {
                width: 7px;
                height: 7px;
                border-radius: 9999px;
                background: rgba(255, 255, 255, 0.55);
                border: 0;
                padding: 0;
                transition: all 0.2s ease;
            }

            .hero-clinic-dot.is-active {
                width: 16px;
                background: #ffffff;
            }
        </style>
        HTML;
    }
}

class StatsBlock extends Component {
    public function render() {
        $experience = defined('CLINIC_EXPERIENCE_YEARS') ? $this->e(CLINIC_EXPERIENCE_YEARS) : '10+';
        $experience_desc = defined('CLINIC_EXPERIENCE_DESC') ? $this->e(CLINIC_EXPERIENCE_DESC) : 'ЛЕТ ОПЫТА';
        $rating = defined('CLINIC_RATING') ? $this->e(CLINIC_RATING) : '4.8';
        $rating_desc = defined('CLINIC_RATING_DESC') ? $this->e(CLINIC_RATING_DESC) : 'СРЕДНЯЯ ОЦЕНКА';
        $patients = defined('CLINIC_PATIENTS_YEARLY') ? $this->e(CLINIC_PATIENTS_YEARLY) : '1000+';
        $patients_desc = defined('CLINIC_PATIENTS_DESC') ? $this->e(CLINIC_PATIENTS_DESC) : 'ПАЦИЕНТОВ';
        $license_text = defined('CLINIC_LICENSE_TEXT') ? $this->e(CLINIC_LICENSE_TEXT) : 'Лицензия';
        $license_desc = defined('CLINIC_LICENSE_DESC') ? $this->e(CLINIC_LICENSE_DESC) : 'ЛИЦЕНЗИЯ И АККРЕДИТАЦИЯ';

        return <<<HTML
        <section class="fade-in border-b border-[#e6eef7] bg-[#1977b2] py-4 md:py-6">
            <div class="w-full">
                <ul class="grid w-full grid-cols-1 gap-0 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Stat 1: Experience -->
                    <li class="flex h-full w-full items-center gap-4 border-b border-white/15 px-6 py-5 text-white last:border-b-0 sm:border-r sm:border-b-0 sm:last:border-r-0 md:px-8 md:py-6">
                        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white/15 text-white" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 0 2-2h2a2 2 0 0 0 2 2m-6 9 2 2 4-4" />
                            </svg>
                        </span>
                        <div>
                            <div class="text-[2rem] font-bold leading-none text-white [font-variant-numeric:tabular-nums] md:text-[2.25rem]">{$experience}</div>
                            <p class="mt-1.5 text-[0.8rem] font-semibold uppercase leading-tight tracking-[0.08em] text-white/92 md:text-[0.88rem]">{$experience_desc}</p>
                        </div>
                    </li>
                    <!-- Stat 2: Rehabilitation methods -->
                    <li class="flex h-full w-full items-center gap-4 border-b border-white/15 px-6 py-5 text-white last:border-b-0 sm:border-r sm:border-b-0 sm:last:border-r-0 md:px-8 md:py-6">
                        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white/15 text-white" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 1-6.23-.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21a48.309 48.309 0 0 1-8.135-.687c-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
                            </svg>
                        </span>
                        <div>
                            <div class="text-[2rem] font-bold leading-none text-white [font-variant-numeric:tabular-nums] md:text-[2.25rem]">{$patients}</div>
                            <p class="mt-1.5 text-[0.8rem] font-semibold uppercase leading-tight tracking-[0.08em] text-white/92 md:text-[0.88rem]">{$patients_desc}</p>
                        </div>
                    </li>
                    <!-- Stat 3: Rating -->
                    <li class="flex h-full w-full items-center gap-4 border-b border-white/15 px-6 py-5 text-white last:border-b-0 sm:border-r sm:border-b-0 sm:last:border-r-0 md:px-8 md:py-6">
                        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white/15 text-white" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                            </svg>
                        </span>
                        <div>
                            <div class="text-[2rem] font-bold leading-none text-white [font-variant-numeric:tabular-nums] md:text-[2.25rem]">{$rating}</div>
                            <p class="mt-1.5 text-[0.8rem] font-semibold uppercase leading-tight tracking-[0.08em] text-white/92 md:text-[0.88rem]">{$rating_desc}</p>
                        </div>
                    </li>
                    <!-- Stat 4: License -->
                    <li class="flex h-full w-full items-center gap-4 px-6 py-5 text-white sm:border-b-0 md:px-8 md:py-6">
                        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-white/15 text-white" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                            </svg>
                        </span>
                        <div>
                            <div class="text-[1.7rem] font-bold leading-none text-white [font-variant-numeric:tabular-nums] md:text-[1.9rem]">{$license_text}</div>
                            <p class="mt-1.5 text-[0.8rem] font-semibold uppercase leading-tight tracking-[0.08em] text-white/92 md:text-[0.88rem]">{$license_desc}</p>
                        </div>
                    </li>
                </ul>
            </div>
        </section>
        HTML;

    }
}

class VisualGallery extends Component {
    public function render() {
        return <<<HTML
        <section class="border-b border-[#e6eef7] bg-[#e4f1fa] py-10 md:py-14">
            <div class="mx-auto max-w-6xl px-6 md:px-10">
                <div class="grid gap-4 md:grid-cols-4">
                    <div class="overflow-hidden rounded-2xl border border-[#dce8f5]">
                        <img src="/public/images/content/about-company.jpg" alt="Клиника БИОИНМЕД" class="h-56 w-full object-cover" loading="lazy" />
                    </div>
                    <div class="overflow-hidden rounded-2xl border border-[#dce8f5]">
                        <img src="/public/images/team/kostromina-default.jpg" alt="Костромина Инна Викторовна" class="h-56 w-full object-cover" loading="lazy" />
                    </div>
                    <div class="overflow-hidden rounded-2xl border border-[#dce8f5]">
                        <img src="/public/images/team/navrozov.jpg" alt="Наврозов Евгений Сергеевич" class="h-56 w-full object-cover" loading="lazy" />
                    </div>
                    <div class="overflow-hidden rounded-2xl border border-[#dce8f5]">
                        <img src="/public/images/team/nehorosheva.jpg" alt="Нехорошева Людмила Сергеевна" class="h-56 w-full object-cover" loading="lazy" />
                    </div>
                </div>
            </div>
        </section>
        HTML;
    }
}

class ProblemsGrid extends Component {
    protected $showTitle = true;
    protected $showCta = true;

    public function __construct($problems, $colors = [], $options = []) {
        parent::__construct($colors);
        $this->data = $problems;
        $this->showTitle = (bool)($options['show_title'] ?? true);
        $this->showCta = (bool)($options['show_cta'] ?? true);
    }

    public function render() {
        if (empty($this->data)) {
            return '';
        }

        $items_html = '';
        foreach ($this->data as $problem) {
            $slug = trim((string)($problem['slug'] ?? ''));
            $title = trim((string)($problem['title'] ?? ''));
            $description = trim((string)($problem['description'] ?? ''));
            if ($slug === '' || $title === '') {
                continue;
            }

            $items_html .= <<<HTML
            <a href="/problems/{$this->e($slug)}" class="group flex h-full min-h-[210px] flex-col rounded-[1rem] bg-[#1977b2] p-6 text-white transition hover:bg-[#16658f]">
                <div class="min-w-0 flex-1">
                    <h3 class="text-[1.18rem] font-bold leading-[1.08] text-white md:text-[1.32rem]">{$this->e($title)}</h3>
                    <p class="mt-4 max-w-[22rem] text-[0.92rem] leading-relaxed text-white md:text-[0.98rem]">{$this->e($description)}</p>
                </div>
                <div class="mt-5 inline-flex self-start items-center gap-2 rounded-full bg-white px-4 py-2 text-[0.92rem] font-semibold text-[#1977b2] shadow-[0_8px_18px_rgba(10,43,80,0.08)] transition group-hover:bg-[#f8fcff] group-hover:text-[#16658f]">
                    Подробнее
                    <i class="fa-solid fa-arrow-right text-[0.72rem]" aria-hidden="true"></i>
                </div>
            </a>
            HTML;
        }

        $section_title_html = $this->showTitle
            ? $this->sectionTitle('С какой проблемой обращаются', 'Найдите вашу ситуацию', 'Нажмите на карточку — откроется отдельная страница с подробным описанием, этапами маршрута и подходящими услугами.')
            : '';
        $section_cta_html = $this->showCta
            ? '<div class="mt-6 flex justify-start"><a href="/#contact" class="inline-flex items-center gap-2 rounded-full bg-[#1977b2] px-5 py-3 text-[0.92rem] font-semibold text-white shadow-[0_10px_24px_rgba(25,119,178,0.18)] transition hover:bg-[#16658f]">Не нашли свою ситуацию? Записаться на консультацию</a></div>'
            : '';

        return <<<HTML
        <section id="problems" class="border-b border-[#e6eef7] bg-[#e4f1fa] py-12 md:py-16">
            <div class="mx-auto max-w-6xl px-6 md:px-10">
                {$section_title_html}
                <div class="grid gap-8 sm:grid-cols-2 xl:grid-cols-4">
                    {$items_html}
                </div>
                {$section_cta_html}
            </div>
        </section>
        HTML;
    }
}

class AdvantagesBlock extends Component {
    public function __construct($advantages, $colors = []) {
        parent::__construct($colors);
        $this->data = $advantages;
    }

    public function render() {
        if (empty($this->data)) {
            return '';
        }

        $fa_icons = [
            'fa-microscope',
            'fa-user-doctor',
            'fa-shield-halved',
            'fa-location-dot',
        ];

        $items_html = '';
        $index = 0;
        foreach ($this->data as $advantage) {
            $icon = $fa_icons[$index % count($fa_icons)];
            $items_html .= <<<HTML
            <li class="flex items-start gap-3.5 rounded-xl border border-[#dce8f5] bg-white p-4">
                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#eaf4fc] text-[#1977b2]" aria-hidden="true">
                    <i class="fa-solid {$icon} text-[1rem]"></i>
                </span>
                <div>
                    <h3 class="text-[1rem] font-bold leading-tight text-[#0f2749]">{$this->e($advantage['title'])}</h3>
                    <p class="mt-1 text-[0.9rem] leading-relaxed text-[#0a293c]">{$this->e($advantage['description'])}</p>
                </div>
            </li>
            HTML;
            $index++;
        }

        return <<<HTML
        <section id="advantages" class="border-b border-[#e6eef7] bg-[#e4f1fa] py-12 md:py-16">
            <div class="mx-auto max-w-6xl px-6 md:px-10">
                {$this->sectionTitle('Почему выбирают нас', 'Преимущества клиники', 'Каждый пункт отражает наш стандарт работы: точная диагностика, персональный план и контроль результата.')}
                <ul class="grid gap-3 sm:grid-cols-2">
                    {$items_html}
                </ul>
            </div>
        </section>
        HTML;
    }
}

class ChiefDoctorBlock extends Component {
    public function __construct($doctor, $colors = []) {
        parent::__construct($colors);
        $this->data = $doctor;
    }

    public function render() {
        if (empty($this->data)) {
            return '';
        }

        $leadership = isset($this->data['leadership']) ? $this->e($this->data['leadership']) : 'Руководит клиническим процессом и развитием стандартов медицинской помощи.';
        $hero_leadership = isset($this->data['hero_leadership']) ? $this->e($this->data['hero_leadership']) : $leadership;
        $hero_tagline = trim((string)($this->data['hero_tagline'] ?? ''));
        $project_title = trim((string)($this->data['project_title'] ?? ''));
        $hero_tagline_html = $hero_tagline !== ''
            ? '<div class="mt-4 max-w-3xl"><p class="text-[#0a293c]" style="font-family:\'Caveat\',cursive;font-size:clamp(1.35rem,4vw,1.8rem);line-height:1.22;font-weight:700;">' . $this->e($hero_tagline) . '</p><p class="mt-2 text-[1.08rem] font-semibold tracking-[0.04em] text-[#4a6f9c]" style="font-family:\'Caveat\',cursive;">Костромина И.В.</p></div>'
            : '<p class="mt-5 text-base leading-relaxed text-[#0a293c]">В БИОИНМЕД каждый пациент получает не набор разрозненных процедур, а цельный лечебный маршрут: диагностика причин, подбор метода, оценка динамики и коррекция тактики.</p>';
        $project_title_html = $project_title !== ''
            ? '<p class="mt-2.5 text-[0.75rem] font-semibold uppercase tracking-[0.14em] text-[#0a293c]">' . $this->e($project_title) . '</p>'
            : '';
        $hero_highlights = $this->data['hero_highlights'] ?? [];
        $hero_highlights_html = '';
        if (!empty($hero_highlights) && is_array($hero_highlights)) {
            $hero_highlights_html .= '<ul class="mt-4 space-y-2 text-sm leading-relaxed text-[#0a293c]">';
            foreach ($hero_highlights as $highlight) {
                $hero_highlights_html .= '<li class="flex items-start gap-3"><span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-[#1977b2]"></span><span>' . $this->e($highlight) . '</span></li>';
            }
            $hero_highlights_html .= '</ul>';
        }
        $chief_image = bioinmed_versioned_asset_path('/public/images/team/kostromina.jpg');

        return <<<HTML
        <section class="border-b border-[#e6eef7] bg-[#e4f1fa] py-10 md:py-14">
            <div class="mx-auto max-w-6xl px-6 md:px-10">
                <div class="grid gap-8 md:grid-cols-[0.85fr_1.15fr] md:items-start">
                    <div class="max-w-[480px]">
                        <div class="aspect-square overflow-hidden rounded-3xl">
                            <img src="{$this->e($chief_image)}" alt="{$this->e($this->data['name'])}" class="h-full w-full rounded-3xl object-cover object-top" loading="lazy" />
                        </div>
                        <p class="mt-4 max-w-none text-[#0a293c]" style="font-family:'Caveat',cursive;font-size:clamp(1.35rem,4vw,1.8rem);line-height:1.22;font-weight:700;">Определение причины заболевания - Ваш первый шаг к психологическому и физическому здоровью</p>
                        <p class="mt-2 text-[1.08rem] font-semibold tracking-[0.04em] text-[#4a6f9c]" style="font-family:'Caveat',cursive;">Костромина И.В.</p>
                    </div>
                    <div>
                        <p class="text-[0.74rem] font-semibold uppercase tracking-[0.24em] text-[#1977b2]">ОСНОВАТЕЛЬ И ГЛАВНЫЙ ВРАЧ</p>
                        <h2 class="mt-2 text-[1.35rem] font-bold leading-tight text-[#0f2749] md:text-[1.6rem]">{$this->e($this->data['name'])}</h2>
                        {$project_title_html}
                        <p class="mt-6 text-[1rem] leading-relaxed text-[#0a293c] md:mt-8 md:text-[1.08rem]">Специализируюсь на сложных случаях. Более 30 лет клинической практики в области детской и взрослой медицины. {$hero_leadership}</p>
                        {$hero_highlights_html}
                        <a href="/doctors/kostromina-inna-viktorovna" class="mt-6 inline-flex items-center gap-2 rounded-full bg-[#1977b2] px-5 py-2.5 text-[0.92rem] font-semibold text-white shadow-[0_10px_24px_rgba(25,119,178,0.18)] transition hover:bg-[#16658f]">
                            Подробнее о враче
                            <i class="fa-solid fa-arrow-right text-[0.72rem]"></i>
                        </a>
                    </div>
                </div>
            </div>
        </section>
        HTML;
    }
}

class SpecialOffer extends Component {
    public function render() {
        $callback_form = bioinmed_render_callback_form([
            'source_label' => 'Главная — спецпредложение HABILECT',
            'submit_label' => 'Перезвоните мне',
        ]);
        return <<<HTML
        <section class="border-b border-[#e6eef7] bg-[#e4f1fa] py-10 md:py-12">
            <div class="mx-auto max-w-6xl px-6 md:px-10">
                <div class="overflow-hidden rounded-2xl border border-[#d8e7f5] bg-white px-6 py-6 md:px-8 md:py-7">
                    <p class="text-[0.74rem] font-semibold uppercase tracking-[0.24em] text-[#1977b2]">Специальное предложение для пациентов</p>
                    <h2 class="mt-2 max-w-3xl text-[1.2rem] font-bold leading-tight text-[#0f2749] md:text-[1.45rem]">Первичная 3D диагностика на мультифункциональном комплексе Хабилект + консультация реабилитолога</h2>
                    <div class="mt-4 border-l-4 border-[#1977b2] pl-4 md:pl-5">
                        <p class="text-[0.78rem] font-semibold uppercase tracking-[0.16em] text-[#0a293c]">Специальная цена</p>
                        <div class="mt-1 flex flex-wrap items-end gap-x-3 gap-y-1">
                            <span class="text-2xl font-bold leading-none text-[#0f2749] md:text-[1.9rem]">3000 руб.</span>
                            <span class="text-sm text-[#5b81a8] md:text-[0.98rem]">вместо <span class="line-through">6000 руб.</span></span>
                        </div>
                        <p class="mt-1 text-[0.92rem] font-medium text-[#2a7b58]">Экономия 3000 руб.</p>
                    </div>
                    <p class="mt-4 max-w-3xl text-[0.94rem] leading-relaxed text-[#0a293c]">
                        Первая консультация, которая помогает увидеть функциональные нарушения позвоночника и суставов и получить понятный план восстановления. 3D-диагностика на мультифункциональном комплексе Хабилект даёт наглядную картину состояния опорно-двигательного аппарата и объективно дополняет данные МРТ.
                    </p>
                    <ul class="mt-4 max-w-3xl space-y-2.5 text-[0.94rem] leading-relaxed text-[#0a293c]">
                        <li class="flex items-start gap-2.5">
                            <i class="fa-solid fa-check mt-1 text-[0.8rem] text-[#1977b2]" aria-hidden="true"></i>
                            <span>3D-диагностика на мультифункциональном комплексе Хабилект для точной оценки нарушений опорно-двигательного аппарата</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <i class="fa-solid fa-check mt-1 text-[0.8rem] text-[#1977b2]" aria-hidden="true"></i>
                            <span>Консультация реабилитолога с подбором индивидуального комплекса ЛФК</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <i class="fa-solid fa-check mt-1 text-[0.8rem] text-[#1977b2]" aria-hidden="true"></i>
                            <span>Диагностика стоп на подоскопе в подарок</span>
                        </li>
                    </ul>
                    <div class="mt-4">
                        <button type="button" data-special-offer-open class="inline-flex items-center gap-2 rounded-full bg-[#1977b2] px-5 py-2.5 text-[0.94rem] font-semibold text-white hover:bg-[#16658f]">
                            <i class="fa-solid fa-phone text-[0.86rem]" aria-hidden="true"></i>
                            Перезвоните мне
                        </button>
                    </div>
                </div>
            </div>
            <div id="special-offer-callback-modal" class="fixed inset-0 z-[120] hidden items-center justify-center bg-[rgba(7,21,40,0.72)] px-4 py-6" role="dialog" aria-modal="true" aria-labelledby="special-offer-callback-title">
                <div class="relative w-full max-w-md rounded-[1.75rem] border border-[#d8e7f5] bg-white p-6 shadow-[0_24px_60px_rgba(7,21,40,0.24)] md:p-7">
                    <button type="button" data-special-offer-close class="absolute right-4 top-4 inline-flex h-10 w-10 items-center justify-center rounded-full border border-[#d7e6f3] bg-white text-[#0a293c] transition hover:border-[#1977b2] hover:text-[#1977b2]" aria-label="Закрыть окно">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                    <p class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-[#1977b2]">Специальное предложение</p>
                    <h3 id="special-offer-callback-title" class="mt-2 text-[1.3rem] font-bold leading-tight text-[#0f2749] md:text-[1.45rem]">Перезвоним и запишем на консультацию</h3>
                    <p class="mt-2 text-[0.92rem] leading-relaxed text-[#0a293c]">Оставьте номер телефона. Мы свяжемся с вами и подберём удобное время записи на диагностику на мультифункциональном комплексе Хабилект.</p>
                    <div class="mt-5">
                        {$callback_form}
                    </div>
                </div>
            </div>
        </section>
        HTML;
    }
}

class DoctorsGrid extends Component {
    public function __construct($doctors, $colors = []) {
        parent::__construct($colors);
        $this->data = $doctors;
    }

    public function render() {
        if (empty($this->data)) {
            return '';
        }

        $cards_html = '';
        foreach ($this->data as $doctor) {
            $slug = isset($doctor['slug']) ? $this->e($doctor['slug']) : '';
            $doctor_link = '/doctors/' . $slug;
            $doctor_image = bioinmed_versioned_asset_path('/public/images/team/' . ($doctor['image'] ?? ''));
            $has_profile = !array_key_exists('has_profile', $doctor) || $doctor['has_profile'] !== false;
            $card_action_text = trim((string)($doctor['card_action_text'] ?? 'Команда клиники'));
            $doctor_image_html = $has_profile
                ? '<a href="' . $doctor_link . '" class="block overflow-hidden"><img src="' . $this->e($doctor_image) . '" alt="' . $this->e($doctor['name']) . '" class="h-80 w-full object-cover transition duration-300 group-hover:scale-[1.03] md:h-[22rem]" loading="lazy"></a>'
                : '<img src="' . $this->e($doctor_image) . '" alt="' . $this->e($doctor['name']) . '" class="h-80 w-full object-cover md:h-[22rem]" loading="lazy">';
            $doctor_name_html = $has_profile
                ? '<h3 class="text-lg font-bold leading-tight text-[#0a293c]"><a href="' . $doctor_link . '" class="transition hover:text-[#1977b2]">' . $this->e($doctor['name']) . '</a></h3>'
                : '<h3 class="text-lg font-bold leading-tight text-[#0a293c]">' . $this->e($doctor['name']) . '</h3>';
            $card_action = $has_profile
                ? '<a href="' . $doctor_link . '" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-full bg-[#1977b2] px-4 py-2.5 text-[0.86rem] font-semibold text-white shadow-[0_10px_24px_rgba(25,119,178,0.18)] transition hover:bg-[#16658f]">Подробнее <i class="fa-solid fa-arrow-right text-[0.72rem]"></i></a>'
                : ($card_action_text !== ''
                    ? '<div class="mt-4 w-full rounded-full border border-[#d8e6f3] bg-white py-2.5 text-center text-[0.82rem] font-semibold uppercase tracking-[0.08em] text-[#6d8db2]">' . $this->e($card_action_text) . '</div>'
                    : '');
            $cards_html .= <<<HTML
            <article class="min-w-[320px] max-w-[320px] shrink-0 overflow-hidden rounded-2xl border border-[#dce8f5] bg-white shadow-[0_10px_28px_rgba(9,39,72,0.08)] sm:min-w-[350px] sm:max-w-[350px] lg:min-w-[380px] lg:max-w-[380px] flex flex-col self-stretch">
                {$doctor_image_html}
                <div class="flex flex-1 flex-col p-6">
                    <div class="flex-1">
                        {$doctor_name_html}
                        <p class="mt-1 text-[0.82rem] font-semibold uppercase tracking-[0.12em] text-[#0a293c]">{$this->e($doctor['title'])}</p>
                        <p class="mt-2 text-sm font-semibold leading-snug text-[#0a293c]">{$this->e($doctor['experience'])}</p>
                        <p class="mt-3 text-[0.96rem] text-[#0a293c]">{$this->e($doctor['specialty'])}</p>
                    </div>
                    {$card_action}
                </div>
            </article>
            HTML;
        }

        return <<<HTML
        <section id="doctors" class="border-b border-[#e6eef7] bg-[#e4f1fa] py-12 md:py-16">
            <div class="mx-auto max-w-6xl px-6 md:px-10">
                {$this->sectionTitle('Наша команда', 'Профессиональная команда специалистов', 'Познакомьтесь с врачами команды и перейдите в карточку специалиста для подробной информации.')}
                <div class="mb-4 flex items-center justify-end gap-2">
                    <button type="button" class="doctor-slider-prev inline-flex h-10 w-10 items-center justify-center rounded-full border border-[#c5d9eb] bg-white text-[#0a293c] hover:bg-[#ecf5ff]" aria-label="Прокрутить влево">
                        <i class="fa-solid fa-chevron-left text-[1rem]" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="doctor-slider-next inline-flex h-10 w-10 items-center justify-center rounded-full border border-[#c5d9eb] bg-white text-[#0a293c] hover:bg-[#ecf5ff]" aria-label="Прокрутить вправо">
                        <i class="fa-solid fa-chevron-right text-[1rem]" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="doctor-slider-track flex items-stretch gap-4 overflow-x-auto scroll-smooth">
                    {$cards_html}
                </div>
            </div>
        </section>
        HTML;
    }
}

class FAQBlock extends Component {
    public function __construct($faqItems, $colors = []) {
        parent::__construct($colors);
        $this->data = $faqItems;
    }

    public function render() {
        if (empty($this->data)) {
            return '';
        }

        $items_html = '';
        foreach ($this->data as $item) {
            $items_html .= <<<HTML
            <details class="group rounded-2xl border border-[#dce8f5] bg-white p-5 open:shadow-[0_10px_30px_rgba(7,35,68,0.08)]">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-left text-base font-semibold text-[#0a293c]">
                    <span>{$this->e($item['question'])}</span>
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#c9dff1] bg-white text-[#0a293c]">
                        <i class="fa-solid fa-chevron-down text-[0.82rem] transition group-open:rotate-180" aria-hidden="true"></i>
                    </span>
                </summary>
                <p class="mt-4 text-[0.96rem] leading-relaxed text-[#0a293c]">{$this->e($item['answer'])}</p>
            </details>
            HTML;
        }

        return <<<HTML
        <section id="faq" class="border-b border-[#e6eef7] bg-[#e4f1fa] py-12 md:py-16">
            <div class="mx-auto max-w-6xl px-6 md:px-10">
                {$this->sectionTitle('Ответы на частые вопросы', 'Что важно знать перед записью', 'Коротко отвечаем на самые частые вопросы перед первым визитом.')}
                <div class="grid gap-3 md:gap-4">
                    {$items_html}
                </div>
            </div>
        </section>
        HTML;
    }
}

class ServicesGrid extends Component {
    protected $showImages = true;

    public function __construct($services, $colors = [], $options = []) {
        parent::__construct($colors);
        $this->data = $services;
        $this->showImages = (bool)($options['show_images'] ?? true);
    }

    public function render() {
        if (empty($this->data)) {
            return '';
        }

        $items_html = '';
        $icons = [
            'fa-stethoscope',
            'fa-pills',
            'fa-wand-magic-sparkles',
            'fa-heart-pulse',
            'fa-hand-holding-heart',
            'fa-lightbulb',
        ];

        $count = 0;
        foreach ($this->data as $service) {
            if ($count >= 6) break; // Показываем только 6 услуг

            $icon = isset($icons[$count]) ? $icons[$count] : $icons[$count % count($icons)];
            $service_id = isset($service['id']) ? $this->e($service['id']) : '';
            $service_link = '/services/' . $service_id;
            $service_image = $this->showImages ? bioinmed_service_primary_image_url($service) : null;
            $service_image_html = '';

            if ($service_image !== null) {
                $service_image_html = '<div class="relative mb-4 overflow-hidden rounded-2xl border border-[#dfeaf3] bg-[#eef7fd] aspect-[4/3]">'
                    . '<img src="' . $this->e($service_image) . '" alt="' . $this->e($service['name']) . '" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]" loading="lazy">'
                    . '<div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-[rgba(8,35,67,0.60)] via-[rgba(8,35,67,0.18)] to-transparent"></div>'
                    . '<div class="absolute left-3 top-3 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/88 text-[#1977b2] shadow-[0_8px_18px_rgba(8,36,70,0.12)]">'
                    . '<i class="fa-solid ' . $icon . ' text-[1rem]" aria-hidden="true"></i>'
                    . '</div>'
                    . '</div>';
            }
            
            $price_display = '';
            if (isset($service['price'])) {
                $price_display = '<div class="mt-4 text-[1rem] font-semibold text-[#1977b2]">' . $this->e($service['price']);
                if (isset($service['price_note']) && !empty($service['price_note'])) {
                    $price_display .= ' <span class="text-[0.86rem] font-normal text-[#0a293c]">' . $this->e($service['price_note']) . '</span>';
                }
                $price_display .= '</div>';
            }
            $card_description = isset($service['card_description']) && trim((string)$service['card_description']) !== ''
                ? (string)$service['card_description']
                : (string)($service['description'] ?? '');

            $items_html .= <<<HTML
            <article class="group flex h-full flex-col rounded-[1.35rem] border border-[#d7e4ef] bg-white/80 p-5 transition hover:-translate-y-0.5 hover:border-[#1977b2] hover:shadow-[0_12px_28px_rgba(25,119,178,0.16)]">
                <div class="flex-1">
                    {$service_image_html}
                    <div class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-[#e3f2fc] text-[#1977b2]">
                        <i class="fa-solid {$icon} text-[1rem]" aria-hidden="true"></i>
                    </div>
                    <p class="mb-1 text-[0.82rem] font-semibold uppercase tracking-[0.1em] text-[#0a293c]">{$this->e($service['subtitle'] ?? 'Услуга')}</p>
                    <h3 class="mb-2 text-[1.2rem] font-bold leading-[1.2]">
                        <a href="{$service_link}" class="text-[#0f2749] transition hover:text-[#1977b2] focus-visible:rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#1977b2] focus-visible:ring-offset-2 focus-visible:ring-offset-white">
                            {$this->e($service['name'])}
                        </a>
                    </h3>
                    <p class="text-[0.96rem] leading-relaxed text-[#0a293c]">{$this->e($card_description)}</p>
                    {$price_display}
                </div>
                <a href="{$service_link}" class="mt-4 inline-flex items-center gap-2 self-start rounded-full bg-[#1977b2] px-4 py-2.5 text-[0.86rem] font-semibold text-white shadow-[0_10px_24px_rgba(25,119,178,0.18)] transition hover:bg-[#16658f]">
                    Подробнее
                    <i class="fa-solid fa-arrow-right text-[0.72rem]"></i>
                </a>
            </article>
            HTML;
            $count++;
        }

        return <<<HTML
        <section id="services" class="border-b border-[#e6eef7] bg-[#e4f1fa] py-10 md:py-14">
            <div class="mx-auto max-w-6xl px-6 md:px-10">
                {$this->sectionTitle('Популярные услуги', 'Основные направления лечения', 'Выберите интересующее Вас направление, чтобы узнать подробнее о методах, показаниях и ценах')}
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {$items_html}
                </div>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[0.96rem] text-[#0a293c]">Хотите начать с комплексной диагностики?</p>
                    <a href="/services/hobilect-diagnostics" class="inline-flex rounded-lg bg-[#1977b2] px-5 py-2.5 text-[0.96rem] font-semibold text-white transition hover:bg-[#16658f] active:bg-[#13557f]">
                        HABILECT диагностика →
                    </a>
                </div>
            </div>
        </section>
        HTML;
    }
}

class CasesSlider extends Component {
    public function __construct($colors = []) {
        parent::__construct($colors);
    }

    public function render() {
        return <<<HTML
        <section id="reviews" class="border-b border-[#e6eef7] bg-[#e4f1fa] py-12 md:py-16">
            <div class="mx-auto max-w-6xl px-6 md:px-10">
                {$this->sectionTitle('Нам доверяют', 'Отзывы пациентов', 'Отзывы наших пациентов на Яндекс.Картах.')}
                <div class="mx-auto overflow-hidden rounded-2xl border border-[#dce8f5] shadow-[0_8px_24px_rgba(10,43,80,0.07)]" style="max-width:700px;">
                    <div style="width:100%;height:800px;overflow:hidden;position:relative;"><iframe style="width:100%;height:100%;border:1px solid #e6e6e6;border-radius:8px;box-sizing:border-box" src="https://yandex.ru/maps-reviews-widget/20810337169?comments"></iframe><a href="https://yandex.com/maps/org/bioinmed/20810337169/" target="_blank" style="box-sizing:border-box;text-decoration:none;color:#b3b3b3;font-size:10px;font-family:YS Text,sans-serif;padding:0 20px;position:absolute;bottom:8px;width:100%;text-align:center;left:0;overflow:hidden;text-overflow:ellipsis;display:block;max-height:14px;white-space:nowrap;padding:0 16px;box-sizing:border-box">Биоинмед на карте Москвы — Яндекс Карты</a></div>
                </div>
            </div>
        </section>
        HTML;
    }
}

class AppointmentCTA extends Component {
    public function render() {
        $callback_form = bioinmed_render_callback_form([
            'source_label' => 'Главная — финальная CTA',
            'submit_label' => 'Перезвоните мне',
        ]);

        return <<<HTML
        <section id="book-now" class="border-b border-[#e6eef7] bg-[#e4f1fa] py-10 md:py-14">
            <div class="mx-auto max-w-6xl px-6 md:px-10">
                <div class="rounded-3xl border border-[#d7e6f3] bg-white p-7 shadow-[0_18px_42px_rgba(6,29,60,0.08)] md:p-9">
                    <p class="text-[0.74rem] font-semibold uppercase tracking-[0.22em] text-[#1977b2]">Запишитесь на консультацию</p>
                    <h2 class="mt-2 text-[1.35rem] font-bold leading-tight text-[#0f2749] md:text-[1.6rem]">Записаться на приём</h2>
                    <p class="mt-2.5 max-w-2xl text-[0.94rem] leading-relaxed text-[#0a293c]">Перезвоним в течение 15 минут.</p>
                    <div class="mt-5 max-w-lg">
                        {$callback_form}
                    </div>
                </div>
            </div>
        </section>
        HTML;
    }
}

class ContactSection extends Component {
    public function render() {
        $phone_1 = $this->e(CLINIC_PHONE);
        $phone_2 = defined('CLINIC_PHONE_2') ? $this->e(CLINIC_PHONE_2) : '';
        $email = $this->e(CLINIC_EMAIL);
        $address = $this->e(CLINIC_ADDRESS);
        $metro = $this->e(CLINIC_METRO);
        $hours = $this->e(CLINIC_HOURS);
        $phone_link_1 = $this->phoneLink(CLINIC_PHONE);
        $phone_link_2 = defined('CLINIC_PHONE_2') ? $this->phoneLink(CLINIC_PHONE_2) : '';

        $second_phone_html = $phone_2 !== ''
            ? '<div class="mt-1"><a href="tel:' . $phone_link_2 . '" class="text-[1rem] font-semibold text-[#1977b2] hover:text-[#0f2749] transition">' . $phone_2 . '</a></div>'
            : '';

        return <<<HTML
        <section id="contact" class="bg-gradient-to-b from-[#e4f1fa] to-[#f8fbff] py-10 md:py-14">
            <div class="mx-auto max-w-6xl px-6 md:px-10">
                {$this->sectionTitle('Контакты', 'Адрес и связь с клиникой', 'Мы всегда на связи и готовы ответить на Ваши вопросы')}

                <div class="grid gap-8 lg:grid-cols-[1fr_1.2fr]">
                    <!-- Левая часть: Фото клиники -->
                    <div class="flex items-start">
                        <div class="mx-auto w-full max-w-[440px] lg:mx-0">
                            <div class="relative aspect-square overflow-hidden rounded-2xl bg-[#eaf4fd]">
                                <img
                                    src="/public/images/bioinmed-contacts-pic.jpg"
                                    alt="Клиника БИОИНМЕД - кабинеты и атмосфера"
                                    class="h-full w-full object-cover object-center"
                                    loading="lazy"
                                    decoding="async"
                                />
                                <div class="pointer-events-none absolute inset-x-0 bottom-0 h-20 bg-gradient-to-t from-[#0f2749]/30 to-transparent"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Правая часть: Информация -->
                    <div class="space-y-5">
                        <!-- Заголовок карточки -->
                        <div class="rounded-2xl border border-[#d7e4ef] bg-white p-6 shadow-[0_4px_16px_rgba(6,29,60,0.06)]">
                            <div class="flex items-start gap-3 mb-1">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#e3f2fc] text-[#1977b2] shrink-0">
                                    <i class="fa-solid fa-hospital text-[0.9rem]" aria-hidden="true"></i>
                                </span>
                                <h3 class="text-[1.25rem] font-bold text-[#0f2749]">Клиника «БИОИНМЕД»</h3>
                            </div>
                            <p class="text-[0.96rem] text-[#0a293c] leading-relaxed ml-11">
                                Свяжитесь с нами удобным способом или посетите клинику по указанному адресу
                            </p>
                        </div>

                        <!-- Блок контактов -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Телефон -->
                            <div class="rounded-xl border border-[#d7e4ef] bg-white p-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-[#e3f2fc] text-[#1977b2]">
                                        <i class="fa-solid fa-phone text-[0.75rem]" aria-hidden="true"></i>
                                    </span>
                                    <p class="text-[0.82rem] font-bold uppercase tracking-[0.08em] text-[#0a293c]">Телефон</p>
                                </div>
                                <a href="tel:{$phone_link_1}" class="block text-[1rem] font-bold text-[#1977b2] hover:text-[#0f2749] transition leading-snug">
                                    {$phone_1}
                                </a>
                                {$second_phone_html}
                            </div>

                            <!-- Адрес -->
                            <div class="rounded-xl border border-[#d7e4ef] bg-white p-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-[#e3f2fc] text-[#1977b2]">
                                        <i class="fa-solid fa-map-pin text-[0.75rem]" aria-hidden="true"></i>
                                    </span>
                                    <p class="text-[0.82rem] font-bold uppercase tracking-[0.08em] text-[#0a293c]">Адрес</p>
                                </div>
                                <p class="text-[1rem] font-semibold text-[#0f2749] leading-snug">
                                    {$address}
                                </p>
                                <p class="text-[0.92rem] text-[#0a293c] mt-1">
                                    {$metro}
                                </p>
                                <a href="javascript:void(0)" class="jsClientix_openWidget mt-4 inline-flex w-full items-center justify-center rounded-full bg-[#1977b2] px-4 py-2.5 text-[0.92rem] font-semibold text-white transition hover:bg-[#16658f] md:hidden">
                                    Онлайн запись
                                </a>
                            </div>

                            <!-- Режим работы -->
                            <div class="rounded-xl border border-[#d7e4ef] bg-white p-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-[#e3f2fc] text-[#1977b2]">
                                        <i class="fa-solid fa-clock text-[0.75rem]" aria-hidden="true"></i>
                                    </span>
                                    <p class="text-[0.82rem] font-bold uppercase tracking-[0.08em] text-[#0a293c]">Режим</p>
                                </div>
                                <p class="text-[1rem] font-semibold text-[#0f2749] leading-snug">
                                    {$hours}
                                </p>
                            </div>

                            <!-- Email -->
                            <div class="rounded-xl border border-[#d7e4ef] bg-white p-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-[#e3f2fc] text-[#1977b2]">
                                        <i class="fa-solid fa-envelope text-[0.75rem]" aria-hidden="true"></i>
                                    </span>
                                    <p class="text-[0.82rem] font-bold uppercase tracking-[0.08em] text-[#0a293c]">Email</p>
                                </div>
                                <a href="mailto:{$email}" class="text-[1rem] font-semibold text-[#1977b2] hover:text-[#0f2749] transition break-all">
                                    {$email}
                                </a>
                            </div>
                        </div>

                        <!-- Как добраться -->
                        <div class="rounded-xl border border-[#1977b2] bg-gradient-to-br from-[#f0fafe] to-[#e8f7fb] p-5">
                            <div class="flex items-start gap-3">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#1977b2] text-white shrink-0 mt-0.5">
                                    <i class="fa-solid fa-directions text-[0.9rem]" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <h4 class="font-bold text-[#0f2749] mb-2">Как добраться</h4>
                                    <p class="text-[0.92rem] text-[#0a293c] leading-relaxed mb-3">
                                        Станция метро <strong>Фрунзенская</strong>. Выход из стеклянных дверей налево, затем прямо по переулку Хользунова до первого перекрёстка со светофором. Перейдите дорогу (ориентир — кафе «Брусника») и пройдите ещё около 50 метров до вывески «БИОИНМЕД».
                                    </p>
                                    <a href="https://yandex.com/maps/-/CPGGyEzo" target="_blank" rel="noreferrer noopener" class="inline-flex items-center gap-2 rounded-lg bg-[#1977b2] px-4 py-2 text-[0.92rem] font-semibold text-white transition hover:bg-[#16658f]">
                                        <i class="fa-solid fa-map text-[0.82rem]" aria-hidden="true"></i>
                                        Открыть в Яндекс.Картах
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Оставить отзыв -->
                        <div class="rounded-xl border border-[#dce8f5] bg-white p-5">
                            <h4 class="font-bold text-[#0f2749] mb-3">Оставить отзыв о центре</h4>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <a href="https://yandex.ru/maps/org/bioinmed/20810337169/reviews/?ll=37.579538%2C55.731055&z=15" target="_blank" rel="noreferrer noopener" class="inline-flex items-center justify-center rounded-lg border border-[#d7e4ef] bg-white px-3 py-2 text-[0.88rem] font-semibold text-[#0a293c] transition hover:border-[#1977b2] hover:bg-[#f0fafe]">
                                    <i class="fa-solid fa-star text-[0.88rem] mr-1 text-[#1977b2]"></i>
                                    Яндекс
                                </a>
                                <a href="https://2gis.ru/moscow/firm/70000001085756150/tab/reviews" target="_blank" rel="noreferrer noopener" class="inline-flex items-center justify-center rounded-lg border border-[#d7e4ef] bg-white px-3 py-2 text-[0.88rem] font-semibold text-[#0a293c] transition hover:border-[#1977b2] hover:bg-[#f0fafe]">
                                    <i class="fa-solid fa-star text-[0.88rem] mr-1 text-[#1977b2]"></i>
                                    2ГИС
                                </a>
                                <a href="https://doctu.ru/msk/clinic/bioinmed" target="_blank" rel="noreferrer noopener" class="inline-flex items-center justify-center rounded-lg border border-[#d7e4ef] bg-white px-3 py-2 text-[0.88rem] font-semibold text-[#0a293c] transition hover:border-[#1977b2] hover:bg-[#f0fafe]">
                                    <i class="fa-solid fa-star text-[0.88rem] mr-1 text-[#1977b2]"></i>
                                    Doctu
                                </a>
                                <a href="https://vk.com/bioinmed" target="_blank" rel="noreferrer noopener" class="inline-flex items-center justify-center rounded-lg border border-[#d7e4ef] bg-white px-3 py-2 text-[0.88rem] font-semibold text-[#0a293c] transition hover:border-[#1977b2] hover:bg-[#f0fafe]">
                                    <i class="fa-brands fa-vk text-[0.88rem] mr-1"></i>
                                    ВКонтакте
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        HTML;
    }
}

class PartnersBlock extends Component {
    public function render() {
        $heel_logo = $this->e(bioinmed_versioned_asset_path('/public/images/partners/heel-logo.svg'));
        $habilect_logo = $this->e(bioinmed_versioned_asset_path('/public/images/partners/habilect-logo.png'));

        return <<<HTML
        <section class="border-b border-[#e6eef7] bg-[#e4f1fa] py-10 md:py-14">
            <div class="mx-auto max-w-6xl px-6 md:px-10">
                <div class="rounded-[2rem] bg-[linear-gradient(180deg,#ffffff_0%,#f7fbff_100%)] p-6 shadow-[0_10px_24px_rgba(6,29,60,0.04)] md:p-8">
                    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                        <div>
                            <p class="text-[0.7rem] font-semibold uppercase tracking-[0.22em] text-[#1977b2]">Партнёры и технологии</p>
                            <h2 class="mt-2 text-[1.35rem] font-bold leading-tight text-[#0f2749] md:text-[1.7rem]">Heel и HABILECT в основе нашего подхода</h2>
                        </div>
                        <p class="max-w-2xl text-[0.9rem] leading-relaxed text-[#0a293c] md:text-[0.95rem]">
                            Мы объединяем доказательные решения и современные технологии диагностики, чтобы путь пациента был точным, понятным и результативным.
                        </p>
                    </div>

                    <div class="mt-5 overflow-hidden rounded-[1.75rem] bg-white/55">
                        <div class="grid gap-0 md:grid-cols-2">
                            <div class="flex flex-col items-center gap-4 px-5 py-5 md:px-8 md:py-7">
                                <div class="flex h-16 w-28 shrink-0 items-center justify-center md:h-18 md:w-36">
                                    <img src="{$heel_logo}" alt="Heel" class="h-12 w-[120px] max-w-none object-contain md:h-14 md:w-[150px]" loading="lazy" decoding="async">
                                </div>
                                <div class="min-w-0 text-center">
                                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-[#1977b2]">Heel</p>
                                    <p class="mt-1 max-w-sm text-[0.92rem] leading-relaxed text-[#0a293c]">Надёжный партнёр в интегративной терапии и мягких лечебных программах.</p>
                                </div>
                            </div>
                            <div class="flex flex-col items-center gap-4 border-t border-[#edf3f9] px-5 py-5 md:border-l md:border-t-0 md:px-8 md:py-7">
                                <div class="flex h-16 w-28 shrink-0 items-center justify-center md:h-18 md:w-36">
                                    <img src="{$habilect_logo}" alt="HABILECT" class="h-12 w-[120px] max-w-none object-contain md:h-14 md:w-[150px]" loading="lazy" decoding="async">
                                </div>
                                <div class="min-w-0 text-center">
                                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-[#1977b2]">Habilect</p>
                                    <p class="mt-1 max-w-sm text-[0.92rem] leading-relaxed text-[#0a293c]">Точная 3D-диагностика и персональный маршрут восстановления.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        HTML;
    }
}

class SolidarityMedicineBlock extends Component {
    public function render() {
        return <<<HTML
        <section class="border-b border-[#e6eef7] bg-[#e4f1fa] py-10 md:py-14">
            <div class="mx-auto max-w-6xl px-6 md:px-10">
                <div class="overflow-hidden rounded-[2rem] border border-[#d7e6f3] bg-white shadow-[0_18px_42px_rgba(6,29,60,0.08)]">
                    <div class="grid gap-0 lg:grid-cols-[1.05fr_0.95fr]">
                        <div class="p-6 md:p-8 lg:p-10">
                            <p class="text-[0.74rem] font-semibold uppercase tracking-[0.22em] text-[#1977b2]">Профессиональное объединение</p>
                            <h2 class="mt-2 text-[1.4rem] font-bold leading-tight text-[#0f2749] md:text-[1.8rem]">Солидарная авторская медицина</h2>
                            <p class="mt-3 text-[1.05rem] font-semibold text-[#0a293c]">Проект «Солидарная авторская медицина»</p>
                            <p class="mt-5 max-w-2xl text-[0.98rem] leading-relaxed text-[#0a293c]">
                                Это профессиональное объединение опытных врачей, работающих на единой научно-практической площадке.
                            </p>
                            <p class="mt-4 max-w-2xl text-[0.98rem] leading-relaxed text-[#0a293c]">
                                Проект объединяет специалистов с большим клиническим опытом, авторскими методиками, собственными программами восстановления здоровья и индивидуальным подходом к пациенту.
                            </p>
                            <p class="mt-4 max-w-2xl text-[0.98rem] leading-relaxed text-[#0a293c]">
                                В основе проекта - обмен профессиональным опытом, медицинские консилиумы, научные дискуссии, разработка новых подходов, подготовка статей и докладов.
                            </p>
                        </div>

                        <div class="border-t border-[#e6eef7] bg-[#f8fbff] p-6 md:p-8 lg:border-l lg:border-t-0 lg:p-10">
                            <div class="flex h-full flex-col justify-between">
                                <div>
                                    <div class="inline-flex items-center gap-2 rounded-full bg-[#e8f3fc] px-4 py-2 text-[0.74rem] font-semibold uppercase tracking-[0.16em] text-[#1977b2]">
                                        <i class="fa-solid fa-diagram-project text-[0.75rem]" aria-hidden="true"></i>
                                        Общая площадка
                                    </div>
                                    <blockquote class="mt-5 text-[1.02rem] leading-relaxed text-[#0a293c] md:text-[1.08rem]">
                                        «Солидарная авторская медицина» - это пространство для профессионального роста врачей, развития медицинской практики и формирования эффективных решений на стыке опыта, науки и доказательной медицины.
                                    </blockquote>
                                </div>

                                <div class="mt-6 grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                                    <div class="rounded-2xl border border-[#dce8f5] bg-white p-4">
                                        <p class="text-[0.78rem] font-semibold uppercase tracking-[0.14em] text-[#1977b2]">Консилиумы</p>
                                        <p class="mt-1 text-[0.92rem] leading-relaxed text-[#0a293c]">Совместный разбор сложных клинических случаев</p>
                                    </div>
                                    <div class="rounded-2xl border border-[#dce8f5] bg-white p-4">
                                        <p class="text-[0.78rem] font-semibold uppercase tracking-[0.14em] text-[#1977b2]">Наука</p>
                                        <p class="mt-1 text-[0.92rem] leading-relaxed text-[#0a293c]">Исследования, статьи, доклады и профессиональная дискуссия</p>
                                    </div>
                                    <div class="rounded-2xl border border-[#dce8f5] bg-white p-4">
                                        <p class="text-[0.78rem] font-semibold uppercase tracking-[0.14em] text-[#1977b2]">Практика</p>
                                        <p class="mt-1 text-[0.92rem] leading-relaxed text-[#0a293c]">Авторские методики и индивидуальные программы восстановления</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        HTML;
    }
}

class SeasonsBlock extends Component {
    public function render() {
        $seasons = require __DIR__ . '/../../config/seasons.php';

        $actual_slug = bioinmed_current_season_slug();
        if (!isset($seasons[$actual_slug])) {
            $actual_slug = 'spring';
        }
        $actual = $seasons[$actual_slug];
        $name = $this->e($actual['name']);
        $color = $this->e($actual['color']);
        $color_light = $this->e($actual['color_light'] ?? '#edf4fb');
        $image_desktop = $this->e($actual['image_desktop'] ?? $actual['image']);
        $image_mobile = $this->e($actual['image_mobile'] ?? $actual['image']);
        $image_alt = $this->e($actual['image_alt'] ?? $actual['name']);
        $image_mobile_alt = $this->e($actual['image_mobile_alt'] ?? ($actual['image_alt'] ?? $actual['name']));
        $slogan = $this->e($actual['slogan']);
        $quote = $this->e($actual['quote'] ?? '');
        $href = $this->e('/seasons/' . $actual_slug);

        return <<<HTML
        <section class="py-0" style="background:linear-gradient(180deg,#ffffff 0%, {$color_light} 100%);" aria-labelledby="seasons-heading">
            <div class="relative min-h-[500px] w-screen overflow-hidden shadow-[0_24px_56px_rgba(6,22,38,0.16)] md:min-h-[640px]">
                <div class="absolute inset-0 hidden bg-cover bg-center bg-no-repeat md:block" style="background-image:url('{$image_desktop}');" role="img" aria-label="{$image_alt}"></div>
                <div class="absolute inset-0 bg-cover bg-center bg-no-repeat md:hidden" style="background-image:url('{$image_mobile}');" role="img" aria-label="{$image_mobile_alt}"></div>
                <div class="absolute inset-0" style="background:linear-gradient(to top, rgba(0,0,0,0.80) 0%, rgba(0,0,0,0.45) 45%, rgba(0,0,0,0.10) 100%)"></div>

                <div class="relative z-[1] flex min-h-[500px] flex-col justify-end pb-10 pt-14 md:min-h-[640px] md:pb-12 md:pt-16">
                    <div class="mx-auto max-w-6xl px-6 md:px-10">
                        <div class="max-w-2xl">
                            <p class="mb-2.5 text-[0.74rem] font-semibold uppercase tracking-[0.2em]" style="color:{$color}">Времена года</p>
                            <h2 id="seasons-heading" class="mb-4 text-4xl font-black leading-none text-white md:text-6xl">{$name}</h2>
                            <p class="mb-4 max-w-xl text-[0.96rem] font-light text-white/92 md:text-[1.1rem]">{$slogan}</p>
                            <blockquote class="max-w-2xl border-l-4 pl-3.5 text-[0.86rem] italic leading-relaxed text-white/86 md:text-[0.94rem]" style="border-color:{$color}">{$quote}</blockquote>
                            <a href="{$href}" class="mt-5 inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-[0.82rem] font-semibold text-[#123a63] shadow-[0_10px_24px_rgba(0,0,0,0.18)] transition hover:bg-[#f2fbff]">
                                Подробнее
                                <i class="fa-solid fa-arrow-right text-[0.76rem]" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        HTML;
    }
}

class Footer extends Component {
    public function render() {
        $vk = defined('CLINIC_VK') ? $this->e(CLINIC_VK) : '#';
        $telegram = defined('CLINIC_TELEGRAM') ? $this->e(CLINIC_TELEGRAM) : '#';
        $max = 'https://max.ru/id9704215369_bot';
        $max_icon_src = $this->e(bioinmed_versioned_asset_path('/public/images/icons/max-logo.png'));
        $phone = $this->phoneLink(CLINIC_PHONE);
        $phone1_display = $this->e(CLINIC_PHONE);
        $phone2 = defined('CLINIC_PHONE_2') ? CLINIC_PHONE_2 : '';
        $phone2_link = $phone2 ? $this->phoneLink($phone2) : '';
        $logo_src = $this->e(bioinmed_versioned_asset_path('/public/images/brand/main-logotype.png'));
        $second_phone_footer = $phone2_link
            ? '<a href="tel:' . $phone2_link . '" class="block text-sm font-semibold text-[#0a293c] hover:text-[#1977b2] transition-colors">' . $this->e($phone2) . '</a>'
            : '';

        return <<<HTML
        <footer class="bg-[#e4f1fa] border-t-2 border-[#1977b2]">
            <div class="mx-auto max-w-6xl px-6 md:px-10 py-12 md:py-16">
                <!-- Верхняя часть подвала -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
                    <!-- Логотип и описание -->
                    <div class="md:col-span-1">
                        <div class="mb-4">
                            <img src="{$logo_src}" alt="БИОИНМЕД" class="h-16 mb-3" loading="lazy" decoding="async">
                        </div>
                        <p class="text-[0.96rem] text-[#0a293c] leading-relaxed">
                            Интегративная и восстановительная медицина с персональным маршрутом лечения для каждого пациента.
                        </p>
                        <div class="mt-4 flex gap-3">
                            <a href="{$vk}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center text-[#2787f5] hover:text-[#1f6fd0] transition-colors" title="ВКонтакте" aria-label="ВКонтакте">
                                <i class="fa-brands fa-vk translate-x-[1px] text-[1.82rem] leading-none" aria-hidden="true"></i>
                            </a>
                            <a href="{$max}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center transition-opacity hover:opacity-85" title="MAX" aria-label="MAX">
                                <img src="{$max_icon_src}" alt="MAX" class="h-[1.72rem] w-auto" loading="lazy" decoding="async">
                            </a>
                            <a href="{$telegram}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center text-[#27a7e7] hover:text-[#1c8fca] transition-colors" title="Telegram" aria-label="Telegram">
                                <i class="fa-brands fa-telegram text-[1.82rem] leading-none" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Услуги -->
                    <div>
                        <h4 class="text-[0.96rem] font-bold uppercase tracking-[0.12em] text-[#1977b2] mb-4">Услуги</h4>
                        <ul class="space-y-2">
                            <li><a href="/services/hobilect-diagnostics" class="text-[0.96rem] text-[#0a293c] hover:text-[#1977b2] transition-colors">Диагностика HABILECT</a></li>
                            <li><a href="/services/musculoskeletal-program" class="text-[0.96rem] text-[#0a293c] hover:text-[#1977b2] transition-colors">Восстановление опорно-двигательного аппарата</a></li>
                            <li><a href="/services/osteopathy" class="text-[0.96rem] text-[#0a293c] hover:text-[#1977b2] transition-colors">Остеопатия</a></li>
                            <li><a href="/services/reflexotherapy" class="text-[0.96rem] text-[#0a293c] hover:text-[#1977b2] transition-colors">Рефлексотерапия</a></li>
                            <li><a href="/prices" class="text-[0.96rem] text-[#0a293c] hover:text-[#1977b2] transition-colors font-semibold">Все услуги и цены →</a></li>
                        </ul>
                    </div>

                    <!-- Компания -->
                    <div>
                        <h4 class="text-[0.96rem] font-bold uppercase tracking-[0.12em] text-[#1977b2] mb-4">Компания</h4>
                        <ul class="space-y-2">
                            <li><a href="/about" class="text-[0.96rem] text-[#0a293c] hover:text-[#1977b2] transition-colors">О клинике</a></li>
                            <li><a href="/doctors" class="text-[0.96rem] text-[#0a293c] hover:text-[#1977b2] transition-colors">Профессиональная команда</a></li>
                            <li><a href="/prices" class="text-[0.96rem] text-[#0a293c] hover:text-[#1977b2] transition-colors">Прайс-лист</a></li>
                            <li><a href="/privacy" class="text-[0.96rem] text-[#0a293c] hover:text-[#1977b2] transition-colors">Политика конфиденциальности</a></li>
                            <li><a href="/user-agreement" class="text-[0.96rem] text-[#0a293c] hover:text-[#1977b2] transition-colors">Пользовательское соглашение</a></li>
                            <li><a href="/#contact" class="text-[0.96rem] text-[#0a293c] hover:text-[#1977b2] transition-colors">Контакты</a></li>
                        </ul>
                    </div>

                    <!-- Контакты -->
                    <div>
                        <h4 class="text-[0.96rem] font-bold uppercase tracking-[0.12em] text-[#1977b2] mb-4">Контакты</h4>
                        <div class="space-y-3">
                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-[#1977b2] mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <div>
                                    <p class="text-[0.96rem] font-semibold text-[#0a293c]">{$this->e(CLINIC_ADDRESS)}</p>
                                    <p class="text-[0.84rem] text-[#0a293c]">{$this->e(CLINIC_METRO)}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-[#1977b2] mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <a href="mailto:{$this->e(CLINIC_EMAIL)}" class="text-[0.96rem] font-semibold text-[#0a293c] hover:text-[#1977b2] transition-colors">
                                    {$this->e(CLINIC_EMAIL)}
                                </a>
                            </div>
                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-[#1977b2] mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <div class="space-y-1">
                                    <a href="tel:{$phone}" class="block text-[0.96rem] font-semibold text-[#0a293c] hover:text-[#1977b2] transition-colors">
                                        {$phone1_display}
                                    </a>
                                    {$second_phone_footer}
                                </div>
                            </div>
                            <div class="flex items-start gap-2 pt-2">
                                <svg class="w-5 h-5 text-[#1977b2] mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-[0.96rem] text-[#0a293c]">
                                    <strong>{$this->e(CLINIC_HOURS)}</strong>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Нижняя часть подвала -->
                <div class="border-t border-[#dce8f5] pt-5">
                    <p class="text-[0.84rem] leading-relaxed text-[#0a293c]">
                        © 2026 <strong>КЛИНИКА БИОИНМЕД</strong> — интегративная и восстановительная медицина. Все права защищены.
                    </p>
                </div>
            </div>
        </footer>
        <style>
            #clientixAppointmentButton { display:none !important; }
        </style>
        <script type="text/javascript" src="https://klientiks.ru/js/online/clientixWidget.js"></script>
        <script type="text/javascript">
            (function initBioinmedBookingUi() {
                if (window.__bioinmedBookingUiReady) {
                    return;
                }
                window.__bioinmedBookingUiReady = true;

                function initQuickBookingWidget() {
                    if (window.__clientixWidgetBooted || !window.clientixWidget || typeof window.clientixWidget.load !== 'function') {
                        return;
                    }
                    window.__clientixWidgetBooted = true;
                    window.clientixWidget.load({
                        baseUrl: 'https://klientiks.ru',
                        alias: '/app2/BIOINMED?awaiting_list=true',
                        text: 'Записаться онлайн',
                        color: '#1977b2'
                    });
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initQuickBookingWidget, { once: true });
                } else {
                    initQuickBookingWidget();
                }

                function setStatus(form, type, message) {
                    var status = form ? form.querySelector('.js-callback-status') : null;
                    if (!status) {
                        return;
                    }
                    if (!message) {
                        status.className = 'js-callback-status hidden rounded-2xl px-3 py-2 text-[0.82rem] leading-relaxed';
                        status.textContent = '';
                        return;
                    }
                    status.className = 'js-callback-status rounded-2xl px-3 py-2 text-[0.82rem] leading-relaxed ' + (type === 'success'
                        ? 'bg-[#ecfbf3] text-[#1f7a46]'
                        : 'bg-[#fff4f4] text-[#b44545]');
                    status.textContent = message;
                }

                function hydrateFormContext(form) {
                    if (!form) {
                        return;
                    }
                    var pageTitleField = form.querySelector('input[name="page_title"]');
                    var pageUrlField = form.querySelector('input[name="page_url"]');
                    if (pageTitleField) {
                        pageTitleField.value = document.title || '';
                    }
                    if (pageUrlField) {
                        pageUrlField.value = window.location.href || '';
                    }
                }

                function syncPhonePlaceholder(input) {
                    if (!input) {
                        return;
                    }
                    var defaultPlaceholder = input.getAttribute('data-placeholder-default') || 'Ваш телефон';
                    var activePlaceholder = input.getAttribute('data-placeholder-active') || '+7 (___) ___-__-__';
                    input.placeholder = input.value ? activePlaceholder : defaultPlaceholder;
                }

                var specialOfferModal = document.getElementById('special-offer-callback-modal');
                var specialOfferBodyOverflow = '';

                function openSpecialOfferModal() {
                    if (!specialOfferModal) {
                        return;
                    }
                    specialOfferBodyOverflow = document.body.style.overflow || '';
                    specialOfferModal.classList.remove('hidden');
                    specialOfferModal.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                    var phoneInput = specialOfferModal.querySelector('.js-callback-phone');
                    if (phoneInput) {
                        window.requestAnimationFrame(function() {
                            phoneInput.focus();
                        });
                    }
                }

                function closeSpecialOfferModal() {
                    if (!specialOfferModal) {
                        return;
                    }
                    specialOfferModal.classList.add('hidden');
                    specialOfferModal.classList.remove('flex');
                    document.body.style.overflow = specialOfferBodyOverflow;
                }

                document.querySelectorAll('[data-special-offer-open]').forEach(function(button) {
                    button.addEventListener('click', openSpecialOfferModal);
                });

                document.querySelectorAll('[data-special-offer-close]').forEach(function(button) {
                    button.addEventListener('click', closeSpecialOfferModal);
                });

                if (specialOfferModal) {
                    specialOfferModal.addEventListener('click', function(event) {
                        if (event.target === specialOfferModal) {
                            closeSpecialOfferModal();
                        }
                    });

                    document.addEventListener('keydown', function(event) {
                        if (event.key === 'Escape' && !specialOfferModal.classList.contains('hidden')) {
                            closeSpecialOfferModal();
                        }
                    });
                }

                document.querySelectorAll('.js-callback-phone').forEach(function(input) {
                    syncPhonePlaceholder(input);
                    input.addEventListener('focus', function() {
                        input.placeholder = input.getAttribute('data-placeholder-active') || '+7 (___) ___-__-__';
                    });
                    input.addEventListener('input', function() {
                        syncPhonePlaceholder(input);
                    });
                    input.addEventListener('blur', function() {
                        syncPhonePlaceholder(input);
                    });
                });

                document.querySelectorAll('.js-callback-form').forEach(function(form) {
                    if (form.dataset.callbackReady === '1') {
                        return;
                    }
                    form.dataset.callbackReady = '1';

                    var submit = form.querySelector('.js-callback-submit');
                    hydrateFormContext(form);

                    var phoneInput = form.querySelector('.js-callback-phone');
                    if (phoneInput) {
                        syncPhonePlaceholder(phoneInput);
                    }

                    form.addEventListener('submit', function(event) {
                        event.preventDefault();
                        hydrateFormContext(form);
                        setStatus(form, '', '');

                        var formData = new FormData(form);
                        if (submit) {
                            submit.disabled = true;
                            submit.dataset.loadingText = submit.textContent || '';
                            submit.textContent = 'Отправляем...';
                        }

                        fetch(form.getAttribute('action') || '/callback-request.php', {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData
                        })
                            .then(function(response) {
                                return response.json().catch(function() {
                                    return {
                                        success: false,
                                        message: 'Не удалось обработать ответ сервера.'
                                    };
                                });
                            })
                            .then(function(payload) {
                                if (!payload || payload.success !== true) {
                                    throw new Error(payload && payload.message ? payload.message : 'Не удалось отправить заявку.');
                                }
                                setStatus(form, 'success', payload.message || 'Заявка отправлена.');
                                form.reset();
                                if (phoneInput) {
                                    syncPhonePlaceholder(phoneInput);
                                }
                            })
                            .catch(function(error) {
                                setStatus(form, 'error', error && error.message ? error.message : 'Не удалось отправить заявку.');
                            })
                            .finally(function() {
                                if (submit) {
                                    submit.disabled = false;
                                    submit.textContent = submit.dataset.loadingText || submit.textContent;
                                }
                            });
                    });
                });
            })();
        </script>
        HTML;
    }
}
?>
