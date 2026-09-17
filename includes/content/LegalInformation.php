<?php

// Shared source: legal.* in data/content/ru/texts.json.
function bioinmed_legal_value($key) {
    return htmlspecialchars(bioinmed_text('legal.' . $key), ENT_QUOTES, 'UTF-8');
}

function bioinmed_render_legal_information($id = 'clinic-details') {
    $id = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');
    $fields = [
        'full_name' => 'Полное наименование',
        'short_name' => 'Сокращённое наименование',
        'inn' => 'ИНН',
        'ogrn' => 'ОГРН',
        'legal_address' => 'Юридический адрес',
        'medical_address' => 'Адрес оказания медицинских услуг',
        'license_number' => 'Регистрационный номер лицензии',
        'license_date' => 'Дата предоставления лицензии',
        'licensing_authority' => 'Лицензирующий орган',
        'activity' => 'Вид деятельности',
        'license_status' => 'Статус лицензии на дату выписки',
        'licensed_services' => 'Работы (услуги) по выписке из реестра',
    ];
    $rows = '';
    foreach ($fields as $key => $label) {
        $rows .= '<div class="grid gap-2 border-t border-[#dce8f5] py-3 md:grid-cols-2">'
            . '<dt class="font-semibold">' . $label . '</dt>'
            . '<dd class="min-w-0" style="overflow-wrap:anywhere" data-text-id="legal.' . $key . '">' . bioinmed_legal_value($key) . '</dd></div>';
    }
    $notice = bioinmed_legal_value('notice');
    $email = htmlspecialchars(CLINIC_EMAIL, ENT_QUOTES, 'UTF-8');
    return <<<HTML
    <section id="{$id}" aria-labelledby="{$id}-heading" class="mt-8 rounded-2xl border border-[#d7e4ef] bg-white p-5 md:p-7 text-[#0a293c]" data-admin-block-root>
        <h2 id="{$id}-heading" class="text-xl font-bold text-[#0f2749]">Юридическая информация и лицензия</h2>
        <dl class="mt-4 text-sm leading-relaxed">{$rows}</dl>
        <p class="mt-4 text-sm leading-relaxed" data-text-id="legal.notice">{$notice}</p>
        <p class="mt-3 text-sm leading-relaxed">Обращения в клинику: <a class="underline" href="mailto:{$email}">{$email}</a>. Для визита используйте адрес оказания медицинских услуг.</p>
        <nav aria-label="Документы и информация для пациентов" class="mt-4 flex flex-wrap gap-4 text-sm font-semibold text-[#1977b2]">
            <a class="underline" href="/license#license-documents">Сканы документов</a>
            <a class="underline" href="https://roszdravnadzor.gov.ru/services/licenses" target="_blank" rel="noopener noreferrer">Реестр лицензий Росздравнадзора</a>
            <a class="underline" href="/prices">Услуги и цены</a>
            <a class="underline" href="/doctors">Врачи клиники</a>
        </nav>
    </section>
    HTML;
}

function bioinmed_render_legal_footer() {
    $name = bioinmed_legal_value('short_name');
    $inn = bioinmed_legal_value('inn');
    $ogrn = bioinmed_legal_value('ogrn');
    $number = bioinmed_legal_value('license_number');
    $date = bioinmed_legal_value('license_date');
    $authority = bioinmed_legal_value('licensing_authority');
    $warning = bioinmed_legal_value('medical_warning');
    $information = bioinmed_legal_value('information_notice');
    $offer = bioinmed_legal_value('offer_notice');
    return <<<HTML
    <div class="mt-4 space-y-3 border-t border-[#dce8f5] pt-5 text-sm leading-relaxed text-[#355b89]" style="overflow-wrap:anywhere">
        <p>{$name}<br>ИНН {$inn} · ОГРН {$ogrn}</p>
        <p>Лицензия на медицинскую деятельность № {$number} от {$date}.<br>Лицензирующий орган: {$authority}.</p>
        <p><a href="/about#clinic-details" class="font-semibold underline text-[#1977b2]">Реквизиты и адреса</a> · <a href="/license" class="font-semibold underline text-[#1977b2]">Лицензия и документы</a></p>
        <p data-text-id="legal.information_notice">{$information}</p>
        <p data-text-id="legal.offer_notice">{$offer}</p>
        <p data-text-id="legal.medical_warning">{$warning}</p>
    </div>
    HTML;
}
