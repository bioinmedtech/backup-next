<?php

function bioinmed_render_about_breadcrumbs($currentLabel, $isAboutPage = false) {
    $e = static function ($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
    $separator = '<i class="fa-solid fa-chevron-right text-[0.6rem]" aria-hidden="true"></i>';
    $html = '<nav aria-label="Хлебные крошки" class="mb-6 flex flex-wrap items-center gap-2 text-xs text-[#7a9cc4]">';
    $html .= '<a href="/" class="transition hover:text-[#1977b2]">Главная</a>' . $separator;
    if (!$isAboutPage) {
        $html .= '<a href="/about" class="transition hover:text-[#1977b2]">О клинике</a>' . $separator;
    }
    $html .= '<span aria-current="page">' . $e($currentLabel) . '</span></nav>';
    return $html;
}
