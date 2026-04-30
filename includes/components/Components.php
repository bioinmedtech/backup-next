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
            ? '<p class="mt-2.5 max-w-2xl text-[0.88rem] leading-relaxed text-[#355b89]">' . $this->e($subtitle) . '</p>'
            : '';

        return <<<HTML
        <div class="mb-7">
            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.26em] text-[#2fbdef]">{$this->e($eyebrow)}</p>
            <h2 class="mt-1.5 text-[1.35rem] font-bold leading-tight text-[#0f2749] md:text-[1.6rem]">{$this->e($title)}</h2>
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
        $is_services = ($current_path === '/services' || strpos($current_path, '/services/') === 0 || $current_path === '/service.php');
        $is_doctors = ($current_path === '/doctors' || strpos($current_path, '/doctors/') === 0 || $current_path === '/doctor.php');
        $is_prices = ($current_path === '/prices' || $current_path === '/prices.php');

        $desktop_link_class = function ($is_active) {
            if ($is_active) {
                return 'is-active text-[#2fbdef] border-b-2 border-transparent';
            }
            return 'text-[#173b64] border-b-2 border-transparent hover:text-[#2fbdef]';
        };
        $mobile_link_attr = function ($is_active) {
            return $is_active ? ' class="is-active" aria-current="page"' : '';
        };

        $desktop_about_class = $desktop_link_class($is_home);
        $desktop_services_class = $desktop_link_class($is_services);
        $desktop_doctors_class = $desktop_link_class($is_doctors);
        $desktop_reviews_class = $desktop_link_class(false);
        $desktop_faq_class = $desktop_link_class(false);
        $desktop_prices_class = $desktop_link_class($is_prices);
        $desktop_contacts_class = $desktop_link_class(false);

        $desktop_about_aria = $is_home ? ' aria-current="page"' : '';
        $desktop_services_aria = $is_services ? ' aria-current="page"' : '';
        $desktop_doctors_aria = $is_doctors ? ' aria-current="page"' : '';
        $desktop_prices_aria = $is_prices ? ' aria-current="page"' : '';

        $mobile_about_attr = $mobile_link_attr($is_home);
        $mobile_services_attr = $mobile_link_attr($is_services);
        $mobile_doctors_attr = $mobile_link_attr($is_doctors);
        $mobile_prices_attr = $mobile_link_attr($is_prices);
        $mobile_services_summary_attr = $is_services ? ' class="is-active"' : '';
        $mobile_services_details_open = $is_services ? ' open' : '';

        $second_phone = $phone_2 !== ''
            ? '<a href="tel:' . $phone_2_link . '" class="mt-0.5 block whitespace-nowrap text-[0.81rem] font-medium leading-tight text-[#133b63] hover:text-[#2fbdef] md:text-[0.84rem]">' . $phone_2 . '</a>'
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

        return <<<HTML
        <header id="site-header" class="z-50 border-b border-[#d7e5f1] bg-[#f7fbff] lg:bg-[#f7fbff]/98 lg:backdrop-blur-md">
            <!-- ─── MOBILE HEADER ─── (hidden on lg+ via CSS) -->
            <div id="mob-header-bar">
                <!-- Row 1: logo + phone + burger -->
                <div class="flex items-center justify-between px-4 py-2.5">
                    <a href="/" class="inline-flex items-center">
                        <img src="/public/images/brand/main-logotype.png" alt="БИОИНМЕД" class="h-10 w-auto" loading="eager">
                    </a>
                    <div class="flex items-center gap-2">
                        <a href="tel:{$phone_1_link}" aria-label="Позвонить" class="flex h-9 w-9 items-center justify-center rounded-full border border-[#b9d7ef] bg-white text-[#2fbdef]">
                            <i class="fa-solid fa-phone text-[0.78rem]" aria-hidden="true"></i>
                        </a>
                        <button id="mob-toggle" onclick="toggleMobMenu()" aria-label="Меню" aria-expanded="false" class="flex h-9 w-9 items-center justify-center rounded-full border border-[#c9dcee] bg-white text-[#2fbdef]">
                            <i id="mob-icon" class="fa-solid fa-bars text-[0.82rem]" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <!-- Row 2: address — always visible, critical -->
                <div class="border-t border-[#e4eef7] bg-[#f0f7fd] px-4 py-2 text-[#173b64]">
                    <div class="flex items-start justify-between gap-3">
                        <div class="leading-tight">
                            <p class="text-[0.82rem] font-semibold">{$this->e(CLINIC_ADDRESS)}</p>
                            <p class="mt-0.5 text-[0.76rem] font-medium text-[#2a5894]">{$this->e(CLINIC_METRO)}</p>
                        </div>
                        <a href="{$map_url}" target="_blank" rel="noreferrer noopener" class="shrink-0 inline-flex items-center gap-1 rounded-full border border-[#c7dbed] bg-white px-2.5 py-1 text-[0.68rem] font-medium text-[#2fbdef] hover:text-[#1fb3d8]">
                            <i class="fa-solid fa-location-dot text-[0.58rem] text-[#2fbdef]" aria-hidden="true"></i>
                            На карте
                        </a>
                    </div>
                </div>
            </div>

            <!-- ─── DESKTOP HEADER ─── (hidden below lg) -->
            <div class="hidden lg:block">
                <div class="mx-auto max-w-6xl px-6 pt-2 md:px-10">
                    <div class="grid gap-2 pb-2.5 lg:grid-cols-[168px_1.05fr_0.9fr_0.74fr_168px] lg:items-start">
                        <a href="/" class="inline-flex items-center">
                            <img src="/public/images/brand/main-logotype.png" alt="БИОИНМЕД" class="h-12 w-auto" loading="eager">
                        </a>

                        <div class="pt-1 leading-tight text-[#173b64]">
                            <p class="text-[0.85rem] font-medium md:text-[0.88rem]">{$this->e(CLINIC_ADDRESS)}</p>
                            <p class="mt-0.5 text-[0.83rem] font-medium text-[#24588d] md:text-[0.86rem]">{$this->e(CLINIC_METRO)}</p>
                            <div class="mt-1.5">
                                <a href="{$map_url}" target="_blank" rel="noreferrer noopener" class="inline-flex items-center gap-1 rounded-full border border-[#c7dbed] bg-white px-2 py-1 text-[0.7rem] font-medium text-[#2fbdef] hover:border-[#a8cbe6] hover:text-[#1fb3d8]">
                                    <i class="fa-solid fa-location-dot text-[0.58rem] text-[#2fbdef]" aria-hidden="true"></i>
                                    На карте
                                </a>
                            </div>
                        </div>

                        <div class="pt-1 leading-tight text-[#173b64]">
                            <p class="text-[0.86rem] font-medium">{$this->e(CLINIC_HOURS)}</p>
                            <p class="mt-0.5 text-[0.72rem] font-medium text-[#2fbdef]">Приём по предварительной записи</p>
                        </div>

                        <div class="flex items-start gap-2.5 pt-1 text-[#173b64]">
                            <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-[#b9d7ef] text-[#2fbdef]">
                                <i class="fa-solid fa-phone-volume text-[0.68rem]" aria-hidden="true"></i>
                            </div>
                            <div class="pt-[1px]">
                                <a href="tel:{$phone_1_link}" class="block whitespace-nowrap text-[0.81rem] font-medium leading-tight text-[#133b63] hover:text-[#2fbdef] md:text-[0.84rem]">{$phone_1}</a>
                                {$second_phone}
                                <p class="mt-0.5 text-[0.68rem] font-medium text-[#2fbdef]">Запись по телефону ежедневно</p>
                            </div>
                        </div>

                        <div class="pt-1 text-right">
                            <a href="{$booking_url}" class="inline-flex h-10 w-auto min-w-[156px] items-center justify-center rounded-full bg-[#2fbdef] px-4 text-[0.88rem] font-medium text-white shadow-[0_10px_24px_rgba(47,189,239,0.2)] transition hover:bg-[#1fb3d8]">
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
                    <nav class="menu-strip flex items-center gap-6 overflow-x-auto whitespace-nowrap text-[0.86rem] font-medium text-[#173b64] lg:overflow-visible">
                        <a href="/#advantages" class="{$desktop_about_class}"{$desktop_about_aria}>О клинике</a>
                        {$desktop_services_dropdown}
                        <a href="/doctors" class="{$desktop_doctors_class}"{$desktop_doctors_aria}>Специалисты</a>
                        <a href="/#reviews" class="{$desktop_reviews_class}">Отзывы</a>
                        <a href="/#faq" class="{$desktop_faq_class}">Вопросы</a>
                        <a href="/prices" class="{$desktop_prices_class}"{$desktop_prices_aria}>Цены</a>
                        <a href="/#contact" class="{$desktop_contacts_class}">Контакты</a>
                    </nav>
                    <div class="flex shrink-0 items-center gap-2">
                        <a href="{$telegram_url}" target="_blank" rel="noreferrer noopener" aria-label="Telegram" class="group flex h-8 w-8 items-center justify-center rounded-full border border-[#c9dcee] bg-white text-[#2fbdef] shadow-[0_4px_12px_rgba(47,189,239,0.08)] transition hover:-translate-y-0.5 hover:bg-[#f2f8fd] hover:text-[#1fb3d8]">
                            <i class="fa-brands fa-telegram text-[0.8rem]" aria-hidden="true"></i>
                        </a>
                        <a href="{$vk_url}" target="_blank" rel="noreferrer noopener" aria-label="VK" class="group flex h-8 w-8 items-center justify-center rounded-full border border-[#c9dcee] bg-white text-[#2fbdef] shadow-[0_4px_12px_rgba(47,189,239,0.08)] transition hover:-translate-y-0.5 hover:bg-[#f2f8fd] hover:text-[#1fb3d8]">
                            <i class="fa-brands fa-vk text-[0.76rem]" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Backdrop and drawer are OUTSIDE <header> to avoid backdrop-filter stacking context issues -->
        <div id="mob-backdrop" onclick="closeMobMenu()"></div>
        <div id="mob-menu">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #dce8f3;">
                <img src="/public/images/brand/main-logotype.png" alt="БИОИНМЕД" style="height:36px;width:auto;">
                <button onclick="closeMobMenu()" aria-label="Закрыть меню" style="display:flex;width:32px;height:32px;align-items:center;justify-content:center;border-radius:9999px;border:1px solid #dce8f3;background:transparent;cursor:pointer;color:#355b89;">
                    <i class="fa-solid fa-xmark" style="font-size:0.9rem;" aria-hidden="true"></i>
                </button>
            </div>
            <div style="padding:12px 20px;background:#eef5fc;border-bottom:1px solid #dce8f3;">
                <p style="font-size:0.81rem;font-weight:600;color:#173b64;margin:0;">{$this->e(CLINIC_ADDRESS)}</p>
                <p style="font-size:0.75rem;color:#2a5894;margin:4px 0 0;">{$this->e(CLINIC_METRO)}</p>
                <p style="font-size:0.73rem;color:#2d86ca;margin:2px 0 0;">{$this->e(CLINIC_HOURS)}</p>
            </div>
            <nav id="mob-nav">
                <a href="/#advantages" onclick="closeMobMenu()"{$mobile_about_attr}>О клинике</a>
                {$mobile_services_dropdown}
                <a href="/doctors" onclick="closeMobMenu()"{$mobile_doctors_attr}>Специалисты</a>
                <a href="/#reviews" onclick="closeMobMenu()">Отзывы</a>
                <a href="/#faq" onclick="closeMobMenu()">Вопросы</a>
                <a href="/prices" onclick="closeMobMenu()"{$mobile_prices_attr}>Цены</a>
                <a href="/#contact" onclick="closeMobMenu()">Контакты</a>
            </nav>
            <div style="margin-top:auto;border-top:1px solid #dce8f3;padding:16px 20px;display:flex;flex-direction:column;gap:12px;">
                <a href="tel:{$phone_1_link}" style="display:flex;align-items:center;gap:10px;font-size:0.88rem;font-weight:600;color:#133b63;text-decoration:none;">
                    <i class="fa-solid fa-phone-volume" style="color:#2fbdef;" aria-hidden="true"></i>
                    {$phone_1}
                </a>
                <a href="{$booking_url}" style="display:flex;height:44px;align-items:center;justify-content:center;border-radius:9999px;background:#2fbdef;font-size:0.88rem;font-weight:500;color:#fff;text-decoration:none;">
                    Онлайн запись
                </a>
                <div style="display:flex;gap:8px;">
                    <a href="{$telegram_url}" target="_blank" rel="noreferrer noopener" aria-label="Telegram" style="display:flex;width:32px;height:32px;align-items:center;justify-content:center;border-radius:9999px;border:1px solid #c9dcee;background:#fff;color:#2fbdef;text-decoration:none;">
                        <i class="fa-brands fa-telegram" style="font-size:0.8rem;" aria-hidden="true"></i>
                    </a>
                    <a href="{$vk_url}" target="_blank" rel="noreferrer noopener" aria-label="VK" style="display:flex;width:32px;height:32px;align-items:center;justify-content:center;border-radius:9999px;border:1px solid #c9dcee;background:#fff;color:#2fbdef;text-decoration:none;">
                        <i class="fa-brands fa-vk" style="font-size:0.76rem;" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
        <style>
            #mob-header-bar{display:block}
            @media(min-width:1024px){#mob-header-bar{display:none}}
            #mob-backdrop{display:none;position:fixed;inset:0;z-index:51;background:rgba(0,0,0,.35)}
            #mob-backdrop.open{display:block}
            #mob-menu{position:fixed;top:0;right:0;bottom:0;z-index:52;width:min(80vw,320px);display:flex;flex-direction:column;background:#f7fbff;box-shadow:-4px 0 32px rgba(10,30,60,.18);transform:translateX(100%);transition:transform .28s cubic-bezier(.4,0,.2,1)}
            @media(min-width:1024px){#mob-menu{display:none!important}}
            #mob-menu.open{transform:translateX(0)}
            #mob-nav{flex:1;min-height:0;overflow-y:auto;display:flex;flex-direction:column;padding:4px 20px}
            #mob-nav a{display:block;padding:12px 0;font-size:.9rem;font-weight:500;color:#1b3f6e;text-decoration:none;border-bottom:1px solid #e8f0f8}
            #mob-nav a:last-child{border-bottom:none}
            #mob-nav a:hover{color:#2fbdef}
            #mob-nav details{border-bottom:1px solid #e8f0f8}
            #mob-nav details>summary{list-style:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:8px;padding:12px 0;font-size:.9rem;font-weight:500;color:#1b3f6e}
            #mob-nav details>summary::-webkit-details-marker{display:none}
            #mob-nav details>summary i{font-size:.66rem;color:#6f95ba;transition:transform .2s ease}
            #mob-nav details[open]>summary i{transform:rotate(180deg)}
            .mob-subnav{margin:0 0 8px;border-left:2px solid #dce8f3;padding-left:10px}
            .mob-nav-subgroup{border-bottom:none!important}
            .mob-nav-subgroup>summary{padding:10px 0;font-size:.82rem!important;font-weight:600!important;color:#2a5a94!important}
            .mob-subsubnav a{display:flex!important;align-items:baseline;justify-content:space-between;gap:8px;border-bottom:1px dashed #e3edf8!important;padding:9px 0!important;font-size:.78rem!important;font-weight:500!important;color:#1b3f6e!important;text-decoration:none}
            .mob-subsubnav a:last-child{border-bottom:none!important}
            .services-nav-item{position:static}
            .services-nav-item button{line-height:1}
            .desktop-menu-bar{position:sticky;top:0;z-index:80;border-bottom:1px solid #dbe8f3;background:#f7fbff}
            .desktop-menu-row{position:relative;background:#f7fbff}
            .menu-strip{scrollbar-width:none}
            .menu-strip::-webkit-scrollbar{display:none}
            .menu-strip a{display:inline-flex;align-items:center;padding-bottom:2px;transition:color .2s ease,border-color .2s ease}
            .menu-strip a.is-active{font-weight:700}
            #mob-nav a.is-active{color:#2fbdef;font-weight:700}
            #mob-nav details>summary.is-active{color:#2fbdef;font-weight:700}
            #mob-nav details>summary.is-active i{color:#2fbdef}
            .mob-subnav a.is-active{color:#2fbdef!important;font-weight:700!important}
        </style>
        <script>
            function toggleMobMenu(){var m=document.getElementById('mob-menu');if(m.classList.contains('open')){closeMobMenu();}else{m.classList.add('open');document.getElementById('mob-backdrop').classList.add('open');document.getElementById('mob-icon').className='fa-solid fa-xmark';document.getElementById('mob-toggle').setAttribute('aria-expanded','true');document.body.style.overflow='hidden';}}
            function closeMobMenu(){document.getElementById('mob-menu').classList.remove('open');document.getElementById('mob-backdrop').classList.remove('open');document.getElementById('mob-icon').className='fa-solid fa-bars';document.getElementById('mob-toggle').setAttribute('aria-expanded','false');document.body.style.overflow='';}
            document.addEventListener('keydown',function(e){if(e.key==='Escape')closeMobMenu();});
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
                    input.placeholder=config.placeholder;
                    input.type='tel';
                    input.inputMode='tel';
                    input.autocomplete='tel';

                    function syncPlaceholderByValue(rawValue){
                        var digits=getDigitsOnly(rawValue);
                        var activeConfig=digits ? detectCountry(digits) : PHONE_CONFIGS['7'];
                        input.placeholder=activeConfig.placeholder;
                    }

                    input.addEventListener('focus',function(){
                        // При фокусе на пустое поле вставляем +7
                        if(this.value===''){
                            this.value='+7';
                            // Переместить курсор в конец
                            this.setSelectionRange(this.value.length,this.value.length);
                        }
                        syncPlaceholderByValue(this.value);
                    });

                    input.addEventListener('input',function(){
                        var currentValue=this.value;
                        var digits=getDigitsOnly(currentValue);
                        
                        // Если удалили всё после +, позволяем вводить новый код
                        if(currentValue==='+'||currentValue===''){
                            this.value='+';
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
        $hero_image = defined('HERO_IMAGE') ? $this->e(HERO_IMAGE) : '/public/images/team/kostromina_i_v.png';
        $chief_name = 'Инна Викторовна Костромина';
        $chief_title = 'Главный врач клиники';

        if (!empty($GLOBALS['doctors'][0]) && is_array($GLOBALS['doctors'][0])) {
            $chief_name = $this->e($GLOBALS['doctors'][0]['name'] ?? $chief_name);
            $chief_title = $this->e($GLOBALS['doctors'][0]['title'] ?? $chief_title);
        }

        return <<<HTML
        <section class="hero-section relative box-border overflow-hidden border-b border-[#dbe7f2] bg-[radial-gradient(circle_at_top_left,#ffffff_0%,#edf6fd_36%,#deedf8_100%)] lg:h-[calc(100svh-var(--header-height,0px))]" style="height:calc(100svh - var(--header-height, 0px));min-height:calc(100svh - var(--header-height, 0px));">
            <div class="pointer-events-none absolute inset-0 opacity-60 [background-image:linear-gradient(rgba(47,189,239,0.06)_1px,transparent_1px),linear-gradient(90deg,rgba(47,189,239,0.06)_1px,transparent_1px)] [background-size:32px_32px]"></div>
            <div class="pointer-events-none absolute -left-20 top-10 h-52 w-52 rounded-full bg-[#2fbdef24] blur-3xl md:-left-32 md:h-72 md:w-72"></div>
            <div class="pointer-events-none absolute right-0 top-0 h-64 w-64 rounded-full bg-[#0f27490d] blur-3xl md:h-96 md:w-96"></div>
            <div class="pointer-events-none absolute bottom-0 left-1/2 h-48 w-48 -translate-x-1/2 rounded-full bg-[#2fbdef14] blur-3xl md:h-72 md:w-72"></div>

            <div class="relative mx-auto flex max-w-6xl items-center px-6 py-4 md:px-10 md:py-5 lg:h-full lg:py-2">
                <div class="w-full lg:grid lg:grid-cols-[minmax(0,1fr)_320px] lg:items-start lg:gap-4">
                    <div class="max-w-3xl lg:flex lg:h-full lg:min-h-0 lg:flex-col lg:justify-center">
                            <p class="text-[0.7rem] font-semibold uppercase tracking-[0.16em] text-[#2a5a94]">Клиника интегративной медицины</p>
                            <h1 class="mt-2 max-w-3xl text-[1.4rem] font-bold leading-[1.02] text-[#0f2749] sm:text-[1.76rem] md:text-[1.92rem] lg:text-[2rem]">
                                Медицина - это искусство выздоровления
                            </h1>
                            <p class="mt-1.5 max-w-xl text-[0.82rem] font-semibold leading-snug text-[#173b64] md:text-[0.88rem]">
                                С нами выздоравливать легко.
                            </p>
                            <p class="mt-1.5 max-w-xl text-[0.76rem] leading-relaxed text-[#4a6f96] md:text-[0.82rem]">
                                Подберём специалиста, проведём диагностику и выстроим понятный маршрут восстановления.
                            </p>

                            <div class="mt-2.5 flex max-w-xl items-center gap-3 rounded-[1.15rem] border border-[#d6e4f0] bg-white p-2.5 shadow-[0_10px_22px_rgba(10,43,80,0.05)]">
                                <div class="h-14 w-14 shrink-0 overflow-hidden rounded-2xl border border-[#c9d9ed] bg-[linear-gradient(180deg,#edf6fd_0%,#dcebf7_100%)] md:h-16 md:w-16">
                                    <img src="{$hero_image}" alt="{$chief_name}" class="h-full w-full object-cover object-top" loading="eager">
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[0.62rem] font-semibold uppercase tracking-[0.14em] text-[#2a5a94]">{$chief_title}</p>
                                    <p class="mt-0.5 text-[0.9rem] font-bold leading-tight text-[#0f2749] md:text-[0.98rem]">{$chief_name}</p>
                                    <p class="mt-1 text-[0.68rem] leading-relaxed text-[#4a6f96] md:text-[0.72rem]">Путь к восстановлению должен быть понятным и системным.</p>
                                </div>
                            </div>

                            <div class="mt-2.5 flex w-full flex-col gap-2 sm:max-w-xl sm:flex-row sm:flex-wrap">
                                <div class="flex w-full items-center gap-2 rounded-full border border-[#d6e4f0] bg-white px-3 py-1.5 shadow-[0_10px_22px_rgba(10,43,80,0.05)] sm:inline-flex sm:w-auto">
                                    <i class="fa-solid fa-wave-square text-[0.72rem] text-[#2fbdef]" aria-hidden="true"></i>
                                    <p class="text-[0.72rem] font-semibold text-[#214a7f]">Диагностика первопричин</p>
                                </div>
                                <div class="flex w-full items-center gap-2 rounded-full border border-[#d6e4f0] bg-white px-3 py-1.5 shadow-[0_10px_22px_rgba(10,43,80,0.05)] sm:inline-flex sm:w-auto">
                                    <i class="fa-solid fa-user-doctor text-[0.72rem] text-[#2fbdef]" aria-hidden="true"></i>
                                    <p class="text-[0.72rem] font-semibold text-[#214a7f]">Врачи с опытом 20-30+ лет</p>
                                </div>
                            </div>

                    </div>

                    <div class="mt-3 w-full max-w-3xl rounded-[1.2rem] border border-[#d6e4f0] bg-white p-3 shadow-[0_18px_38px_rgba(10,43,80,0.09)] md:p-3.5 lg:mt-0 lg:max-w-none lg:self-center">
                        <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <h2 class="text-[0.92rem] font-bold text-[#0f2749] md:text-[1rem]">Записаться на приём</h2>
                                        <p id="hero-form-note" class="mt-1 text-[0.72rem] leading-relaxed text-[#4a6f96]">Оставьте номер, и мы свяжемся с вами.</p>
                                    </div>
                                </div>

                        <form class="mt-2.5" action="{$booking_url}" method="post" aria-describedby="hero-form-note">
                            <input type="hidden" name="source" value="homepage-hero">
                            <div class="space-y-2">
                                <div>
                                    <label for="hero-phone-input" class="mb-1 block text-[0.64rem] font-semibold uppercase tracking-[0.08em] text-[#4a6f96]">Номер телефона</label>
                                    <input
                                        type="tel"
                                        id="hero-phone-input"
                                        name="phone"
                                        autocomplete="tel"
                                        inputmode="tel"
                                        required
                                        placeholder="Ваш телефон"
                                        class="w-full rounded-full border border-[#d6e2ee] bg-[#f9fcff] px-4 py-2.5 text-[0.82rem] text-[#173f73] outline-none placeholder:text-[#8ca2b8] transition focus:border-[#2fbdef] focus:bg-white focus:ring-2 focus:ring-[#2fbdef]/20"
                                        aria-label="Введите номер телефона"
                                    />
                                </div>
                                <button
                                    type="submit"
                                    class="w-full rounded-full bg-[#2fbdef] px-6 py-2.5 text-[0.82rem] font-semibold text-white transition hover:bg-[#1fb3d8] active:bg-[#1597b9]"
                                >
                                    Перезвоните мне
                                </button>
                            </div>

                            <label class="mt-2 flex items-start gap-2 text-[0.63rem] leading-snug text-[#355b89]">
                                <input
                                    type="checkbox"
                                    required
                                    class="mt-0.5 h-4 w-4 shrink-0 cursor-pointer rounded border-[#89b9df] text-[#2fbdef]"
                                />
                                <span class="block pt-0.5">
                                    Я даю согласие с <a href="/privacy.php" class="text-[#2fbdef] underline-offset-2 hover:underline">политикой конфиденциальности</a> и <a href="/user-agreement.php" class="text-[#2fbdef] underline-offset-2 hover:underline">пользовательским соглашением</a>
                                </span>
                            </label>
                        </form>
                    </div>

                </div>
            </div>
        </section>
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
        <section class="border-b border-[#e6eef7] bg-white py-5 md:py-6">
            <div class="mx-auto max-w-6xl px-6 md:px-10">
                <ul class="grid grid-cols-2 divide-y divide-[#e6eef7] md:grid-cols-4 md:divide-x md:divide-y-0">
                    <!-- Stat 1: Experience -->
                    <li class="flex items-center gap-3 py-4 pr-4 md:px-7 md:py-0 md:first:pl-0">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#eaf4fc] text-[#2fbdef]" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 0 2-2h2a2 2 0 0 0 2 2m-6 9 2 2 4-4" />
                            </svg>
                        </span>
                        <div>
                            <div class="text-[1.35rem] font-bold leading-none text-[#0f2749] [font-variant-numeric:tabular-nums]">{$experience}</div>
                    </li>
                    <!-- Stat 2: Rating -->
                    <li class="flex items-center gap-3 py-4 pl-4 md:px-7 md:py-0">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#eaf4fc] text-[#2fbdef]" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                            </svg>
                        </span>
                        <div>
                            <div class="text-[1.35rem] font-bold leading-none text-[#0f2749] [font-variant-numeric:tabular-nums]">{$rating}</div>
                            <p class="mt-0.5 text-[0.64rem] font-semibold uppercase leading-tight tracking-wide text-[#4a6f96]">{$rating_desc}</p>
                        </div>
                    </li>
                    <!-- Stat 3: Medical directions -->
                    <li class="flex items-center gap-3 py-4 pr-4 md:px-7 md:py-0">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#eaf4fc] text-[#2fbdef]" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 1-6.23-.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21a48.309 48.309 0 0 1-8.135-.687c-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
                            </svg>
                        </span>
                        <div>
                            <div class="text-[1.35rem] font-bold leading-none text-[#0f2749] [font-variant-numeric:tabular-nums]">{$patients}</div>
                            <p class="mt-0.5 text-[0.64rem] font-semibold uppercase leading-tight tracking-wide text-[#4a6f96]">{$patients_desc}</p>
                        </div>
                    </li>
                    <!-- Stat 4: License -->
                    <li class="flex items-center gap-3 py-4 pl-4 md:px-7 md:py-0 md:last:pr-0">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#eaf4fc] text-[#2fbdef]" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                            </svg>
                        </span>
                        <div>
                            <div class="text-[1.15rem] font-bold leading-none text-[#0f2749]">{$license_text}</div>
                            <p class="mt-0.5 text-[0.64rem] font-semibold uppercase leading-tight tracking-wide text-[#4a6f96]">{$license_desc}</p>
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
        <section class="border-b border-[#e6eef7] bg-white py-10 md:py-14">
            <div class="mx-auto max-w-6xl px-6 md:px-10">
                <div class="grid gap-4 md:grid-cols-4">
                    <div class="overflow-hidden rounded-2xl border border-[#dce8f5]">
                        <img src="/public/images/content/about-company.jpg" alt="Клиника БИОИНМЕД" class="h-56 w-full object-cover" loading="lazy" />
                    </div>
                    <div class="overflow-hidden rounded-2xl border border-[#dce8f5]">
                        <img src="/public/images/team/kostromina.jpg" alt="Костромина Инна Викторовна" class="h-56 w-full object-cover" loading="lazy" />
                    </div>
                    <div class="overflow-hidden rounded-2xl border border-[#dce8f5]">
                        <img src="/public/images/team/ferencz.jpg" alt="Ференц Надежда Юрьевна" class="h-56 w-full object-cover" loading="lazy" />
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
    public function __construct($problems, $colors = []) {
        parent::__construct($colors);
        $this->data = $problems;
    }

    public function render() {
        if (empty($this->data)) {
            return '';
        }

        global $services, $service_aliases;

        $services_map = [];
        if (is_array($services)) {
            foreach ($services as $service) {
                $sid = trim((string)($service['id'] ?? ''));
                if ($sid !== '') {
                    $services_map[$sid] = true;
                }
            }
        }

        $solution_keyword_map = [
            'habilect' => 'hobilect-diagnostics',
            'hobilect' => 'hobilect-diagnostics',
            'хабилект' => 'hobilect-diagnostics',
            'остеопат' => 'osteopathy',
            'hilt' => 'hilt-therapy',
            'хилт' => 'hilt-therapy',
            'физиотерап' => 'fizioterapiya',
            'рефлекс' => 'acupuncture',
            'игло' => 'acupuncture',
            'психотерап' => 'psychotherapy',
            'психолог' => 'psychotherapy',
            'мануаль' => 'osteopathy',
            'афк' => 'physiotherapy-comprehensive',
            'биорезонанс' => 'chief-doctor-consultation',
            'гомеопат' => 'chief-doctor-consultation',
        ];

        $items_html = '';
        foreach ($this->data as $problem) {
            $solutions_html = '';
            $solutions_raw = (string)($problem['solutions'] ?? '');
            $solution_parts = preg_split('/\s*,\s*/u', $solutions_raw, -1, PREG_SPLIT_NO_EMPTY);

            foreach ($solution_parts as $solution_part) {
                $solution_text = trim((string)$solution_part);
                if ($solution_text === '') {
                    continue;
                }

                $needle = mb_strtolower($solution_text, 'UTF-8');
                $resolved_id = '';
                foreach ($solution_keyword_map as $keyword => $service_id) {
                    if (mb_strpos($needle, $keyword) !== false) {
                        $resolved_id = $service_id;
                        break;
                    }
                }

                if ($resolved_id !== '' && isset($service_aliases[$resolved_id])) {
                    $resolved_id = (string)$service_aliases[$resolved_id];
                }

                if ($resolved_id !== '' && isset($services_map[$resolved_id])) {
                    $solutions_html .= '<a href="/services/' . $this->e($resolved_id) . '" class="inline-flex items-center gap-1 rounded-full border border-[#c9dff1] bg-white px-2.5 py-1 text-[0.72rem] font-semibold text-[#2a5a94] hover:border-[#2fbdef] hover:text-[#2fbdef]">' . $this->e($solution_text) . '</a>';
                } else {
                    $solutions_html .= '<span class="inline-flex items-center gap-1 rounded-full border border-[#e1ecf7] bg-[#f8fcff] px-2.5 py-1 text-[0.72rem] font-semibold text-[#355b89]">' . $this->e($solution_text) . '</span>';
                }
            }

            $items_html .= <<<HTML
            <details class="group border-b border-[#e8f0f8] last:border-0">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3.5 md:px-6">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#fde8e8] text-[#d94f4f]" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/></svg>
                        </span>
                        <h3 class="text-[0.92rem] font-semibold leading-tight text-[#0f2749]">{$this->e($problem['title'])}</h3>
                    </div>
                    <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-[#d4e5f2] bg-[#f3f9ff] text-[#2a5a94] transition-transform group-open:rotate-180" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </span>
                </summary>
                <div class="px-4 pb-3.5 pt-0 md:px-6">
                    <div class="grid gap-2 rounded-xl bg-[#f5faff] p-3 sm:grid-cols-2">
                        <div>
                            <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-[#6b8fb5]">Описание</p>
                            <p class="mt-1 text-[0.82rem] leading-relaxed text-[#355b89]">{$this->e($problem['description'])}</p>
                        </div>
                        <div>
                            <p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-[#2fbdef]">Методы лечения в клинике</p>
                            <div class="mt-1 flex flex-wrap gap-1.5">{$solutions_html}</div>
                        </div>
                    </div>
                    <a href="/#contact" class="mt-2.5 inline-flex items-center gap-1.5 rounded-full border border-[#c6ddf2] bg-white px-3.5 py-1.5 text-[0.74rem] font-semibold text-[#2a5a94] hover:bg-[#ebf4ff]">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                        Записаться на консультацию
                    </a>
                </div>
            </details>
            HTML;
        }

        return <<<HTML
        <section id="problems" class="border-b border-[#e6eef7] bg-white py-12 md:py-16">
            <div class="mx-auto max-w-6xl px-6 md:px-10">
                {$this->sectionTitle('С какой проблемой обращаются', 'Найдите вашу ситуацию', 'Нажмите на свою проблему — покажем метод лечения, который применяем в клинике.')}
                <div class="overflow-hidden rounded-2xl border border-[#dce8f5] bg-white shadow-[0_10px_28px_rgba(10,43,80,0.07)]">
                    {$items_html}
                </div>
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
                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#eaf4fc] text-[#2fbdef]" aria-hidden="true">
                    <i class="fa-solid {$icon} text-[0.9rem]"></i>
                </span>
                <div>
                    <h3 class="text-[0.92rem] font-bold leading-tight text-[#0f2749]">{$this->e($advantage['title'])}</h3>
                    <p class="mt-1 text-[0.82rem] leading-relaxed text-[#4a6f96]">{$this->e($advantage['description'])}</p>
                </div>
            </li>
            HTML;
            $index++;
        }

        return <<<HTML
        <section id="advantages" class="border-b border-[#e6eef7] bg-[#f7fbff] py-12 md:py-16">
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

        return <<<HTML
        <section class="border-b border-[#e6eef7] bg-[#f6fbff] py-10 md:py-14">
            <div class="mx-auto max-w-6xl px-6 md:px-10">
                <div class="grid gap-8 rounded-3xl border border-[#d6e5f2] bg-white p-6 shadow-[0_18px_40px_rgba(6,29,60,0.08)] md:grid-cols-[0.85fr_1.15fr] md:p-8">
                    <img src="/public/images/team/{$this->e($this->data['image'])}" alt="{$this->e($this->data['name'])}" class="h-full max-h-[460px] w-full rounded-2xl object-cover" loading="lazy" />
                    <div>
                        <p class="text-[0.68rem] font-semibold uppercase tracking-[0.26em] text-[#2fbdef]">Экспертный подход</p>
                        <h2 class="mt-2 text-[1.35rem] font-bold leading-tight text-[#0f2749] md:text-[1.6rem]">{$this->e($this->data['name'])}</h2>
                        <p class="mt-1 text-[0.78rem] font-semibold uppercase tracking-[0.15em] text-[#4a6f96]">{$this->e($this->data['title'])}</p>
                        <p class="mt-5 text-base leading-relaxed text-[#355b89]">
                            В БИОИНМЕД каждый пациент получает не набор разрозненных процедур, а цельный лечебный маршрут:
                            диагностика причин, подбор метода, оценка динамики и коррекция тактики.
                        </p>
                        <div class="mt-5 rounded-2xl border border-[#dce8f5] bg-[#f8fcff] p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.13em] text-[#2a5a94]">Образовательная и управленческая роль</p>
                            <p class="mt-2 text-sm leading-relaxed text-[#214a7f]">{$leadership}</p>
                        </div>
                        <ul class="mt-6 space-y-3 text-sm text-[#214a7f]">
                            <li class="flex items-start gap-3">
                                <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#e3f2fc] text-[#2fbdef]"><i class="fa-solid fa-check text-xs" aria-hidden="true"></i></span>
                                <span>Комплексное ведение сложных хронических случаев</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#e3f2fc] text-[#2fbdef]"><i class="fa-solid fa-check text-xs" aria-hidden="true"></i></span>
                                <span>Интегративная схема лечения без избыточной медикаментозной нагрузки</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#e3f2fc] text-[#2fbdef]"><i class="fa-solid fa-check text-xs" aria-hidden="true"></i></span>
                                <span>Ежегодное обновление клинических протоколов и методик</span>
                            </li>
                        </ul>
                        <a href="/doctors/kostromina-inna-viktorovna" class="mt-6 inline-flex rounded-full bg-[#2fbdef] px-5 py-2.5 text-[0.84rem] font-semibold text-white hover:bg-[#1fb3d8]">Подробнее о специалисте</a>
                    </div>
                </div>
            </div>
        </section>
        HTML;
    }
}

class SpecialOffer extends Component {
    public function render() {
        $phone = $this->e(CLINIC_PHONE);
        $phone_link = $this->phoneLink(CLINIC_PHONE);
        return <<<HTML
        <section class="border-b border-[#e6eef7] bg-white py-10 md:py-12">
            <div class="mx-auto max-w-6xl px-6 md:px-10">
                <div class="overflow-hidden rounded-2xl border border-[#d8e7f5] bg-[linear-gradient(110deg,#ecf6ff_0%,#f7fcff_60%,#eaf7f5_100%)] px-6 py-6 md:px-8 md:py-7">
                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.26em] text-[#2fbdef]">Специальное предложение для новых пациентов</p>
                    <h2 class="mt-2 text-[1.2rem] font-bold leading-tight text-[#0f2749] md:text-[1.45rem]">Диагностика HABILECT + первичный приём врача</h2>
                    <p class="mt-2.5 max-w-2xl text-[0.86rem] leading-relaxed text-[#355b89]">
                        Начните знакомство с комплексной диагностики HABILECT и первичного приёма. Получите персональный план восстановления и рекомендации по лечению.
                    </p>
                    <div class="mt-4">
                        <a href="tel:{$phone_link}" class="inline-flex items-center gap-2 rounded-full bg-[#2fbdef] px-5 py-2.5 text-[0.88rem] font-semibold text-white hover:bg-[#1fb3d8]">
                            <i class="fa-solid fa-phone text-[0.78rem]" aria-hidden="true"></i>
                            Записаться на консультацию
                        </a>
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
            $cards_html .= <<<HTML
            <article class="min-w-[280px] max-w-[280px] shrink-0 overflow-hidden rounded-2xl border border-[#dce8f5] bg-white shadow-[0_10px_28px_rgba(9,39,72,0.08)] sm:min-w-[310px] sm:max-w-[310px] flex h-[560px] flex-col sm:h-[585px]">
                <img src="/public/images/team/{$this->e($doctor['image'])}" alt="{$this->e($doctor['name'])}" class="h-72 w-full object-cover" loading="lazy">
                <div class="flex flex-col flex-1 p-6">
                    <div class="flex-1">
                        <h3 class="text-lg font-bold leading-tight text-[#0f3463]" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{$this->e($doctor['name'])}</h3>
                        <p class="mt-1 text-xs font-semibold uppercase tracking-[0.12em] text-[#2a5a94]">{$this->e($doctor['title'])}</p>
                        <p class="mt-3 text-sm text-[#355b89]" style="display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">{$this->e($doctor['specialty'])}</p>
                        <p class="mt-2 text-sm font-semibold text-[#214a7f]">{$this->e($doctor['experience'])}</p>
                    </div>
                    <a href="{$doctor_link}" class="mt-auto w-full rounded-full bg-[#2fbdef] py-2.5 text-center text-xs font-semibold uppercase tracking-[0.08em] text-white hover:bg-[#1fb3d8]">Подробнее</a>
                </div>
            </article>
            HTML;
        }

        return <<<HTML
        <section id="doctors" class="border-b border-[#e6eef7] bg-white py-12 md:py-16">
            <div class="mx-auto max-w-6xl px-6 md:px-10">
                {$this->sectionTitle('Наша команда', 'Профессиональная команда специалистов', 'Познакомьтесь с врачами команды и перейдите в карточку специалиста для подробной информации.')}
                <div class="rounded-3xl border border-[#dce8f5] bg-[#f7fbff] p-4 sm:p-5">
                    <div class="mb-4 flex items-center justify-end gap-2">
                        <button type="button" class="doctor-slider-prev inline-flex h-10 w-10 items-center justify-center rounded-full border border-[#c5d9eb] bg-white text-[#2a5a94] hover:bg-[#ecf5ff]" aria-label="Прокрутить влево">
                            <i class="fa-solid fa-chevron-left text-sm" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="doctor-slider-next inline-flex h-10 w-10 items-center justify-center rounded-full border border-[#c5d9eb] bg-white text-[#2a5a94] hover:bg-[#ecf5ff]" aria-label="Прокрутить вправо">
                            <i class="fa-solid fa-chevron-right text-sm" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="doctor-slider-track flex items-stretch gap-4 overflow-x-auto scroll-smooth pb-2">
                        {$cards_html}
                    </div>
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
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-left text-base font-semibold text-[#0f3463]">
                    <span>{$this->e($item['question'])}</span>
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-[#c9dff1] bg-[#f3f9ff] text-[#2a5a94]">
                        <i class="fa-solid fa-chevron-down text-xs transition group-open:rotate-180" aria-hidden="true"></i>
                    </span>
                </summary>
                <p class="mt-4 text-sm leading-relaxed text-[#355b89]">{$this->e($item['answer'])}</p>
            </details>
            HTML;
        }

        return <<<HTML
        <section id="faq" class="border-b border-[#e6eef7] bg-[#f8fcff] py-12 md:py-16">
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
                    . '<div class="absolute left-3 top-3 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/88 text-[#2fbdef] shadow-[0_8px_18px_rgba(8,36,70,0.12)]">'
                    . '<i class="fa-solid ' . $icon . ' text-[1rem]" aria-hidden="true"></i>'
                    . '</div>'
                    . '</div>';
            }
            
            $price_display = '';
            if (isset($service['price'])) {
                $price_display = '<div class="mt-4 text-[1rem] font-semibold text-[#2fbdef]">' . $this->e($service['price']);
                if (isset($service['price_note']) && !empty($service['price_note'])) {
                    $price_display .= ' <span class="text-[0.8rem] font-normal text-[#355b89]">' . $this->e($service['price_note']) . '</span>';
                }
                $price_display .= '</div>';
            }

            $items_html .= <<<HTML
            <article class="group rounded-[1.35rem] border border-[#d7e4ef] bg-white/80 p-5 transition hover:-translate-y-0.5 hover:border-[#2fbdef] hover:shadow-[0_12px_28px_rgba(47,189,239,0.16)]">
                {$service_image_html}
                <div class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-[#e3f2fc] text-[#2fbdef] mb-3">
                    <i class="fa-solid {$icon} text-[1rem]" aria-hidden="true"></i>
                </div>
                <p class="text-[0.75rem] font-semibold uppercase tracking-[0.1em] text-[#2a5a94] mb-1">{$this->e($service['subtitle'] ?? 'Услуга')}</p>
                <h3 class="text-[1.05rem] font-bold leading-[1.2] text-[#0f2749] mb-2">{$this->e($service['name'])}</h3>
                <p class="text-[0.9rem] leading-relaxed text-[#355b89] line-clamp-2">{$this->e($service['description'])}</p>
                {$price_display}
                <a href="{$service_link}" class="mt-4 inline-flex rounded-lg border border-[#2fbdef] bg-[#f0fafe] px-3.5 py-2 text-[0.8rem] font-semibold text-[#2fbdef] transition hover:bg-[#2fbdef] hover:text-white">
                    Подробнее →
                </a>
            </article>
            HTML;
            $count++;
        }

        return <<<HTML
        <section id="services" class="border-b border-[#e6eef7] bg-white py-10 md:py-14">
            <div class="mx-auto max-w-6xl px-6 md:px-10">
                {$this->sectionTitle('Популярные услуги', 'Основные направления лечения', 'Выберите интересующее вас направление, чтобы узнать подробнее о методах, показаниях и ценах')}
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {$items_html}
                </div>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-[0.9rem] text-[#355b89]">Хотите начать с комплексной диагностики?</p>
                    <a href="/services/hobilect-diagnostics" class="inline-flex rounded-lg bg-[#2fbdef] px-5 py-2.5 text-[0.9rem] font-semibold text-white transition hover:bg-[#1fb3d8] active:bg-[#1a9ec0]">
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
        <section id="reviews" class="border-b border-[#e6eef7] bg-[#f7fbff] py-12 md:py-16">
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
        return <<<HTML
        <section id="book-now" class="border-b border-[#e6eef7] bg-[linear-gradient(120deg,#ecf6ff_0%,#f7fbff_45%,#edf7ff_100%)] py-10 md:py-14">
            <div class="mx-auto max-w-6xl px-6 md:px-10">
                <div class="rounded-3xl border border-[#d7e6f3] bg-white p-7 shadow-[0_18px_42px_rgba(6,29,60,0.08)] md:p-9">
                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-[#2fbdef]">Запишитесь на консультацию</p>
                    <h2 class="mt-2 text-[1.35rem] font-bold leading-tight text-[#0f2749] md:text-[1.6rem]">Оставьте заявку — перезвоним в течение 15 минут</h2>
                    <p class="mt-2.5 max-w-2xl text-[0.86rem] leading-relaxed text-[#355b89]">Мы уточним запрос, подберём специалиста и согласуем удобное время. Ежедневно с 9:00 до 21:00.</p>
                    <form class="mt-5 flex flex-col gap-2.5 sm:max-w-lg sm:flex-row">
                        <input type="tel" placeholder="+7 (___) ___-__-__" class="w-full rounded-full border border-[#d6e4f2] bg-[#f8fbff] px-5 py-2.5 text-[0.88rem] text-[#173f73] outline-none focus:border-[#2fbdef]">
                        <button type="submit" class="shrink-0 rounded-full bg-[#2fbdef] px-6 py-2.5 text-[0.88rem] font-semibold text-white hover:bg-[#269bc4] transition-colors">Записаться</button>
                    </form>
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
            ? '<div class="mt-1"><a href="tel:' . $phone_link_2 . '" class="text-[1rem] font-semibold text-[#2fbdef] hover:text-[#0f2749] transition">' . $phone_2 . '</a></div>'
            : '';

        return <<<HTML
        <section id="contact" class="bg-gradient-to-b from-white to-[#f8fbff] py-10 md:py-14">
            <div class="mx-auto max-w-6xl px-6 md:px-10">
                {$this->sectionTitle('Контакты', 'Адрес и связь с клиникой', 'Мы всегда на связи и готовы ответить на ваши вопросы')}

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
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#e3f2fc] text-[#2fbdef] shrink-0">
                                    <i class="fa-solid fa-hospital text-[0.9rem]" aria-hidden="true"></i>
                                </span>
                                <h3 class="text-[1.25rem] font-bold text-[#0f2749]">Клиника «БИОИНМЕД»</h3>
                            </div>
                            <p class="text-[0.9rem] text-[#355b89] leading-relaxed ml-11">
                                Свяжитесь с нами удобным способом или посетите клинику по указанному адресу
                            </p>
                        </div>

                        <!-- Блок контактов -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Телефон -->
                            <div class="rounded-xl border border-[#d7e4ef] bg-white p-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-[#e3f2fc] text-[#2fbdef]">
                                        <i class="fa-solid fa-phone text-[0.75rem]" aria-hidden="true"></i>
                                    </span>
                                    <p class="text-[0.75rem] font-bold uppercase tracking-[0.08em] text-[#2a5a94]">Телефон</p>
                                </div>
                                <a href="tel:{$phone_link_1}" class="block text-[1rem] font-bold text-[#2fbdef] hover:text-[#0f2749] transition leading-snug">
                                    {$phone_1}
                                </a>
                                {$second_phone_html}
                            </div>

                            <!-- Адрес -->
                            <div class="rounded-xl border border-[#d7e4ef] bg-white p-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-[#e3f2fc] text-[#2fbdef]">
                                        <i class="fa-solid fa-map-pin text-[0.75rem]" aria-hidden="true"></i>
                                    </span>
                                    <p class="text-[0.75rem] font-bold uppercase tracking-[0.08em] text-[#2a5a94]">Адрес</p>
                                </div>
                                <p class="text-[0.95rem] font-semibold text-[#0f2749] leading-snug">
                                    {$address}
                                </p>
                                <p class="text-[0.85rem] text-[#214a7f] mt-1">
                                    {$metro}
                                </p>
                            </div>

                            <!-- Режим работы -->
                            <div class="rounded-xl border border-[#d7e4ef] bg-white p-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-[#e3f2fc] text-[#2fbdef]">
                                        <i class="fa-solid fa-clock text-[0.75rem]" aria-hidden="true"></i>
                                    </span>
                                    <p class="text-[0.75rem] font-bold uppercase tracking-[0.08em] text-[#2a5a94]">Режим</p>
                                </div>
                                <p class="text-[0.95rem] font-semibold text-[#0f2749] leading-snug">
                                    {$hours}
                                </p>
                            </div>

                            <!-- Email -->
                            <div class="rounded-xl border border-[#d7e4ef] bg-white p-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-[#e3f2fc] text-[#2fbdef]">
                                        <i class="fa-solid fa-envelope text-[0.75rem]" aria-hidden="true"></i>
                                    </span>
                                    <p class="text-[0.75rem] font-bold uppercase tracking-[0.08em] text-[#2a5a94]">Email</p>
                                </div>
                                <a href="mailto:{$email}" class="text-[0.95rem] font-semibold text-[#2fbdef] hover:text-[#0f2749] transition break-all">
                                    {$email}
                                </a>
                            </div>
                        </div>

                        <!-- Как добраться -->
                        <div class="rounded-xl border border-[#2fbdef] bg-gradient-to-br from-[#f0fafe] to-[#e8f7fb] p-5">
                            <div class="flex items-start gap-3">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#2fbdef] text-white shrink-0 mt-0.5">
                                    <i class="fa-solid fa-directions text-[0.9rem]" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <h4 class="font-bold text-[#0f2749] mb-2">Как добраться</h4>
                                    <p class="text-[0.85rem] text-[#355b89] leading-relaxed mb-3">
                                        Станция метро <strong>Фрунзенская</strong>. Выход из стеклянных дверей налево, затем прямо по переулку Хользунова до первого перекрёстка со светофором. Перейдите дорогу (ориентир — кафе «Брусника») и пройдите ещё около 50 метров до вывески «БИОИНМЕД».
                                    </p>
                                    <a href="https://yandex.com/maps/-/CPGGyEzo" target="_blank" rel="noreferrer noopener" class="inline-flex items-center gap-2 rounded-lg bg-[#2fbdef] px-4 py-2 text-[0.85rem] font-semibold text-white transition hover:bg-[#1fb3d8]">
                                        <i class="fa-solid fa-map text-[0.75rem]" aria-hidden="true"></i>
                                        Открыть в Яндекс.Картах
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Оставить отзыв -->
                        <div class="rounded-xl border border-[#dce8f5] bg-[#f8fcff] p-5">
                            <h4 class="font-bold text-[#0f2749] mb-3">Оставить отзыв о центре</h4>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <a href="https://yandex.ru/maps/org/bioinmed/20810337169/reviews/?ll=37.579538%2C55.731055&z=15" target="_blank" rel="noreferrer noopener" class="inline-flex items-center justify-center rounded-lg border border-[#d7e4ef] bg-white px-3 py-2 text-[0.8rem] font-semibold text-[#2a5a94] transition hover:border-[#2fbdef] hover:bg-[#f0fafe]">
                                    <i class="fa-solid fa-star text-[0.8rem] mr-1 text-[#2fbdef]"></i>
                                    Яндекс
                                </a>
                                <a href="https://2gis.ru/moscow/firm/70000001085756150/tab/reviews" target="_blank" rel="noreferrer noopener" class="inline-flex items-center justify-center rounded-lg border border-[#d7e4ef] bg-white px-3 py-2 text-[0.8rem] font-semibold text-[#2a5a94] transition hover:border-[#2fbdef] hover:bg-[#f0fafe]">
                                    <i class="fa-solid fa-star text-[0.8rem] mr-1 text-[#2fbdef]"></i>
                                    2ГИС
                                </a>
                                <a href="https://doctu.ru/msk/clinic/bioinmed" target="_blank" rel="noreferrer noopener" class="inline-flex items-center justify-center rounded-lg border border-[#d7e4ef] bg-white px-3 py-2 text-[0.8rem] font-semibold text-[#2a5a94] transition hover:border-[#2fbdef] hover:bg-[#f0fafe]">
                                    <i class="fa-solid fa-star text-[0.8rem] mr-1 text-[#2fbdef]"></i>
                                    Doctu
                                </a>
                                <a href="https://vk.com/bioinmed" target="_blank" rel="noreferrer noopener" class="inline-flex items-center justify-center rounded-lg border border-[#d7e4ef] bg-white px-3 py-2 text-[0.8rem] font-semibold text-[#2a5a94] transition hover:border-[#2fbdef] hover:bg-[#f0fafe]">
                                    <i class="fa-brands fa-vk text-[0.8rem] mr-1"></i>
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

class Footer extends Component {
    public function render() {
        $vk = defined('CLINIC_VK') ? $this->e(CLINIC_VK) : '#';
        $telegram = defined('CLINIC_TELEGRAM') ? $this->e(CLINIC_TELEGRAM) : '#';
        $phone = $this->phoneLink(CLINIC_PHONE);
        $phone1_display = $this->e(CLINIC_PHONE);
        $phone2 = defined('CLINIC_PHONE_2') ? CLINIC_PHONE_2 : '';
        $phone2_link = $phone2 ? $this->phoneLink($phone2) : '';
        $second_phone_footer = $phone2_link
            ? '<a href="tel:' . $phone2_link . '" class="block text-sm font-semibold text-[#214a7f] hover:text-[#2fbdef] transition-colors">' . $this->e($phone2) . '</a>'
            : '';

        return <<<HTML
        <footer class="bg-gradient-to-b from-[#f4f9ff] to-[#e8f1fa] border-t-4 border-[#2fbdef]">
            <div class="mx-auto max-w-6xl px-6 md:px-10 py-12 md:py-16">
                <!-- Верхняя часть подвала -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
                    <!-- Логотип и описание -->
                    <div class="md:col-span-1">
                        <div class="mb-4">
                            <img src="/public/images/brand/main-logotype.png" alt="БИОИНМЕД" class="h-12 mb-3" loading="lazy" decoding="async">
                        </div>
                        <p class="text-sm text-[#214a7f] leading-relaxed">
                            Интегративная и восстановительная медицина с персональным маршрутом лечения для каждого пациента.
                        </p>
                        <div class="mt-4 flex gap-3">
                            <a href="{$vk}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center w-10 h-10 bg-[#2fbdef]/10 hover:bg-[#2fbdef] rounded-full text-[#2fbdef] hover:text-white transition-colors" title="ВКонтакте">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M15.07 2H8.93C3.33 2 2 3.33 2 8.93v6.14C2 20.67 3.33 22 8.93 22h6.14C20.67 22 22 20.67 22 15.07V8.93C22 3.33 20.67 2 15.07 2zm3.08 13.96h-1.58c-.6 0-.78-.48-1.86-1.57-1-.94-1.4-.94-1.64-.94-.33 0-.42.1-.42.54v1.43c0 .38-.12.6-1.12.6-1.65 0-3.47-.99-4.75-2.84C5.43 10.41 5 8.68 5 8.31c0-.24.1-.46.54-.46h1.58c.4 0 .55.18.7.6.77 2.22 2.06 4.17 2.6 4.17.2 0 .29-.1.29-.63V9.75c-.06-1.13-.66-1.22-.66-1.62 0-.2.16-.4.42-.4h2.49c.34 0 .46.18.46.57v3.07c0 .34.15.46.25.46.2 0 .37-.12.74-.5 1.15-1.28 1.97-3.25 1.97-3.25.1-.24.3-.46.7-.46h1.58c.48 0 .58.24.48.57-.2.94-2.15 3.69-2.15 3.69-.17.27-.23.4 0 .7.17.23.74.71 1.12 1.14.7.8 1.23 1.47 1.37 1.94.16.46-.08.7-.54.7z"/>
                                </svg>
                            </a>
                            <a href="{$telegram}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center w-10 h-10 bg-[#2fbdef]/10 hover:bg-[#2fbdef] rounded-full text-[#2fbdef] hover:text-white transition-colors" title="Telegram">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 0C5.37 0 0 5.37 0 12s5.37 12 12 12 12-5.37 12-12S18.63 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.82-1.084.51l-3-2.21-1.446 1.394c-.16.16-.295.295-.605.295l.21-3.053 5.56-5.023c.242-.213-.054-.33-.373-.117l-6.869 4.332-2.96-.924c-.64-.203-.658-.64.135-.954l11.566-4.461c.54-.203 1.01.131.84.941z"/>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Услуги -->
                    <div>
                        <h4 class="text-sm font-bold uppercase tracking-[0.12em] text-[#2fbdef] mb-4">Услуги</h4>
                        <ul class="space-y-2">
                            <li><a href="/services/hobilect-diagnostics" class="text-sm text-[#214a7f] hover:text-[#2fbdef] transition-colors">Диагностика HABILECT</a></li>
                            <li><a href="/services/musculoskeletal-program" class="text-sm text-[#214a7f] hover:text-[#2fbdef] transition-colors">Восстановление опорно-двигательного аппарата</a></li>
                            <li><a href="/services/osteopathy" class="text-sm text-[#214a7f] hover:text-[#2fbdef] transition-colors">Остеопатия</a></li>
                            <li><a href="/services/reflexotherapy" class="text-sm text-[#214a7f] hover:text-[#2fbdef] transition-colors">Рефлексотерапия</a></li>
                            <li><a href="/prices" class="text-sm text-[#214a7f] hover:text-[#2fbdef] transition-colors font-semibold">Все услуги и цены →</a></li>
                        </ul>
                    </div>

                    <!-- Компания -->
                    <div>
                        <h4 class="text-sm font-bold uppercase tracking-[0.12em] text-[#2fbdef] mb-4">Компания</h4>
                        <ul class="space-y-2">
                            <li><a href="/" class="text-sm text-[#214a7f] hover:text-[#2fbdef] transition-colors">О клинике</a></li>
                            <li><a href="/doctors" class="text-sm text-[#214a7f] hover:text-[#2fbdef] transition-colors">Специалисты</a></li>
                            <li><a href="/prices" class="text-sm text-[#214a7f] hover:text-[#2fbdef] transition-colors">Прайс-лист</a></li>
                            <li><a href="/privacy.php" class="text-sm text-[#214a7f] hover:text-[#2fbdef] transition-colors">Политика конфиденциальности</a></li>
                            <li><a href="/user-agreement.php" class="text-sm text-[#214a7f] hover:text-[#2fbdef] transition-colors">Пользовательское соглашение</a></li>
                            <li><a href="/#contact" class="text-sm text-[#214a7f] hover:text-[#2fbdef] transition-colors">Контакты</a></li>
                        </ul>
                    </div>

                    <!-- Контакты -->
                    <div>
                        <h4 class="text-sm font-bold uppercase tracking-[0.12em] text-[#2fbdef] mb-4">Контакты</h4>
                        <div class="space-y-3">
                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-[#2fbdef] mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-semibold text-[#214a7f]">{$this->e(CLINIC_ADDRESS)}</p>
                                    <p class="text-xs text-[#5a7fa3]">{$this->e(CLINIC_METRO)}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-[#2fbdef] mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <a href="mailto:{$this->e(CLINIC_EMAIL)}" class="text-sm font-semibold text-[#214a7f] hover:text-[#2fbdef] transition-colors">
                                    {$this->e(CLINIC_EMAIL)}
                                </a>
                            </div>
                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-[#2fbdef] mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <div class="space-y-1">
                                    <a href="tel:{$phone}" class="block text-sm font-semibold text-[#214a7f] hover:text-[#2fbdef] transition-colors">
                                        {$phone1_display}
                                    </a>
                                    {$second_phone_footer}
                                </div>
                            </div>
                            <div class="flex items-start gap-2 pt-2">
                                <svg class="w-5 h-5 text-[#2fbdef] mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-sm text-[#214a7f]">
                                    <strong>{$this->e(CLINIC_HOURS)}</strong>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Нижняя часть подвала -->
                <div class="flex flex-col gap-3 border-t border-[#dce8f5] pt-5 md:flex-row md:items-center md:justify-between">
                    <p class="text-xs leading-relaxed text-[#5a7fa3]">
                        © 2026 <strong>КЛИНИКА БИОИНМЕД</strong> — интегративная и восстановительная медицина. Все права защищены.
                    </p>
                    <div class="flex items-center gap-4">
                        <a href="/privacy.php" class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2fbdef] hover:text-[#0f2749] transition-colors">
                            Политика
                        </a>
                        <a href="/user-agreement.php" class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2fbdef] hover:text-[#0f2749] transition-colors">
                            Соглашение
                        </a>
                        <a href="/prices" class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2fbdef] hover:text-[#0f2749] transition-colors">
                            Прайс-лист
                        </a>
                        <a href="/#contact" class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2fbdef] hover:text-[#0f2749] transition-colors">
                            Контакты
                        </a>
                    </div>
                </div>
            </div>
        </footer>
        HTML;
    }
}
?>