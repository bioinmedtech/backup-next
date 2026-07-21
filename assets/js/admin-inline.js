(function () {
    'use strict';

    var cfg = window.BioinmedAdminConfig || {};
    var state = {
        config: cfg,
        activeElement: null,
        activeTextKey: '',
        activeBlockRoot: null,
        activeBlockFields: [],
        activeBlockLinks: [],
        menu: null,
        autosaveTimer: null,
        saveQueue: {},
        editMode: false,
        editingUserId: '',
        savingTextChanges: false,
        blockModeReady: false,
        editableBlocks: [],
        activePriceEdit: null,
        pendingPriceDelete: null,
        pricesManager: {
            loaded: false,
            loading: false,
            saving: false,
            sections: [],
            services: []
        }
    };

    var EDIT_MODE_KEY = 'bioinmed:edit-mode';
    var SHOW_ALL_BLOCKS_KEY = 'bioinmed:show-all-edit-zones';

    function scheduleInlinePricesAutosave() {
        if (!pageHasInlinePricesEditor() || !state.config.isAuthenticated) {
            return;
        }

        if (state.pricesManager.autosaveTimer) {
            clearTimeout(state.pricesManager.autosaveTimer);
        }

        state.pricesManager.autosaveTimer = setTimeout(function () {
            state.pricesManager.autosaveTimer = null;
            state.pricesManager.sections = collectInlinePricesStructure();
            savePricesManagerData();
        }, 450);
    }

    function byId(id) {
        return document.getElementById(id);
    }

    function esc(str) {
        return (str || '').replace(/[&<>"']/g, function (ch) {
            if (ch === '&') return '&amp;';
            if (ch === '<') return '&lt;';
            if (ch === '>') return '&gt;';
            if (ch === '"') return '&quot;';
            return '&#039;';
        });
    }

    function setText(el, txt) {
        if (!el) return;
        el.textContent = txt || '';
    }

    function formatPriceEditorNumber(value) {
        var digits = String(value || '').replace(/[^0-9]/g, '');
        if (!digits) {
            return '';
        }
        return digits.replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' руб.';
    }

    function formatPriceEditorDuration(value) {
        var text = String(value || '').trim();
        if (!text) {
            return '';
        }

        var rangeMatch = text.match(/(\d+)\s*[-–]\s*(\d+)/);
        if (rangeMatch) {
            return rangeMatch[1] + '-' + rangeMatch[2] + ' мин';
        }

        var durationMatch = text.match(/^(\d+)\s*(?:мин\.?|m|minutes?)?$/i);
        if (durationMatch) {
            return durationMatch[1] + ' мин';
        }

        var digits = text.match(/\d+/);
        if (digits) {
            return digits[0] + ' мин';
        }

        return text.replace(/\s+/g, ' ').replace(/(?:мин\.?|minutes?)$/i, '').trim() + ' мин';
    }

    function normalizePriceEditorField(fieldName, value) {
        if (fieldName === 'price') {
            return formatPriceEditorNumber(value);
        }
        if (fieldName === 'duration') {
            return formatPriceEditorDuration(value);
        }
        return value;
    }

    function syncPriceEditorFieldValue(field) {
        if (!field || !field.getAttribute) {
            return;
        }

        var fieldName = field.getAttribute('data-price-modal-field') || '';
        if (!fieldName || field.type === 'checkbox' || field.tagName === 'SELECT') {
            return;
        }

        var nextValue = normalizePriceEditorField(fieldName, field.value || '');
        if (nextValue !== field.value) {
            field.value = nextValue;
        }
        syncPriceMaterialFieldState(field);
    }

    function showToast(message, type) {
        var root = byId('bioinmed-admin-toast-root');
        if (!root || !message) {
            return;
        }

        var toast = document.createElement('div');
        toast.className = 'bioinmed-admin-toast' + (type === 'error' ? ' is-error' : '');
        toast.textContent = message;
        root.appendChild(toast);

        requestAnimationFrame(function () {
            toast.classList.add('is-visible');
        });

        setTimeout(function () {
            toast.classList.remove('is-visible');
            setTimeout(function () {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 220);
        }, 2600);
    }

    function callApi(path, options) {
        return fetch((state.config.apiBase || '/api/admin') + path, options || {})
            .then(function (resp) {
                return resp.json().catch(function () { return { ok: false, error: 'Ошибка сервера' }; });
            })
            .catch(function () {
                return { ok: false, error: 'Сервер админки недоступен' };
            });
    }

    function normalizePinSettings(settings) {
        var normalized = settings && typeof settings === 'object' ? settings : { enabled: true, pin: '' };
        var defaultPinValue = '1290';

        if (typeof normalized.enabled === 'undefined') {
            normalized.enabled = true;
        }

        if (normalized.enabled && !normalized.pin) {
            normalized.pin = defaultPinValue;
        }

        return normalized;
    }

    function hashString(str) {
        var h = 0;
        var i;
        for (i = 0; i < str.length; i++) {
            h = ((h << 5) - h) + str.charCodeAt(i);
            h |= 0;
        }
        return String(Math.abs(h));
    }

    function ensureLinkIds() {
        var counters = {};
        document.querySelectorAll('a').forEach(function (a) {
            if (a.getAttribute('data-link-id')) {
                return;
            }
            var href = (a.getAttribute('href') || '').trim();
            var text = (a.textContent || '').trim();
            var scopeHost = a.closest('[data-text-id]');
            var scope = scopeHost ? (scopeHost.getAttribute('data-text-id') || 'scope') : 'global';
            var base = 'lnk.' + hashString(scope + '|' + href + '|' + text);
            counters[base] = (counters[base] || 0) + 1;
            a.setAttribute('data-link-id', base + '.' + counters[base]);
        });
    }

    function showLogin(open) {
        var overlay = byId('bioinmed-admin-login-overlay');
        if (!overlay) return;
        overlay.classList.toggle('is-open', !!open);
    }

    function showUserEdit(open) {
        var overlay = byId('bioinmed-admin-user-edit-overlay');
        if (!overlay) return;
        overlay.classList.toggle('is-open', !!open);
        if (open) {
            // ensure visual switch reflects hidden value after overlay is shown
            setTimeout(function () {
                var hidden = byId('bioinmed-admin-edit-active');
                var aSwitch = byId('bioinmed-admin-edit-active-switch');
                if (hidden && aSwitch) {
                    var isOn = hidden.value === '1';
                    aSwitch.classList.toggle('is-on', isOn);
                    aSwitch.setAttribute('aria-checked', isOn ? 'true' : 'false');
                }
            }, 0);
        }
    }

    function showTextEdit(open) {
        var overlay = byId('bioinmed-admin-text-edit-overlay');
        if (!overlay) return;
        overlay.classList.toggle('is-open', !!open);
    }

    function showPricesEdit(open) {
        var overlay = byId('bioinmed-admin-prices-edit-overlay');
        if (!overlay) return;
        overlay.classList.toggle('is-open', !!open);
        if (!open) {
            state.activePriceEdit = null;
        }
    }

    function showPriceDeleteConfirm(open, payload) {
        var overlay = byId('bioinmed-admin-price-delete-overlay');
        var titleEl = byId('bioinmed-admin-price-delete-title');
        var textEl = byId('bioinmed-admin-price-delete-text');
        var cancelBtn = byId('bioinmed-admin-price-delete-cancel');
        var confirmBtn = byId('bioinmed-admin-price-delete-confirm');

        if (!overlay) {
            return;
        }

        if (open) {
            state.pendingPriceDelete = payload || null;
            if (titleEl) {
                titleEl.textContent = (payload && payload.title) ? payload.title : 'Подтвердите удаление';
            }
            if (textEl) {
                textEl.textContent = (payload && payload.text) ? payload.text : 'Действие нельзя будет отменить.';
            }
            if (confirmBtn) {
                confirmBtn.textContent = (payload && payload.confirmText) ? payload.confirmText : 'Удалить';
            }
            if (cancelBtn) {
                cancelBtn.focus();
            }
            overlay.classList.add('is-open');
            return;
        }

        overlay.classList.remove('is-open');
        state.pendingPriceDelete = null;
    }

    function runPendingPriceDelete() {
        var pending = state.pendingPriceDelete;
        if (!pending || typeof pending.action !== 'function') {
            showPriceDeleteConfirm(false);
            return;
        }
        showPriceDeleteConfirm(false);
        pending.action();
    }

    function renderPricesEditModal(title, subtitle, html) {
        var titleEl = byId('bioinmed-admin-prices-edit-title');
        var subtitleEl = byId('bioinmed-admin-prices-edit-subtitle');
        var fieldsHost = byId('bioinmed-admin-prices-edit-fields');
        if (!fieldsHost) {
            return;
        }
        if (titleEl) {
            titleEl.textContent = title || 'Редактирование прайса';
        }
        if (subtitleEl) {
            subtitleEl.textContent = subtitle || '';
        }
        fieldsHost.innerHTML = html || '';
    }

    function getPriceSectionState(sectionEl) {
        return {
            id: (sectionEl.getAttribute('data-price-section-id') || '').trim(),
            title: ((sectionEl.querySelector('[data-price-section-title-view]') || {}).textContent || '').trim(),
            nav_label: (sectionEl.getAttribute('data-price-section-nav-label') || '').trim(),
            badge: (sectionEl.getAttribute('data-price-section-badge') || '').trim(),
            hidden: sectionEl.getAttribute('data-price-section-hidden') === '1',
            rows: []
        };
    }

    function applyPriceSectionState(sectionEl, nextState) {
        var titleView = sectionEl.querySelector('[data-price-section-title-view]') || sectionEl.querySelector('h2');
        var normalizedId = normalizePriceManagerSectionId(nextState.id || '', 0);
        sectionEl.setAttribute('data-price-section-id', normalizedId);
        sectionEl.id = normalizedId;
        sectionEl.setAttribute('data-price-section-nav-label', (nextState.nav_label || '').trim());
        sectionEl.setAttribute('data-price-section-badge', (nextState.badge || '').trim());
        if (titleView) {
            titleView.textContent = (nextState.title || '').trim();
        }
    }

    function getPriceRowState(rowEl) {
        var serviceHolder = rowEl.querySelector('[data-service-id]');
        return {
            service_id: serviceHolder ? ((serviceHolder.getAttribute('data-service-id') || '').trim()) : '',
            title: rowEl.getAttribute('data-price-row-title') || '',
            description: rowEl.getAttribute('data-price-row-description') || '',
            duration: rowEl.getAttribute('data-price-row-duration') || '',
            price: rowEl.getAttribute('data-price-row-price') || '',
            row_class: rowEl.getAttribute('data-price-row-class') || '',
            link: rowEl.getAttribute('data-price-row-link') !== '0',
            hidden: rowEl.getAttribute('data-price-row-hidden') === '1'
        };
    }

    function applyPriceRowState(rowEl, nextState) {
        var titleView = rowEl.querySelector('[data-price-row-title-view]');
        var descView = rowEl.querySelector('[data-price-row-description-view]');
        var durationView = rowEl.querySelector('[data-price-row-duration-view]');
        var priceView = rowEl.querySelector('[data-price-row-price-view]');
        var serviceCell = rowEl.querySelector('[data-service-id]');
        var rowClass = (nextState.row_class || '').trim();

        rowEl.className = rowClass + (nextState.hidden ? (rowClass ? ' ' : '') + 'price-row-hidden' : '');
        rowEl.setAttribute('data-price-row-title', nextState.title || '');
        rowEl.setAttribute('data-price-row-description', nextState.description || '');
        rowEl.setAttribute('data-price-row-duration', nextState.duration || '');
        rowEl.setAttribute('data-price-row-price', nextState.price || '');
        rowEl.setAttribute('data-price-row-class', rowClass);
        rowEl.setAttribute('data-price-row-link', nextState.link ? '1' : '0');
        rowEl.setAttribute('data-price-row-hidden', nextState.hidden ? '1' : '0');
        rowEl.classList.toggle('price-row-hidden', !!nextState.hidden);

        if (titleView) {
            titleView.textContent = nextState.title || '';
        }
        if (descView) {
            descView.textContent = nextState.description || '';
            descView.style.display = (nextState.description || '').trim() ? '' : 'none';
        }
        if (durationView) {
            durationView.textContent = (nextState.duration || '').trim() || '—';
        }
        if (priceView) {
            priceView.textContent = nextState.price || '';
        }
        if (serviceCell) {
            serviceCell.setAttribute('data-service-id', nextState.service_id || '');
        }
    }

    function renderPriceMaterialInputField(fieldName, label, value) {
        return [
            '<label class="price-admin-editor-field price-admin-editor-field-material">',
            '<span class="price-admin-editor-material-field" data-price-material-field="1">',
            '<input type="text" class="price-admin-editor-input" data-price-modal-field="' + esc(fieldName) + '" value="' + esc(value || '') + '" placeholder=" ">',
            '<span class="price-admin-editor-floating-label">' + esc(label) + '</span>',
            '</span>',
            '</label>'
        ].join('');
    }

    function renderPriceMaterialTextareaField(fieldName, label, value, rows) {
        return [
            '<label class="price-admin-editor-field price-admin-editor-field-material">',
            '<span class="price-admin-editor-material-field" data-price-material-field="1">',
            '<textarea class="price-admin-editor-input price-admin-editor-textarea" data-price-modal-field="' + esc(fieldName) + '" rows="' + String(rows || 6) + '" placeholder=" ">' + esc(value || '') + '</textarea>',
            '<span class="price-admin-editor-floating-label">' + esc(label) + '</span>',
            '</span>',
            '</label>'
        ].join('');
    }

    function renderPriceMaterialSelectField(fieldName, label, optionsHtml) {
        return [
            '<label class="price-admin-editor-field price-admin-editor-field-material">',
            '<span class="price-admin-editor-material-field" data-price-material-field="1">',
            '<select class="price-admin-editor-input" data-price-modal-field="' + esc(fieldName) + '">',
            optionsHtml,
            '</select>',
            '<span class="price-admin-editor-floating-label">' + esc(label) + '</span>',
            (fieldName === 'service_id' ? '<a href="#" class="price-admin-editor-service-link" data-price-service-edit-link target="_blank" rel="noopener noreferrer">Открыть услугу для быстрого редактирования</a>' : ''),
            '</span>',
            '</label>'
        ].join('');
    }

    function getPriceRowStyleState(currentValue) {
        var classes = String(currentValue || '')
            .split(/\s+/)
            .filter(function (className) {
                return className && className !== 'price-row-hidden';
            });
        var background = '';

        if (classes.indexOf('price-row-background-blue') !== -1 || classes.indexOf('bg-[#f0f7fc]') !== -1) {
            background = 'price-row-background-blue';
        } else if (classes.indexOf('price-row-background-beige') !== -1 || classes.indexOf('bg-[#f9f0e6]') !== -1) {
            background = 'price-row-background-beige';
        }

        return {
            background: background,
            bold: classes.indexOf('price-row-emphasis') !== -1 || classes.indexOf('font-semibold') !== -1,
            extra: classes.filter(function (className) {
                return [
                    'price-row-background-blue',
                    'price-row-background-beige',
                    'bg-[#f0f7fc]',
                    'bg-[#f9f0e6]',
                    'price-row-emphasis',
                    'font-semibold'
                ].indexOf(className) === -1;
            }).join(' ')
        };
    }

    function renderPriceRowStyleField(currentValue) {
        var style = getPriceRowStyleState(currentValue);
        var value = [style.extra, style.background, style.bold ? 'price-row-emphasis' : ''].filter(Boolean).join(' ');

        return [
            '<div class="price-admin-editor-style-field" data-price-row-style-editor>',
            '<span class="price-admin-editor-style-title">Оформление строки</span>',
            '<div class="price-admin-editor-style-controls">',
            '<div class="price-admin-editor-style-background">',
            '<span>Фон строки</span>',
            '<div class="price-admin-editor-style-background-options">',
            '<label class="price-admin-editor-style-background-option"><input type="radio" name="price-row-style-background" value="" data-price-row-style-background' + (!style.background ? ' checked' : '') + '><span class="price-admin-editor-style-swatch is-none">×</span><span>Без фона</span></label>',
            '<label class="price-admin-editor-style-background-option"><input type="radio" name="price-row-style-background" value="price-row-background-blue" data-price-row-style-background' + (style.background === 'price-row-background-blue' ? ' checked' : '') + '><span class="price-admin-editor-style-swatch is-blue"></span><span>Голубой</span></label>',
            '<label class="price-admin-editor-style-background-option"><input type="radio" name="price-row-style-background" value="price-row-background-beige" data-price-row-style-background' + (style.background === 'price-row-background-beige' ? ' checked' : '') + '><span class="price-admin-editor-style-swatch is-beige"></span><span>Бежевый</span></label>',
            '</div>',
            '</div>',
            '<label class="price-admin-editor-style-bold">',
            '<input type="checkbox" data-price-row-style-bold' + (style.bold ? ' checked' : '') + '>',
            '<span>Выделить всю строку жирным</span>',
            '</label>',
            '</div>',
            '<button type="button" class="price-admin-editor-style-reset" data-price-row-style-reset>Сбросить оформление</button>',
            '<input type="hidden" data-price-modal-field="row_class" data-price-row-style-extra="' + esc(style.extra) + '" value="' + esc(value) + '">',
            '</div>'
        ].join('');
    }

    function syncPriceRowStyleEditor(editor) {
        var backgroundField = editor.querySelector('[data-price-row-style-background]:checked');
        var boldField = editor.querySelector('[data-price-row-style-bold]');
        var valueField = editor.querySelector('[data-price-modal-field="row_class"]');
        var background = backgroundField ? backgroundField.value : '';
        var bold = !!(boldField && boldField.checked);
        var extra = valueField ? (valueField.getAttribute('data-price-row-style-extra') || '').trim() : '';

        if (valueField) {
            valueField.value = [extra, background, bold ? 'price-row-emphasis' : ''].filter(Boolean).join(' ');
        }
        editor.querySelectorAll('.price-admin-editor-style-background-option').forEach(function (option) {
            var radio = option.querySelector('[data-price-row-style-background]');
            option.classList.toggle('is-selected', !!(radio && radio.checked));
        });
    }

    function bindPriceRowStyleEditors(scope) {
        scope.querySelectorAll('[data-price-row-style-editor]').forEach(function (editor) {
            if (!editor.hasAttribute('data-price-row-style-bound')) {
                editor.querySelectorAll('[data-price-row-style-background], [data-price-row-style-bold]').forEach(function (field) {
                    field.addEventListener('change', function () {
                        syncPriceRowStyleEditor(editor);
                    });
                });
                var resetButton = editor.querySelector('[data-price-row-style-reset]');
                if (resetButton) {
                    resetButton.addEventListener('click', function () {
                        var emptyBackground = editor.querySelector('[data-price-row-style-background][value=""]');
                        var boldField = editor.querySelector('[data-price-row-style-bold]');
                        if (emptyBackground) emptyBackground.checked = true;
                        if (boldField) boldField.checked = false;
                        syncPriceRowStyleEditor(editor);
                    });
                }
                editor.setAttribute('data-price-row-style-bound', '1');
            }
            syncPriceRowStyleEditor(editor);
        });
    }

    function syncPriceMaterialFieldState(field) {
        if (!field || !field.closest) {
            return;
        }
        var materialField = field.closest('[data-price-material-field="1"]');
        if (!materialField) {
            return;
        }
        var rawValue = '';
        if (field.tagName === 'SELECT') {
            rawValue = field.value || '';
        } else {
            rawValue = field.value || '';
        }
        materialField.classList.toggle('is-filled', String(rawValue).trim().length > 0);
    }

    function bindPriceMaterialFields(scope) {
        if (!scope || !scope.querySelectorAll) {
            return;
        }
        scope.querySelectorAll('[data-price-material-field="1"] [data-price-modal-field]').forEach(function (field) {
            if (!field.hasAttribute('data-price-material-bound')) {
                field.addEventListener('input', function () {
                    syncPriceMaterialFieldState(field);
                });
                field.addEventListener('blur', function () {
                    syncPriceEditorFieldValue(field);
                });
                field.addEventListener('change', function () {
                    syncPriceMaterialFieldState(field);
                    syncPriceEditorFieldValue(field);
                });
                field.setAttribute('data-price-material-bound', '1');
            }
            syncPriceEditorFieldValue(field);
            syncPriceMaterialFieldState(field);
        });

        scope.querySelectorAll('[data-price-service-edit-link]').forEach(function (link) {
            var select = scope.querySelector('[data-price-modal-field="service_id"]');
            if (!select) {
                link.style.display = 'none';
                return;
            }

            var updateServiceEditLink = function () {
                var serviceId = (select.value || '').trim();
                if (!serviceId) {
                    link.style.display = 'none';
                    link.removeAttribute('href');
                    link.textContent = 'Выберите услугу, чтобы открыть её параметры';
                    return;
                }

                link.style.display = 'inline-flex';
                link.setAttribute('href', '/services/' + encodeURIComponent(serviceId));
                link.textContent = 'Открыть услугу для быстрого редактирования';
            };

            if (!select.hasAttribute('data-price-service-link-bound')) {
                select.addEventListener('change', updateServiceEditLink);
                select.addEventListener('input', updateServiceEditLink);
                select.setAttribute('data-price-service-link-bound', '1');
            }

            updateServiceEditLink();
        });

        bindPriceRowStyleEditors(scope);
    }

    function renderPriceSectionEditForm(sectionEl) {
        var sectionState = getPriceSectionState(sectionEl);
        var html = [];
        ['id', 'title', 'nav_label', 'badge'].forEach(function (fieldName) {
            var label = 'Поле';
            if (fieldName === 'id') label = 'ID раздела';
            if (fieldName === 'title') label = 'Заголовок';
            if (fieldName === 'nav_label') label = 'Ярлык в навигации';
            if (fieldName === 'badge') label = 'Бейдж';
            html.push(renderPriceMaterialInputField(fieldName, label, sectionState[fieldName] || ''));
        });
        return '<div class="price-admin-editor-grid price-admin-editor-grid-section">' + html.join('') + '</div>';
    }

    function renderPriceRowEditForm(rowEl) {
        var rowState = getPriceRowState(rowEl);
        var html = [];
        ['title', 'description', 'duration', 'price', 'service_id', 'row_class', 'link'].forEach(function (fieldName) {
            var label = 'Поле';
            if (fieldName === 'title') label = 'Название';
            if (fieldName === 'description') label = 'Описание';
            if (fieldName === 'duration') label = 'Длительность';
            if (fieldName === 'price') label = 'Цена';
            if (fieldName === 'service_id') label = 'Привязка к услуге';
            if (fieldName === 'row_class') label = 'Выделение строки';
            if (fieldName === 'link') {
                html.push([
                    '<label class="price-admin-editor-field price-admin-editor-field-checkbox">',
                    '<input type="checkbox" data-price-modal-field="link"' + (rowState.link ? ' checked' : '') + '>',
                    '<span>Ссылка на услугу включена</span>',
                    '</label>'
                ].join(''));
                return;
            }

            if (fieldName === 'service_id') {
                html.push(renderPriceMaterialSelectField(fieldName, label, getInlineServiceOptionsHtml()));
                return;
            }

            if (fieldName === 'description') {
                html.push(renderPriceMaterialTextareaField('description', label, rowState.description || '', 3));
                return;
            }

            if (fieldName === 'row_class') {
                html.push(renderPriceRowStyleField(rowState[fieldName] || ''));
                return;
            }

            html.push(renderPriceMaterialInputField(fieldName, label, rowState[fieldName] || ''));
        });
        return '<div class="price-admin-editor-grid">' + html.join('') + '</div>';
    }

    function openPriceSectionEditModal(sectionEl) {
        var sectionId = sectionEl.getAttribute('data-price-section-id') || '';
        var modalHost;
        state.activePriceEdit = {
            type: 'section',
            sectionEl: sectionEl
        };
        renderPricesEditModal('Редактирование раздела', sectionId ? ('Раздел: ' + sectionId) : '', renderPriceSectionEditForm(sectionEl));
        modalHost = byId('bioinmed-admin-prices-edit-fields');
        bindPriceMaterialFields(modalHost);
        showPricesEdit(true);
    }

    function openPriceRowEditModal(sectionEl, rowEl) {
        var titleView = rowEl.querySelector('[data-price-row-title-view]');
        var modalHost;
        state.activePriceEdit = {
            type: 'row',
            sectionEl: sectionEl,
            rowEl: rowEl
        };
        renderPricesEditModal('Редактирование цены', titleView ? ((titleView.textContent || '').trim()) : '', renderPriceRowEditForm(rowEl));
        modalHost = byId('bioinmed-admin-prices-edit-fields');
        var modalServiceField = document.querySelector('#bioinmed-admin-prices-edit-fields [data-price-modal-field="service_id"]');
        var rowState = getPriceRowState(rowEl);
        if (modalServiceField) {
            modalServiceField.value = rowState.service_id || '';
            syncPriceMaterialFieldState(modalServiceField);
        }
        bindPriceMaterialFields(modalHost);
        showPricesEdit(true);
    }

    function saveActivePriceEdit() {
        if (!state.activePriceEdit) {
            showPricesEdit(false);
            return;
        }

        var modalHost = byId('bioinmed-admin-prices-edit-fields');
        if (!modalHost) {
            return;
        }

        if (state.activePriceEdit.type === 'section' && state.activePriceEdit.sectionEl) {
            var sectionEl = state.activePriceEdit.sectionEl;
            var sectionState = getPriceSectionState(sectionEl);
            modalHost.querySelectorAll('[data-price-modal-field]').forEach(function (field) {
                var fieldName = field.getAttribute('data-price-modal-field') || '';
                sectionState[fieldName] = field.value || '';
            });
            applyPriceSectionState(sectionEl, sectionState);
            scheduleInlinePricesAutosave();
            showPricesEdit(false);
            return;
        }

        if (state.activePriceEdit.type === 'row' && state.activePriceEdit.rowEl) {
            var rowEl = state.activePriceEdit.rowEl;
            var rowState = getPriceRowState(rowEl);

            modalHost.querySelectorAll('[data-price-modal-field]').forEach(function (field) {
                var fieldName = field.getAttribute('data-price-modal-field') || '';
                rowState[fieldName] = field.type === 'checkbox' ? !!field.checked : normalizePriceEditorField(fieldName, field.value || '');
            });
            applyPriceRowState(rowEl, rowState);
            scheduleInlinePricesAutosave();
            showPricesEdit(false);
        }
    }

    function normalizePriceManagerSectionId(value, fallbackIndex) {
        var normalized = String(value || '').toLowerCase().trim().replace(/[^a-z0-9_-]+/g, '-').replace(/^-+|-+$/g, '');
        if (!normalized) {
            normalized = 'section-' + String((fallbackIndex || 0) + 1);
        }
        return normalized;
    }

    function createEmptyPriceRow() {
        return {
            service_id: '',
            title: '',
            description: '',
            duration: '',
            price: '',
            row_class: '',
            link: true,
            hidden: false
        };
    }

    function normalizePriceRow(row) {
        var source = row && typeof row === 'object' ? row : {};
        return {
            service_id: (source.service_id || '').toString().trim(),
            title: (source.title || '').toString().trim(),
            description: (source.description || '').toString(),
            duration: (source.duration || '').toString().trim(),
            price: (source.price || '').toString().trim(),
            row_class: (source.row_class || '').toString().trim(),
            link: !!(typeof source.link === 'undefined' ? true : source.link),
            hidden: !!source.hidden
        };
    }

    function normalizePriceSection(section, index) {
        var source = section && typeof section === 'object' ? section : {};
        var rows = Array.isArray(source.rows) ? source.rows.map(normalizePriceRow) : [];

        return {
            id: normalizePriceManagerSectionId(source.id || '', index),
            title: (source.title || '').toString().trim(),
            nav_label: (source.nav_label || source.title || '').toString().trim(),
            badge: (source.badge || '').toString().trim(),
            hidden: !!source.hidden,
            rows: rows
        };
    }

    function sanitizePricesBeforeSave() {
        var normalizedSections = [];

        (state.pricesManager.sections || []).forEach(function (section, index) {
            if (!section || typeof section !== 'object') {
                return;
            }

            var nextSection = normalizePriceSection(section, index);
            nextSection.title = (nextSection.title || '').trim() || ('Раздел ' + String(index + 1));
            nextSection.nav_label = (nextSection.nav_label || '').trim() || nextSection.title;
            nextSection.rows = (nextSection.rows || []).map(function (row) {
                var nextRow = normalizePriceRow(row);
                if (!nextRow.title && nextRow.service_id) {
                    var found = state.pricesManager.services.find(function (service) {
                        return service.id === nextRow.service_id;
                    });
                    if (found && found.name) {
                        nextRow.title = found.name;
                    }
                }
                return nextRow;
            }).filter(function (row) {
                return !!(row.title || row.price || row.service_id);
            });

            normalizedSections.push(nextSection);
        });

        state.pricesManager.sections = normalizedSections;
    }

    function loadPricesManagerData(forceReload) {
        var shouldReload = !!forceReload;
        if (state.pricesManager.loaded && !shouldReload) {
            return Promise.resolve(true);
        }

        if (state.pricesManager.loading) {
            return Promise.resolve(false);
        }

        state.pricesManager.loading = true;

        return callApi('/prices-manage.php', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (resp) {
            state.pricesManager.loading = false;
            if (!resp || !resp.ok) {
                showToast((resp && resp.error) || 'Не удалось загрузить прайс-лист', 'error');
                return false;
            }

            var sections = Array.isArray(resp.sections) ? resp.sections : [];
            var services = Array.isArray(resp.services) ? resp.services : [];
            state.pricesManager.sections = sections.map(function (section, index) {
                return normalizePriceSection(section, index);
            });
            state.pricesManager.services = services.map(function (service) {
                return {
                    id: (service && service.id ? service.id : '').toString(),
                    name: (service && service.name ? service.name : '').toString()
                };
            }).filter(function (service) {
                return !!service.id;
            });
            state.pricesManager.loaded = true;
            return true;
        });
    }

    function savePricesManagerData() {
        if (!state.config.isAuthenticated || state.pricesManager.saving) {
            return Promise.resolve(false);
        }

        sanitizePricesBeforeSave();
        state.pricesManager.saving = true;

        return callApi('/prices-manage.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                csrf: state.config.csrf,
                action: 'save_structure',
                sections: state.pricesManager.sections
            })
        }).then(function (resp) {
            state.pricesManager.saving = false;
            if (!resp || !resp.ok) {
                showToast((resp && resp.error) || 'Не удалось сохранить прайс-лист', 'error');
                return false;
            }
            return true;
        });
    }

    function pageHasInlinePricesEditor() {
        return !!document.querySelector('[data-prices-page-root]');
    }

    function getInlineServiceOptionsHtml() {
        var options = ['<option value="">Без привязки</option>'];
        (state.pricesManager.services || []).forEach(function (service) {
            if (!service || !service.id) {
                return;
            }
            options.push('<option value="' + esc(service.id) + '">' + esc(service.name || service.id) + '</option>');
        });
        return options.join('');
    }

    function refreshInlinePriceIndices() {
        document.querySelectorAll('[data-price-section-id]').forEach(function (sectionEl) {
            var index = 0;
            var bodyRows = sectionEl.querySelectorAll('tbody > tr[data-price-row-index]');
            bodyRows.forEach(function (rowEl) {
                rowEl.setAttribute('data-price-row-index', String(index));
                index += 1;
            });
        });
    }

    function createInlinePriceRowDom(sectionEl, rowData) {
        var tbody = sectionEl ? sectionEl.querySelector('tbody') : null;
        if (!tbody) {
            return null;
        }

        var row = normalizePriceRow(rowData || createEmptyPriceRow());
        var rowIndex = tbody.querySelectorAll('tr[data-price-row-index]').length;

        var displayDuration = row.duration || '—';
        var mainRow = document.createElement('tr');
        mainRow.setAttribute('data-price-row-index', String(rowIndex));
        mainRow.setAttribute('data-price-row-hidden', row.hidden ? '1' : '0');
        mainRow.setAttribute('data-price-row-title', row.title || '');
        mainRow.setAttribute('data-price-row-description', row.description || '');
        mainRow.setAttribute('data-price-row-duration', row.duration || '');
        mainRow.setAttribute('data-price-row-price', row.price || '');
        mainRow.setAttribute('data-price-row-class', row.row_class || '');
        mainRow.setAttribute('data-price-row-link', row.link ? '1' : '0');
        mainRow.setAttribute('data-admin-disable-block-edit', '1');
        if (row.hidden) {
            mainRow.classList.add('price-row-hidden');
        }
        if (row.row_class) {
            row.row_class.split(/\s+/).filter(Boolean).forEach(function (className) {
                mainRow.classList.add(className);
            });
        }
        mainRow.innerHTML = [
            '<td class="px-4 py-3 price-admin-row-host" data-service-id="' + esc(row.service_id || '') + '">',
            '<div class="price-admin-row-actions" data-admin-disable-block-edit="1">',
            '<button type="button" class="price-admin-inline-btn" data-price-row-action="move-up" title="Поднять строку выше"><span aria-hidden="true">↑</span><span>Выше</span></button>',
            '<button type="button" class="price-admin-inline-btn" data-price-row-action="move-down" title="Опустить строку ниже"><span aria-hidden="true">↓</span><span>Ниже</span></button>',
            '<button type="button" class="price-admin-inline-btn" data-price-row-action="add-after" title="Добавить цену ниже">Добавить цену ниже</button>',
            '<button type="button" class="price-admin-inline-btn" data-price-row-action="toggle-editor" title="Редактировать строку">Редактировать</button>',
            '<button type="button" class="price-admin-inline-btn" data-price-row-action="toggle-hidden" title="Скрыть или показать">' + (row.hidden ? 'Показать' : 'Скрыть') + '</button>',
            '<button type="button" class="price-admin-inline-btn price-admin-inline-btn-danger" data-price-row-action="delete-row" title="Удалить цену">Удалить</button>',
            '</div>',
            '<span data-price-row-title-view>' + esc(row.title || '') + '</span><p class="text-sm text-[#0a293c] mt-1" data-price-row-description-view' + ((row.description || '').trim() ? '' : ' style="display:none"') + '>' + esc(row.description || '') + '</p></td>',
            '<td class="px-4 py-3 text-[#0a293c]" data-price-row-duration-view>' + esc(displayDuration) + '</td>',
            '<td class="px-4 py-3 text-right font-bold text-[#1977b2] whitespace-nowrap" data-price-row-price-view>' + esc(row.price || '') + '</td>'
        ].join('');

        tbody.appendChild(mainRow);

        return mainRow;
    }

    function createInlinePriceSectionDom() {
        var root = document.querySelector('[data-prices-page-root]');
        if (!root) {
            return null;
        }

        var nextNumber = root.querySelectorAll('[data-price-section-id]').length + 1;
        var sectionId = 'new-section-' + String(nextNumber);
        var sectionEl = document.createElement('section');
        sectionEl.className = 'category-section';
        sectionEl.id = sectionId;
        sectionEl.setAttribute('data-price-section-id', sectionId);
        sectionEl.setAttribute('data-price-section-hidden', '0');
        sectionEl.setAttribute('data-price-section-nav-label', 'Новый раздел');
        sectionEl.setAttribute('data-price-section-badge', '');
        sectionEl.setAttribute('data-admin-disable-block-edit', '1');
        sectionEl.innerHTML = [
            '<div class="flex items-center gap-3 mb-6 pb-4 border-b-2 border-[#1977b2]" data-admin-disable-block-edit="1">',
            '<h2 class="text-2xl font-bold text-[#1977b2]" data-price-section-title-view>Новый раздел</h2>',
            '<div class="price-admin-section-toolbar" data-admin-disable-block-edit="1">',
            '<button type="button" class="price-admin-inline-btn" data-price-section-action="move-up" title="Поднять раздел выше"><span aria-hidden="true">↑</span><span>Выше</span></button>',
            '<button type="button" class="price-admin-inline-btn" data-price-section-action="move-down" title="Опустить раздел ниже"><span aria-hidden="true">↓</span><span>Ниже</span></button>',
            '<button type="button" class="price-admin-inline-btn" data-price-section-action="toggle-settings" title="Редактировать раздел">Редактировать</button>',
            '<button type="button" class="price-admin-inline-btn" data-price-section-action="add-row" title="Добавить цену в раздел">Добавить цену</button>',
            '<button type="button" class="price-admin-inline-btn" data-price-section-action="add-section-below" title="Добавить новый раздел ниже">Добавить раздел ниже</button>',
            '<button type="button" class="price-admin-inline-btn" data-price-section-action="toggle-hidden" title="Скрыть или показать раздел">Скрыть</button>',
            '<button type="button" class="price-admin-inline-btn price-admin-inline-btn-danger" data-price-section-action="delete-section" title="Удалить раздел">Удалить</button>',
            '</div>',
            '</div>',
            '<div class="overflow-x-auto">',
            '<table class="w-full border-collapse">',
            '<thead><tr class="bg-[#f0f7fc]"><th class="text-left px-4 py-3 font-semibold text-[#1977b2]">Наименование услуги</th><th class="px-4 py-3 font-semibold text-[#1977b2] whitespace-nowrap">Длительность</th><th class="text-right px-4 py-3 font-semibold text-[#1977b2] whitespace-nowrap">Цена, руб.</th></tr></thead>',
            '<tbody></tbody>',
            '</table>',
            '</div>'
        ].join('');

        root.appendChild(sectionEl);
        createInlinePriceRowDom(sectionEl, createEmptyPriceRow());
        return sectionEl;
    }

    function collectInlinePricesStructure() {
        var sections = [];
        document.querySelectorAll('[data-prices-page-root] [data-price-section-id]').forEach(function (sectionEl, sectionIndex) {
            var section = getPriceSectionState(sectionEl);

            section.id = normalizePriceManagerSectionId(section.id || '', sectionIndex);
            section.title = (section.title || '').trim() || ('Раздел ' + String(sectionIndex + 1));
            section.nav_label = (section.nav_label || '').trim() || section.title;

            sectionEl.querySelectorAll('tbody > tr[data-price-row-index]').forEach(function (rowEl) {
                var row = getPriceRowState(rowEl);

                if (row.title || row.price || row.service_id) {
                    section.rows.push(row);
                }
            });

            sections.push(section);
        });

        return sections;
    }

    function bindInlinePricesEditor() {
        if (!pageHasInlinePricesEditor()) {
            return;
        }

        loadPricesManagerData(false);

        document.querySelectorAll('[data-price-section-id]').forEach(function (sectionEl) {
            if (!sectionEl.__priceInlineBound) {
                sectionEl.__priceInlineBound = true;

                sectionEl.querySelectorAll('[data-price-section-action]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var action = btn.getAttribute('data-price-section-action') || '';
                        if (action === 'toggle-settings') {
                            openPriceSectionEditModal(sectionEl);
                            return;
                        }
                        if (action === 'move-up') {
                            var prev = sectionEl.previousElementSibling;
                            if (prev) {
                                sectionEl.parentNode.insertBefore(sectionEl, prev);
                                refreshInlinePriceIndices();
                            }
                            return;
                        }
                        if (action === 'move-down') {
                            var next = sectionEl.nextElementSibling;
                            if (next) {
                                sectionEl.parentNode.insertBefore(next, sectionEl);
                                refreshInlinePriceIndices();
                            }
                            return;
                        }
                        if (action === 'toggle-hidden') {
                            var hidden = sectionEl.getAttribute('data-price-section-hidden') === '1';
                            sectionEl.setAttribute('data-price-section-hidden', hidden ? '0' : '1');
                            sectionEl.classList.toggle('price-section-hidden', !hidden);
                            btn.textContent = !hidden ? 'Показать' : 'Скрыть';
                            return;
                        }
                        if (action === 'delete-section') {
                            showPriceDeleteConfirm(true, {
                                title: 'Удалить раздел?',
                                text: 'Раздел и все цены внутри него будут удалены. Это действие нельзя отменить.',
                                confirmText: 'Удалить раздел',
                                action: function () {
                                    sectionEl.remove();
                                    refreshInlinePriceIndices();
                                    scheduleInlinePricesAutosave();
                                }
                            });
                            return;
                        }
                        if (action === 'add-row') {
                            createInlinePriceRowDom(sectionEl, createEmptyPriceRow());
                            refreshInlinePriceIndices();
                            bindInlinePricesEditor();
                            scheduleInlinePricesAutosave();
                            return;
                        }
                        if (action === 'add-section-below') {
                            var createdSection = createInlinePriceSectionDom();
                            if (createdSection && sectionEl.parentNode) {
                                sectionEl.parentNode.insertBefore(createdSection, sectionEl.nextElementSibling);
                                bindInlinePricesEditor();
                                refreshInlinePriceIndices();
                                scheduleInlinePricesAutosave();
                            }
                        }
                    });
                });
            }

            sectionEl.querySelectorAll('tbody > tr[data-price-row-index]').forEach(function (rowEl) {
                if (rowEl.__priceInlineBound) {
                    return;
                }
                rowEl.__priceInlineBound = true;

                rowEl.querySelectorAll('[data-price-row-action]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var action = btn.getAttribute('data-price-row-action') || '';

                        if (action === 'toggle-editor') {
                            openPriceRowEditModal(sectionEl, rowEl);
                            return;
                        }
                        if (action === 'move-up') {
                            var prevRow = rowEl.previousElementSibling;
                            if (prevRow && prevRow.hasAttribute('data-price-row-index')) {
                                rowEl.parentNode.insertBefore(rowEl, prevRow);
                                refreshInlinePriceIndices();
                                scheduleInlinePricesAutosave();
                            }
                            return;
                        }
                        if (action === 'move-down') {
                            var nextRow = rowEl.nextElementSibling;
                            if (nextRow && nextRow.hasAttribute('data-price-row-index')) {
                                rowEl.parentNode.insertBefore(nextRow, rowEl);
                                refreshInlinePriceIndices();
                                scheduleInlinePricesAutosave();
                            }
                            return;
                        }
                        if (action === 'add-after') {
                            var createdRow = createInlinePriceRowDom(sectionEl, createEmptyPriceRow());
                            if (createdRow) {
                                rowEl.parentNode.insertBefore(createdRow, rowEl.nextElementSibling);
                                refreshInlinePriceIndices();
                                bindInlinePricesEditor();
                                scheduleInlinePricesAutosave();
                            }
                            return;
                        }
                        if (action === 'toggle-hidden') {
                            var hidden = rowEl.getAttribute('data-price-row-hidden') === '1';
                            rowEl.setAttribute('data-price-row-hidden', hidden ? '0' : '1');
                            rowEl.classList.toggle('price-row-hidden', !hidden);
                            btn.textContent = !hidden ? 'Показать' : 'Скрыть';
                            scheduleInlinePricesAutosave();
                            return;
                        }
                        if (action === 'delete-row') {
                            showPriceDeleteConfirm(true, {
                                title: 'Удалить цену?',
                                text: 'Материал будет удалён из прайса. Это действие нельзя отменить.',
                                confirmText: 'Удалить цену',
                                action: function () {
                                    rowEl.remove();
                                    refreshInlinePriceIndices();
                                    scheduleInlinePricesAutosave();
                                }
                            });
                        }
                    });
                });
            });
        });
    }

    function syncPinSettingsUi() {
        var settings = normalizePinSettings(state.config && state.config.pinSettings ? state.config.pinSettings : { enabled: true, pin: '' });
        var toggle = byId('bioinmed-pin-enabled-switch');
        var input = byId('bioinmed-pin-input');
        var status = byId('bioinmed-pin-status');
        var enabled = !!settings.enabled;
        var defaultPinValue = '1290';

        if (toggle) {
            toggle.classList.toggle('is-on', enabled);
            toggle.setAttribute('aria-checked', enabled ? 'true' : 'false');
        }
        if (input) {
            input.value = settings.pin || defaultPinValue;
        }
        if (status) {
            status.textContent = enabled
                ? ('PIN-защита включена. Текущий PIN: ' + (settings.pin || defaultPinValue))
                : 'PIN-защита выключена. Сайт открыт без PIN-кода.';
        }
    }

    function isValidPinValue(value) {
        return /^[0-9]{4,12}$/.test((value || '').trim());
    }

    function savePinSettings(nextPin, nextEnabled) {
        var currentSettings = normalizePinSettings(state.config && state.config.pinSettings ? state.config.pinSettings : { enabled: true, pin: '' });
        var enabled = typeof nextEnabled === 'boolean' ? nextEnabled : !!currentSettings.enabled;
        var pinValue = typeof nextPin === 'string' ? nextPin.trim() : '';
        var defaultPinValue = '1290';

        if (enabled && pinValue === '') {
            pinValue = defaultPinValue;
        }

        if (enabled && pinValue !== '' && !isValidPinValue(pinValue)) {
            return Promise.resolve({ ok: false, error: 'PIN должен содержать от 4 до 12 цифр.' });
        }

        if (!enabled && pinValue === '') {
            pinValue = currentSettings.pin || '';
        }

        return callApi('/pin-settings.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                csrf: state.config.csrf,
                enabled: enabled,
                pin: pinValue
            })
        }).then(function (resp) {
            if (resp && resp.ok && resp.pinSettings) {
                state.config.pinSettings = resp.pinSettings;
                state.config.pinSettings = normalizePinSettings(state.config.pinSettings);
                syncPinSettingsUi();
                return resp;
            }

            return resp || { ok: false, error: 'Не удалось сохранить PIN' };
        });
    }

    function showMobileAdminMenu(open) {
        var menu = byId('bioinmed-admin-mobile-menu');
        if (!menu) {
            return;
        }

        // keep functionality in case other code triggers mobile menu
        menu.hidden = !open;
        menu.classList.toggle('is-open', !!open);
    }

    function focusEditableElement(el) {
        if (!el || el.getAttribute('contenteditable') !== 'true') {
            return;
        }

        el.focus();
        if (window.getSelection && document.createRange) {
            var range = document.createRange();
            range.selectNodeContents(el);
            range.collapse(false);
            var sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
        }
    }

    function linkHasNestedEditable(link) {
        if (!link) {
            return false;
        }
        var nested = link.querySelector('[data-text-id]');
        return !!nested;
    }

    function isMenuLink(link) {
        if (!link) {
            return false;
        }

        if (link.closest('nav,[role="navigation"]')) {
            return true;
        }

        if (link.closest('header, footer')) {
            return true;
        }

        var cls = ((link.className || '') + ' ' + (((link.closest('header, footer, nav, [role="navigation"], .menu, .nav, .navbar')) || {}).className || '')).toLowerCase();
        return /(menu|nav|navbar|footer|header)/.test(cls);
    }

    function isIgnoredTextKey(key) {
        return key === 'hero.season_badge' || /(^|\.)image_alt$/.test(key);
    }

    function isSemanticBlock(el) {
        if (!el || !el.tagName) {
            return false;
        }
        var tag = el.tagName.toLowerCase();
        if (/^(section|article|aside|header|footer|li|figure)$/.test(tag)) {
            return true;
        }
        var cls = (el.className || '').toLowerCase();
        return /(card|item|block|tile|feature|content|row|col|wrapper|section)/.test(cls);
    }

    function countEditableDescendants(root) {
        if (!root || !root.querySelectorAll) {
            return 0;
        }

        var count = 0;
        if (root.hasAttribute && root.hasAttribute('data-text-id')) {
            var ownKey = root.getAttribute('data-text-id') || '';
            if (ownKey && !isIgnoredTextKey(ownKey)) {
                count += 1;
            }
        }

        root.querySelectorAll('[data-text-id]').forEach(function (node) {
            var key = node.getAttribute('data-text-id') || '';
            if (key && !isIgnoredTextKey(key)) {
                count += 1;
            }
        });

        return count;
    }

    function isGranularBlockCandidate(el) {
        if (!el || !el.tagName) {
            return false;
        }

        var tag = el.tagName.toLowerCase();
        if (/^(details|li|article|figure)$/.test(tag)) {
            return true;
        }

        var cls = (el.className || '').toLowerCase();
        return /(card|item|tile|panel|accordion|faq|step|service-card|doctor|problem|benefit|feature)/.test(cls);
    }

    function findGranularBlockRoot(el) {
        var current = el;

        while (current && current !== document.body) {
            if (isGranularBlockCandidate(current)) {
                var count = countEditableDescendants(current);
                if (count >= 2 && count <= 8) {
                    return current;
                }
            }

            current = current.parentElement;
        }

        return null;
    }

    function getBlockBadgeHost(root) {
        if (!root || !root.tagName) {
            return root;
        }

        var tag = root.tagName.toLowerCase();
        if (tag === 'tr') {
            return root.querySelector('td,th') || root;
        }

        return root;
    }

    function isTouchLikePointer() {
        if (typeof window === 'undefined') {
            return false;
        }

        if ('ontouchstart' in window) {
            return true;
        }

        if (navigator && typeof navigator.maxTouchPoints === 'number' && navigator.maxTouchPoints > 0) {
            return true;
        }

        if (window.matchMedia && window.matchMedia('(hover: none), (pointer: coarse), (max-width: 768px)').matches) {
            return true;
        }

        return false;
    }

    function isIosSafariLike() {
        if (typeof navigator === 'undefined') {
            return false;
        }

        var ua = navigator.userAgent || '';
        var isIOS = /iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        var isWebKit = /WebKit/.test(ua);
        var isCriOS = /CriOS|FxiOS|EdgiOS/.test(ua);
        return !!(isIOS && isWebKit && !isCriOS);
    }

    function collectBlockFields(root) {
        if (!root) {
            return [];
        }
        var fields = [];
        var seen = {};
        var all = [];

        if (root.hasAttribute && root.hasAttribute('data-text-id')) {
            all.push(root);
        }

        root.querySelectorAll('[data-text-id]').forEach(function (node) {
            all.push(node);
        });

        all.forEach(function (node) {
            var key = node.getAttribute('data-text-id') || '';
            if (!key || seen[key] || isIgnoredTextKey(key)) {
                return;
            }
            seen[key] = true;
            fields.push({
                key: key,
                el: node,
                value: (node.textContent || '').trim(),
                label: describeFieldLabel(node, key)
            });
        });

        return fields;
    }

    function collectBlockLinks(root) {
        if (!root) {
            return [];
        }

        var links = [];
        var seen = {};
        var all = [];

        if (root.matches && root.matches('a[data-link-key]')) {
            all.push(root);
        }

        root.querySelectorAll('a[data-link-key]').forEach(function (link) {
            all.push(link);
        });

        all.forEach(function (link) {
            var key = link.getAttribute('data-link-key') || '';
            if (!key || seen[key]) {
                return;
            }

            seen[key] = true;
            links.push({
                key: key,
                el: link,
                value: (link.getAttribute('href') || '').trim(),
                label: describeLinkLabel(link, key),
                text: (link.textContent || '').trim()
            });
        });

        return links;
    }

    function describeLinkLabel(link, key) {
        if (!link) {
            return key || 'Ссылка';
        }

        return (
            link.getAttribute('data-link-label') ||
            link.getAttribute('aria-label') ||
            link.getAttribute('title') ||
            (link.textContent || '').trim() ||
            key ||
            'Ссылка'
        );
    }

    function describeFieldLabel(node, key) {
        var tag = ((node && node.tagName) || '').toLowerCase();
        var cls = ((node && node.className) || '').toLowerCase();
        var loweredKey = (key || '').toLowerCase();

        if (/\.(cta|button|submit_label)$/.test(loweredKey) || tag === 'button') {
            return 'Кнопка';
        }

        if (/\.(title|heading|name)$/.test(loweredKey) || /^h[1-6]$/.test(tag)) {
            return 'Заголовок';
        }

        if (/\.(subtitle|eyebrow|label)$/.test(loweredKey) || /(subtitle|eyebrow|label|caption)/.test(cls)) {
            return 'Подзаголовок';
        }

        if (/\.(price|price_label|price_note)$/.test(loweredKey)) {
            return 'Цена';
        }

        if (/\.(description|text|answer|summary|note|content|intro)$/.test(loweredKey) || tag === 'p') {
            return 'Текст';
        }

        if (tag === 'li') {
            return 'Пункт списка';
        }

        if (tag === 'span' || tag === 'div') {
            return 'Текст';
        }

        return 'Поле';
    }

    function getDisplayLabels(fields) {
        var counts = {};
        return fields.map(function (field) {
            var base = field.label || 'Поле';
            counts[base] = (counts[base] || 0) + 1;
            return counts[base] > 1 ? (base + ' ' + counts[base]) : base;
        });
    }

    function getBlockRoot(el) {
        if (!el) {
            return null;
        }

        if (el.closest && el.closest('[data-admin-disable-block-edit="1"]')) {
            return null;
        }

        var explicitRoot = el.closest('[data-admin-block-root]');
        if (explicitRoot) {
            return explicitRoot;
        }

        var granularRoot = findGranularBlockRoot(el);
        if (granularRoot) {
            return granularRoot;
        }

        var semanticRoot = el.closest('section,article,aside,header,footer,figure');
        if (semanticRoot) {
            return semanticRoot;
        }

        var current = el;
        var chosen = el;
        while (current && current !== document.body) {
            var parent = current.parentElement;
            if (!parent || parent === document.body) {
                break;
            }

            var count = countEditableDescendants(parent);
            if (!count) {
                current = parent;
                continue;
            }

            if (count <= 12) {
                chosen = parent;
                if (count > 1 && isSemanticBlock(parent)) {
                    break;
                }
            }

            if (count > 20) {
                break;
            }

            current = parent;
        }

        return chosen;
    }

    function renderBlockEditor(fields, links) {
        var host = byId('bioinmed-admin-text-edit-fields');
        if (!host) {
            return;
        }

        if (!fields.length && !(links && links.length)) {
            host.innerHTML = '<p class="text-[13px] text-[#4a6f9c]">Нет редактируемых полей в блоке.</p>';
            return;
        }

        var displayLabels = getDisplayLabels(fields);
        var showLabels = fields.length > 1;

        // Группируем поля в три типа: простые строки, массивы, объекты
        var fieldsByType = { simple: [], arrays: {} };
        
        fields.forEach(function (f, idx) {
            var isArray = Array.isArray(f.value);
            var isObject = f.value !== null && typeof f.value === 'object' && !isArray;
            
            if (isArray) {
                // Собираем массивы по ключу (без индекса)
                var keyWithoutIndex = f.key.replace(/\.\d+$/, '');
                if (!fieldsByType.arrays[keyWithoutIndex]) {
                    fieldsByType.arrays[keyWithoutIndex] = {
                        label: displayLabels[idx],
                        parentKey: keyWithoutIndex,
                        items: []
                    };
                }
                fieldsByType.arrays[keyWithoutIndex].items.push({
                    index: idx,
                    key: f.key,
                    value: f.value
                });
            } else if (!isObject) {
                fieldsByType.simple.push({
                    index: idx,
                    key: f.key,
                    value: f.value,
                    label: displayLabels[idx]
                });
            }
        });

        // Отрисовываем простые поля
        var simpleHtml = fieldsByType.simple.map(function (item) {
            return [
                '<div class="bioinmed-block-edit-field">',
                showLabels ? '<label class="bioinmed-block-edit-field-key">' + esc(item.label) + '</label>' : '',
                renderBlockFieldControl(item),
                '</div>'
            ].join('');
        }).join('');

        // Отрисовываем массивы
        var arraysHtml = Object.keys(fieldsByType.arrays).map(function (keyWithoutIndex) {
            var arr = fieldsByType.arrays[keyWithoutIndex];
            var itemsHtml = arr.items.map(function (item, arrIdx) {
                return [
                    '<div class="bioinmed-array-item">',
                    '<textarea data-block-field-index="' + item.index + '" rows="1" class="bioinmed-array-item-input">' + esc(item.value) + '</textarea>',
                    '<button type="button" class="bioinmed-array-item-delete" data-array-parent="' + esc(keyWithoutIndex) + '" data-array-index="' + arrIdx + '" title="Удалить пункт">',
                    '<i class="fa-solid fa-trash-can" aria-hidden="true"></i>',
                    '</button>',
                    '</div>'
                ].join('');
            }).join('');

            return [
                '<div class="bioinmed-array-group">',
                '<label class="bioinmed-block-edit-field-key">' + esc(arr.label) + ' (' + arr.items.length + ')</label>',
                '<div class="bioinmed-array-items">',
                itemsHtml,
                '</div>',
                '<button type="button" class="bioinmed-array-add" data-array-parent="' + esc(keyWithoutIndex) + '" title="Добавить новый пункт">',
                '<i class="fa-solid fa-plus" aria-hidden="true"></i> Добавить пункт',
                '</button>',
                '</div>'
            ].join('');
        }).join('');

        var textHtml = [simpleHtml, arraysHtml].join('');
        var linksHtml = (links || []).map(function (link, idx) {
            return [
                '<div class="bioinmed-block-edit-link">',
                '<label class="bioinmed-block-edit-link-label">' + esc(link.label) + '</label>',
                link.text ? '<div class="bioinmed-block-edit-link-static">' + esc(link.text) + '</div>' : '',
                '<input type="text" data-block-link-index="' + idx + '" value="' + esc(link.value) + '">',
                '</div>'
            ].join('');
        }).join('');

        if (linksHtml) {
            linksHtml = '<p class="bioinmed-block-edit-group-title">Ссылки</p>' + linksHtml;
        }

        host.innerHTML = textHtml;
        if (linksHtml) {
            host.innerHTML += linksHtml;
        }

        bindBlockTextareasAutoGrow(host);
        bindArrayControls(host);
    }

    function renderBlockFieldControl(item) {
        var value = item && typeof item.value !== 'undefined' ? item.value : '';
        var text = value === null || typeof value === 'undefined' ? '' : String(value);
        var isMultiline = shouldRenderBlockTextarea(text);
        if (isMultiline) {
            return '<textarea data-block-field-index="' + item.index + '" rows="' + getBlockTextareaRows(text) + '">' + esc(text) + '</textarea>';
        }
        return '<input type="text" data-block-field-index="' + item.index + '" value="' + esc(text) + '">';
    }

    function shouldRenderBlockTextarea(text) {
        var value = String(text || '').trim();
        if (!value) {
            return false;
        }

        if (value.indexOf('\n') !== -1) {
            return true;
        }

        if (value.length >= 80) {
            return true;
        }

        var words = value.split(/\s+/);
        return words.length >= 8 && value.length >= 48;
    }

    function getBlockTextareaRows(text) {
        var value = String(text || '').trim();
        if (!value) {
            return 2;
        }

        var estimatedRows = Math.ceil(value.length / 60);
        return Math.max(2, Math.min(estimatedRows, 6));
    }

    function collectFormEditedLinks(host, originalLinks) {
        if (!host || !originalLinks || !originalLinks.length) {
            return [];
        }

        var linksToSave = [];
        host.querySelectorAll('input[data-block-link-index]').forEach(function (input) {
            var idx = parseInt(input.getAttribute('data-block-link-index') || '-1', 10);
            if (idx < 0 || idx >= originalLinks.length) {
                return;
            }

            var link = originalLinks[idx];
            var nextValue = (input.value || '').trim();
            var oldValue = (link.value || '').trim();
            if (nextValue === oldValue) {
                return;
            }

            linksToSave.push({
                key: link.key,
                el: link.el,
                value: oldValue,
                newValue: nextValue
            });
        });

        return linksToSave;
    }

    function bindArrayControls(host) {
        if (!host) return;
        
        // Обработчик для кнопок удаления
        host.querySelectorAll('.bioinmed-array-item-delete').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var item = btn.closest('.bioinmed-array-item');
                if (item) {
                    item.remove();
                }
            });
        });

        // Обработчик для кнопок добавления
        host.querySelectorAll('.bioinmed-array-add').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var parent = btn.closest('.bioinmed-array-group');
                if (!parent) return;
                
                var itemsContainer = parent.querySelector('.bioinmed-array-items');
                if (!itemsContainer) return;
                
                var newIdx = itemsContainer.querySelectorAll('.bioinmed-array-item').length;
                var arrayParent = btn.getAttribute('data-array-parent') || '';
                
                var newItem = document.createElement('div');
                newItem.className = 'bioinmed-array-item';
                newItem.innerHTML = [
                    '<textarea rows="1" class="bioinmed-array-item-input" placeholder="Новый пункт" data-new-item="true"></textarea>',
                    '<button type="button" class="bioinmed-array-item-delete" title="Удалить пункт">',
                    '<i class="fa-solid fa-trash-can" aria-hidden="true"></i>',
                    '</button>'
                ].join('');
                
                newItem.querySelector('.bioinmed-array-item-delete').addEventListener('click', function () {
                    newItem.remove();
                });
                
                itemsContainer.appendChild(newItem);
                
                var ta = newItem.querySelector('textarea');
                if (ta) {
                    ta.focus();
                    autoGrowTextarea(ta);
                    ta.addEventListener('input', function () {
                        autoGrowTextarea(ta);
                    });
                }
            });
        });
    }

    function collectFormEditedFields(host, originalFields) {
        if (!host || !originalFields) return [];

        var fieldsToSave = [];
        var arrayGroupsByKey = {};

        // Сначала собираем простые поля
        host.querySelectorAll('[data-block-field-index]').forEach(function (input) {
            var idx = parseInt(input.getAttribute('data-block-field-index') || '-1', 10);
            if (idx < 0 || idx >= originalFields.length) return;

            var field = originalFields[idx];
            var nextValue = input.value || '';
            var isArray = Array.isArray(field.value);

            if (!isArray) {
                field.el.textContent = nextValue;
                fieldsToSave.push({
                    key: field.key,
                    value: field.value,
                    newValue: nextValue
                });
            }
        });

        // Затем собираем массивы - для каждого массива берем все textarea в его контейнере
        host.querySelectorAll('.bioinmed-array-group').forEach(function (arrayGroup) {
            var arrayParentKey = arrayGroup.querySelector('.bioinmed-array-add').getAttribute('data-array-parent');
            if (!arrayParentKey) return;

            var items = [];
            arrayGroup.querySelectorAll('.bioinmed-array-item-input').forEach(function (ta) {
                var val = ta.value || '';
                if (val.trim()) {
                    items.push(val);
                }
            });

            // Найдем соответствующее оригинальное поле
            var arrayField = null;
            for (var i = 0; i < originalFields.length; i++) {
                if (Array.isArray(originalFields[i].value) && 
                    originalFields[i].key.indexOf(arrayParentKey) !== -1) {
                    arrayField = originalFields[i];
                    break;
                }
            }

            if (arrayField) {
                var oldValue = Array.isArray(arrayField.value) ? arrayField.value : [];
                var oldValueStr = JSON.stringify(oldValue);
                var newValueStr = JSON.stringify(items);
                
                if (oldValueStr !== newValueStr) {
                    fieldsToSave.push({
                        key: arrayParentKey,
                        value: oldValue,
                        newValue: items
                    });
                }
            }
        });

        return fieldsToSave;
    }

    function autoGrowTextarea(ta) {
        if (!ta) {
            return;
        }
        var minHeight = getTextareaMinHeight(ta);
        ta.style.height = 'auto';
        ta.style.height = Math.max(ta.scrollHeight, minHeight) + 'px';
    }

    function getTextareaMinHeight(ta) {
        var styles = window.getComputedStyle ? window.getComputedStyle(ta) : null;
        var lineHeight = styles ? parseFloat(styles.lineHeight) : 24;
        var fontSize = styles ? parseFloat(styles.fontSize) : 18;
        var paddingTop = styles ? parseFloat(styles.paddingTop) : 14;
        var paddingBottom = styles ? parseFloat(styles.paddingBottom) : 14;
        var content = ta.value || ta.textContent || '';
        var lines = content.split('\n');
        var width = ta.clientWidth || 480;
        var charsPerLine = Math.max(18, Math.floor(width / Math.max(fontSize * 0.62, 8)));
        var visualLines = 0;

        lines.forEach(function (line) {
            var length = (line || '').length;
            visualLines += Math.max(1, Math.ceil(length / charsPerLine));
        });

        visualLines = Math.max(1, Math.min(visualLines, 12));
        return Math.ceil((visualLines * lineHeight) + paddingTop + paddingBottom + 2);
    }

    function bindBlockTextareasAutoGrow(host) {
        if (!host) {
            return;
        }

        host.querySelectorAll('textarea[data-block-field-index]').forEach(function (ta) {
            autoGrowTextarea(ta);
            ta.addEventListener('input', function () {
                autoGrowTextarea(ta);
            });
        });
    }

    function openBlockEditorByElement(el) {
        if (!el) {
            return;
        }

        ensureLinkIds();

        var blockRoot = getBlockRoot(el);
        var fields = collectBlockFields(blockRoot);
        var links = collectBlockLinks(blockRoot);
        var key = el.getAttribute('data-text-id') || (fields[0] ? fields[0].key : 'block');

        state.activeElement = el;
        state.activeTextKey = key;
        state.activeBlockRoot = blockRoot;
        state.activeBlockFields = fields;
        state.activeBlockLinks = links;

        renderBlockEditor(fields, links);
        showTextEdit(true);
    }

    function ensureEditableBlocks() {
        if (state.blockModeReady) {
            return;
        }

        state.editableBlocks = [];
        var candidates = [];

        document.querySelectorAll('[data-text-id]').forEach(function (el) {
            var ownKey = el.getAttribute('data-text-id') || '';
            if (!ownKey || isIgnoredTextKey(ownKey)) {
                return;
            }

            if (el.closest && el.closest('[data-admin-disable-block-edit="1"]')) {
                return;
            }

            var root = getBlockRoot(el);
            if (!root) {
                return;
            }

            candidates.push({
                root: root,
                origin: el
            });
        });

        var uniqueCandidates = [];
        candidates.forEach(function (candidate) {
            var alreadyAdded = uniqueCandidates.some(function (existing) {
                return existing.root === candidate.root;
            });
            if (!alreadyAdded) {
                uniqueCandidates.push(candidate);
            }
        });

        var selected = uniqueCandidates.filter(function (candidate) {
            return !uniqueCandidates.some(function (other) {
                return other.root !== candidate.root && candidate.root.contains(other.root);
            });
        });

        selected.forEach(function (candidate, idx) {
            var root = candidate.root;
            var originEl = candidate.origin;
            var badgeHost = getBlockBadgeHost(root);
            if (!badgeHost) {
                return;
            }
            var blockId = root.getAttribute('data-admin-block-id');
            if (!blockId) {
                blockId = 'adm-block-' + String(idx + 1);
                root.setAttribute('data-admin-block-id', blockId);
            }

            badgeHost.classList.add('bioinmed-edit-block');
            if (!badgeHost.querySelector('.bioinmed-block-edit-badge')) {
                var badge = document.createElement('button');
                badge.type = 'button';
                badge.className = 'bioinmed-block-edit-badge';
                badge.innerHTML = '<span aria-hidden="true">✎</span><span>Редактировать</span>';
                badge.addEventListener('click', function (ev) {
                    if (!state.config.isAuthenticated || !state.editMode) {
                        return;
                    }

                    ev.preventDefault();
                    ev.stopPropagation();
                    openBlockEditorByElement(originEl);
                });
                badgeHost.appendChild(badge);
            }

            state.editableBlocks.push({
                root: root,
                host: badgeHost
            });
        });

        state.blockModeReady = true;
    }

    function syncEditableBlocks() {
        var active = (state.config.isAuthenticated && state.editMode);
        var showAll = !!state.showAllEditableZones;
        state.editableBlocks.forEach(function (entry) {
            if (!entry || !entry.host) {
                return;
            }
            entry.host.classList.toggle('bioinmed-edit-block-active', active);
            entry.host.classList.toggle('bioinmed-edit-block-force-visible', active && showAll);
        });
    }

    function syncShowAllToggleUi() {
        var toggle = byId('bioinmed-show-all-toggle');
        var label = byId('bioinmed-show-all-toggle-label');
        var mobileToggle = byId('bioinmed-show-all-toggle-mobile');
        var mobileLabel = byId('bioinmed-show-all-toggle-mobile-label');
        var on = !!state.showAllEditableZones;

        if (toggle) {
            toggle.classList.toggle('is-on', on);
            toggle.setAttribute('aria-checked', on ? 'true' : 'false');
        }
        if (mobileToggle) {
            mobileToggle.classList.toggle('is-on', on);
            mobileToggle.setAttribute('aria-checked', on ? 'true' : 'false');
        }
        if (label) {
            label.textContent = 'Зоны редактирования';
        }
        if (mobileLabel) {
            mobileLabel.textContent = 'Зоны редактирования';
        }

        document.body.classList.toggle('bioinmed-show-all-edit-zones', on);
    }

    function syncEditToggleUi() {
        var toggle = byId('bioinmed-edit-toggle');
        var label = byId('bioinmed-edit-toggle-label');
        var mobileToggle = byId('bioinmed-edit-toggle-mobile');
        var mobileLabel = byId('bioinmed-edit-toggle-mobile-label');
        var on = !!state.editMode;

        if (toggle) {
            toggle.classList.toggle('is-on', on);
            toggle.setAttribute('aria-checked', on ? 'true' : 'false');
        }
        if (mobileToggle) {
            mobileToggle.classList.toggle('is-on', on);
            mobileToggle.setAttribute('aria-checked', on ? 'true' : 'false');
        }
        if (label) {
            label.textContent = 'Режим редактирования';
        }
        if (mobileLabel) {
            mobileLabel.textContent = 'Режим редактирования';
        }
    }

    function setShowAllEditableZones(on) {
        state.showAllEditableZones = !!on && !!state.editMode;
        try {
            localStorage.setItem(SHOW_ALL_BLOCKS_KEY, state.showAllEditableZones ? '1' : '0');
        } catch (e) {
            // ignore storage errors
        }
        syncShowAllToggleUi();
        syncEditableBlocks();
    }

    function setEditMode(on) {
        state.editMode = !!on;
        syncEditToggleUi();

        document.body.classList.toggle('bioinmed-edit-mode', state.editMode);
        document.body.classList.toggle('bioinmed-touch-device', isTouchLikePointer());
        document.body.classList.toggle('bioinmed-ios-device', isIosSafariLike());
        try {
            localStorage.setItem(EDIT_MODE_KEY, state.editMode ? '1' : '0');
        } catch (e) {
            // ignore storage errors
        }
        document.querySelectorAll('[data-text-id]').forEach(function (el) {
            el.removeAttribute('contenteditable');
            el.classList.remove('bioinmed-edit-focus');
        });

        if (!state.editMode && state.showAllEditableZones) {
            state.showAllEditableZones = false;
            try {
                localStorage.setItem(SHOW_ALL_BLOCKS_KEY, '0');
            } catch (e) {
                // ignore storage errors
            }
            syncShowAllToggleUi();
        }

        ensureEditableBlocks();
        syncEditableBlocks();
    }

    function applyAuthUi() {
        var auth = !!(state.config && state.config.isAuthenticated);
        var toolbar = byId('bioinmed-admin-toolbar');
        var loginTrigger = byId('bioinmed-admin-login-trigger');
        if (toolbar) {
            toolbar.classList.toggle('is-open', auth);
        }
        if (loginTrigger) {
            loginTrigger.style.display = auth ? 'none' : 'inline-flex';
        }

        document.body.classList.toggle('bioinmed-admin-authenticated', auth);
        var userText = auth && state.config.user ? (state.config.user.name + ' (' + state.config.user.role_label + ')') : '';
        setText(byId('bioinmed-admin-user-badge'), '');
        setText(byId('bioinmed-admin-mobile-user-badge'), userText);
        setContextUserLabel(auth && state.config.user ? state.config.user.name : '');

        var usersBtn = byId('bioinmed-admin-users-open');
        if (usersBtn) {
            usersBtn.style.display = state.config.canManageUsers ? 'inline-flex' : 'none';
        }
        var usersBtnMobile = byId('bioinmed-admin-users-open-mobile');
        if (usersBtnMobile) {
            usersBtnMobile.style.display = state.config.canManageUsers ? 'inline-flex' : 'none';
        }

        if (!auth) {
            showMobileAdminMenu(false);
        }

        if (!auth) {
            setEditMode(false);
            setShowAllEditableZones(false);
        } else {
            var preferred = '0';
            var preferredShowAll = '0';
            try {
                preferred = localStorage.getItem(EDIT_MODE_KEY) || '0';
                preferredShowAll = localStorage.getItem(SHOW_ALL_BLOCKS_KEY) || '0';
            } catch (e) {
                preferred = '0';
                preferredShowAll = '0';
            }
            setEditMode(preferred === '1');
            setShowAllEditableZones(preferredShowAll === '1');
        }

        document.querySelectorAll('[data-text-id]').forEach(function (el) {
            el.setAttribute('spellcheck', 'false');
            if (!auth) {
                el.removeAttribute('contenteditable');
                el.classList.remove('bioinmed-edit-focus');
            }
        });

        ensureEditableBlocks();
        syncEditableBlocks();
    }

    function loadSession() {
        return fetch('/api/admin/session.php', {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (resp) { return resp.json(); })
            .then(function (payload) {
                if (payload && payload.ok && payload.config) {
                    state.config = Object.assign({}, state.config || {}, payload.config);
                    window.BioinmedAdminConfig = state.config;
                    applyAuthUi();
                    if (state.config.isAuthenticated) {
                        ensureLinkIds();
                    }
                }
            })
            .catch(function () {
                showToast('Нет соединения с админ API', 'error');
            });
    }

    function loadPinSettings() {
        return callApi('/pin-settings.php', {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (resp) {
            if (resp && resp.ok && resp.pinSettings) {
                state.config.pinSettings = normalizePinSettings(resp.pinSettings);
                syncPinSettingsUi();
            }

            return resp;
        });
    }

    function saveContentChange(textKey, value) {
        if (!state.config.isAuthenticated || !textKey) return Promise.resolve(false);

        var valueToSend = (typeof value === 'string') ? (value || '') : value;

        return callApi('/content-save.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                csrf: state.config.csrf,
                text_key: textKey,
                value: valueToSend
            })
        }).then(function (resp) {
            if (!resp || !resp.ok) {
                showToast((resp && resp.error) || 'Ошибка сохранения текста', 'error');
                return false;
            }
            return true;
        });
    }

    function saveBlockChanges(fields, links) {
        var textFields = fields || [];
        var linkFields = links || [];

        if (!state.config.isAuthenticated || (!textFields.length && !linkFields.length)) {
            return Promise.resolve(true);
        }

        var saveTasks = [];
        var changedCount = 0;
        var changedLinks = [];

        for (var i = 0; i < textFields.length; i += 1) {
            var f = textFields[i];
            var newValue = (f.newValue || '').trim();
            var oldValue = (f.value || '').trim();

            if (newValue === oldValue) {
                continue;
            }

            changedCount += 1;
            saveTasks.push(saveContentChange(f.key, newValue));
        }

        for (var j = 0; j < linkFields.length; j += 1) {
            var linkField = linkFields[j];
            var nextHref = (linkField.newValue || '').trim();
            var oldHref = (linkField.value || '').trim();

            if (nextHref === oldHref) {
                continue;
            }

            changedCount += 1;
            changedLinks.push(linkField);
            saveTasks.push(saveContentChange(linkField.key, nextHref));
        }

        if (!changedCount) {
            return Promise.resolve(true);
        }

        return Promise.all(saveTasks).then(function (results) {
            var failed = results.find(function (r) { return !r; });
            if (!failed) {
                changedLinks.forEach(function (item) {
                    document.querySelectorAll('a[data-link-key="' + item.key + '"]').forEach(function (link) {
                        link.setAttribute('href', item.newValue);
                    });
                });
                showToast('Изменения применены');
                return true;
            }
            return false;
        });
    }

    function flushTextSaveQueue() {
        if (!state.config.isAuthenticated) {
            return Promise.resolve();
        }

        if (state.autosaveTimer) {
            clearTimeout(state.autosaveTimer);
            state.autosaveTimer = null;
        }

        var keys = Object.keys(state.saveQueue);
        if (!keys.length) {
            return Promise.resolve();
        }

        var reqs = keys.map(function (key) {
            var val = state.saveQueue[key];
            return callApi('/content-save.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    csrf: state.config.csrf,
                    text_key: key,
                    value: val
                })
            });
        });

        state.saveQueue = {};
        return Promise.all(reqs).then(function (results) {
            var failed = results.find(function (r) { return !r || !r.ok; });
            if (failed) {
                showToast((failed && failed.error) || 'Ошибка сохранения текста', 'error');
                throw new Error('Text save flush failed');
            }
            showToast('Изменения сохранены');
        });
    }

    function openUsersPanel() {
        var overlay = byId('bioinmed-admin-users-overlay');
        if (!overlay) return;
        overlay.classList.add('is-open');

        var list = byId('bioinmed-admin-users-list');
        if (list) list.innerHTML = '<p>Загрузка...</p>';

        fetch('/api/admin/users.php', {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (resp) { return resp.json(); })
            .then(function (payload) {
                if (!list) return;
                if (!payload || !payload.ok) {
                    list.innerHTML = '<p>Не удалось загрузить пользователей.</p>';
                    return;
                }

                // cache users by id for reliable access when opening edit
                state.userCache = {};
                list.innerHTML = payload.users.map(function (u) {
                    try { state.userCache[String(u.id)] = u; } catch (e) { /* ignore */ }
                    return [
                        '<div class="bioinmed-admin-user-card">',
                        '<div class="flex items-start justify-between gap-3">',
                        '<div>',
                        '<p class="text-[14px] font-semibold text-[#0f2749]">' + esc(u.name) + '</p>',
                        '<p class="text-[12px] text-[#355b89]">' + esc(u.email) + '</p>',
                        '<p class="bioinmed-admin-user-meta">Роль: ' + esc(u.role) + ' | Активен: ' + ((u.is_active || u.active) ? 'да' : 'нет') + '</p>',
                        '</div>',
                        '<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#eef6ff] text-[#1977b2]"><i class="fa-solid fa-user"></i></span>',
                        '</div>',
                        '<div class="bioinmed-admin-user-actions">',
                        '<button type="button" data-user-act="edit" data-user-id="' + esc(u.id) + '" data-user-name="' + esc(u.name) + '" data-user-email="' + esc(u.email) + '" data-user-role="' + esc(u.role) + '" data-user-active="' + ((u.is_active || u.active) ? '1' : '0') + '"><i class="fa-solid fa-pen"></i> Редактировать</button>',
                        '</div>',
                        '</div>'
                    ].join('');
                }).join('');

                list.querySelectorAll('button[data-user-act]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var act = btn.getAttribute('data-user-act');
                        var userId = btn.getAttribute('data-user-id');
                        if (!userId) return;

                        if (act === 'edit') {
                            state.editingUserId = userId;
                            // prefer authoritative data from cached payload
                            var uObj = (state.userCache && state.userCache[String(userId)]) ? state.userCache[String(userId)] : null;
                            byId('bioinmed-admin-edit-id').value = userId;
                            byId('bioinmed-admin-edit-name').value = (uObj && uObj.name) ? uObj.name : (btn.getAttribute('data-user-name') || '');
                            byId('bioinmed-admin-edit-email').value = (uObj && uObj.email) ? uObj.email : (btn.getAttribute('data-user-email') || '');
                            byId('bioinmed-admin-edit-role').value = (uObj && uObj.role) ? uObj.role : (btn.getAttribute('data-user-role') || 'editor');
                            var activeVal = (uObj && (uObj.is_active || uObj.active)) ? '1' : (btn.getAttribute('data-user-active') === '1' ? '1' : '0');
                            // ensure correct hidden field is updated
                            var hiddenActive = byId('bioinmed-admin-edit-active');
                            if (hiddenActive) {
                                hiddenActive.value = activeVal;
                                console.debug('[users] set hidden active =', hiddenActive.value, 'for user', userId);
                            }
                            // sync visual active switch
                            var aSwitch = byId('bioinmed-admin-edit-active-switch');
                            if (aSwitch) {
                                var isOn = activeVal === '1';
                                aSwitch.classList.toggle('is-on', isOn);
                                aSwitch.setAttribute('aria-checked', isOn ? 'true' : 'false');
                                console.debug('[users] set visual switch is-on =', isOn, 'for user', userId);
                            }
                            byId('bioinmed-admin-edit-password').value = '';
                            // hide delete button when editing self
                            var deleteBtn = byId('bioinmed-admin-user-edit-delete');
                            if (deleteBtn) {
                                if (state.config && state.config.user && String(state.config.user.id) === String(userId)) {
                                    deleteBtn.style.display = 'none';
                                } else {
                                    deleteBtn.style.display = '';
                                }
                            }
                            showUserEdit(true);
                        }
                    });
                });
            });
    }

    function usersApi(payload, onDone) {
        payload.csrf = state.config.csrf;
        callApi('/users.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        }).then(function (resp) {
            var msg = (resp && (resp.message || resp.error)) || 'Действие выполнено';
            showToast(msg, (resp && !resp.ok) ? 'error' : 'info');
            // invalidate user cache on any successful mutation so subsequent opens fetch fresh data
            if (resp && resp.ok) {
                try { state.userCache = {}; } catch (e) { state.userCache = null; }
            }
            if (resp && resp.ok && typeof onDone === 'function') {
                onDone();
            }
        });
    }

    function initEvents() {
        var trigger = byId('bioinmed-admin-login-trigger');
        if (trigger) {
            trigger.addEventListener('click', function () {
                if (state.config && state.config.isAuthenticated) {
                    return;
                }
                showLogin(true);
            });
        }

        var loginClose = byId('bioinmed-admin-login-close');
        if (loginClose) loginClose.addEventListener('click', function () { showLogin(false); });



        var loginOverlay = byId('bioinmed-admin-login-overlay');
        if (loginOverlay) {
            loginOverlay.addEventListener('click', function (ev) {
                if (ev.target === loginOverlay) {
                    ev.preventDefault();
                    ev.stopPropagation();
                }
            });
        }

        var loginForm = byId('bioinmed-admin-login-form');
        if (loginForm) {
            loginForm.addEventListener('submit', function (ev) {
                ev.preventDefault();
                var email = byId('bioinmed-admin-email');
                var password = byId('bioinmed-admin-password');

                callApi('/auth-login.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        email: email ? email.value : '',
                        password: password ? password.value : ''
                    })
                }).then(function (resp) {
                    if (!resp || !resp.ok || !resp.config) {
                        setText(byId('bioinmed-admin-login-error'), (resp && resp.error) || 'Ошибка авторизации');
                        return;
                    }

                    state.config = resp.config;
                    window.BioinmedAdminConfig = resp.config;
                    setText(byId('bioinmed-admin-login-error'), '');
                    showLogin(false);
                    applyAuthUi();
                    ensureLinkIds();
                });
            });
        }

        var logoutBtn = byId('bioinmed-admin-logout');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function () {
                callApi('/auth-logout.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ csrf: state.config.csrf })
                }).then(function () {
                    loadSession();
                });
            });
        }

        var usersBtn = byId('bioinmed-admin-users-open');
        if (usersBtn) usersBtn.addEventListener('click', openUsersPanel);

        var editToggle = byId('bioinmed-edit-toggle');
        if (editToggle) {
            editToggle.addEventListener('click', function () {
                setEditMode(!state.editMode);
            });
        }

        var editToggleMobile = byId('bioinmed-edit-toggle-mobile');
        if (editToggleMobile) {
            editToggleMobile.addEventListener('click', function () {
                setEditMode(!state.editMode);
            });
        }

        var showAllToggle = byId('bioinmed-show-all-toggle');
        if (showAllToggle) {
            showAllToggle.addEventListener('click', function () {
                setShowAllEditableZones(!state.showAllEditableZones);
            });
        }

        var showAllToggleMobile = byId('bioinmed-show-all-toggle-mobile');
        if (showAllToggleMobile) {
            showAllToggleMobile.addEventListener('click', function () {
                setShowAllEditableZones(!state.showAllEditableZones);
            });
        }

        var usersBtnMobile = byId('bioinmed-admin-users-open-mobile');
        if (usersBtnMobile) {
            usersBtnMobile.addEventListener('click', function () {
                showMobileAdminMenu(false);
                openUsersPanel();
            });
        }

        // mobile menu toggle removed — mobile access via settings button

        var mobileMenuClose = byId('bioinmed-admin-mobile-menu-close');
        if (mobileMenuClose) {
            mobileMenuClose.addEventListener('click', function () {
                showMobileAdminMenu(false);
            });
        }

        var mobileMenu = byId('bioinmed-admin-mobile-menu');
        if (mobileMenu) {
            mobileMenu.addEventListener('click', function (ev) {
                if (ev.target === mobileMenu) {
                    showMobileAdminMenu(false);
                }
            });
        }

        var usersClose = byId('bioinmed-admin-users-close');
        if (usersClose) usersClose.addEventListener('click', function () {
            var overlay = byId('bioinmed-admin-users-overlay');
            if (overlay) overlay.classList.remove('is-open');
        });

        var usersAddBtn = byId('bioinmed-admin-users-add-btn');
        if (usersAddBtn) {
            usersAddBtn.addEventListener('click', function () {
                var overlay = byId('bioinmed-admin-users-create-overlay');
                if (overlay) {
                    overlay.classList.add('is-open');
                    byId('bioinmed-admin-new-name').focus();
                }
            });
        }

        var usersCreateClose = byId('bioinmed-admin-users-create-close');
        if (usersCreateClose) {
            usersCreateClose.addEventListener('click', function () {
                var overlay = byId('bioinmed-admin-users-create-overlay');
                if (overlay) overlay.classList.remove('is-open');
            });
        }

        var usersCreateOverlay = byId('bioinmed-admin-users-create-overlay');
        if (usersCreateOverlay) {
            usersCreateOverlay.addEventListener('click', function (ev) {
                if (ev.target === usersCreateOverlay) {
                    usersCreateOverlay.classList.remove('is-open');
                }
            });
        }

        var userEditClose = byId('bioinmed-admin-user-edit-close');
        if (userEditClose) {
            userEditClose.addEventListener('click', function () {
                showUserEdit(false);
            });
        }

        var userEditForm = byId('bioinmed-admin-user-edit-form');
        if (userEditForm) {
            userEditForm.addEventListener('submit', function (ev) {
                ev.preventDefault();

                var id = byId('bioinmed-admin-edit-id').value;
                if (!id) {
                    return;
                }

                var updatePayload = {
                    action: 'update',
                    id: id,
                    name: byId('bioinmed-admin-edit-name').value,
                    email: byId('bioinmed-admin-edit-email').value,
                    role: byId('bioinmed-admin-edit-role').value,
                    is_active: byId('bioinmed-admin-edit-active').value === '1'
                };

                var pwd = byId('bioinmed-admin-edit-password').value;
                if (pwd && pwd.trim() !== '') {
                    updatePayload.password = pwd;
                }

                usersApi(updatePayload, function () {
                    showUserEdit(false);
                    openUsersPanel();
                });
            });
        }

        var userEditDelete = byId('bioinmed-admin-user-edit-delete');
        if (userEditDelete) {
            userEditDelete.addEventListener('click', function () {
                var id = byId('bioinmed-admin-edit-id').value;
                if (!id) {
                    return;
                }
                if (!window.confirm('Удалить пользователя?')) {
                    return;
                }

                usersApi({ action: 'delete', id: id }, function () {
                    showUserEdit(false);
                    openUsersPanel();
                });
            });
        }

        var priceDeleteClose = byId('bioinmed-admin-price-delete-cancel');
        if (priceDeleteClose) {
            priceDeleteClose.addEventListener('click', function () {
                showPriceDeleteConfirm(false);
            });
        }

        var priceDeleteConfirm = byId('bioinmed-admin-price-delete-confirm');
        if (priceDeleteConfirm) {
            priceDeleteConfirm.addEventListener('click', function () {
                runPendingPriceDelete();
            });
        }

        var priceDeleteOverlay = byId('bioinmed-admin-price-delete-overlay');
        if (priceDeleteOverlay) {
            priceDeleteOverlay.addEventListener('click', function (ev) {
                if (ev.target === priceDeleteOverlay) {
                    showPriceDeleteConfirm(false);
                }
            });
        }

        var usersCreate = byId('bioinmed-admin-users-create-form');
        if (usersCreate) {
            usersCreate.addEventListener('submit', function (ev) {
                ev.preventDefault();
                usersApi({
                    action: 'create',
                    email: byId('bioinmed-admin-new-email').value,
                    name: byId('bioinmed-admin-new-name').value,
                    role: byId('bioinmed-admin-new-role').value,
                    password: byId('bioinmed-admin-new-password').value
                }, function () {
                    usersCreate.reset();
                    var createOverlay = byId('bioinmed-admin-users-create-overlay');
                    if (createOverlay) createOverlay.classList.remove('is-open');
                    showToast('Пользователь создан');
                    openUsersPanel();
                });
            });
        }

        document.addEventListener('click', function (ev) {
            var link = ev.target && ev.target.closest ? ev.target.closest('a') : null;
            if (!state.config.isAuthenticated || !state.editMode) {
                return;
            }

            var withinAdminUi = ev.target && ev.target.closest ? ev.target.closest('[id^="bioinmed-admin-"]') : null;
            if (withinAdminUi) {
                return;
            }

            if (link) {
                return;
            }
        }, true);

        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape' && state.pendingPriceDelete) {
                showPriceDeleteConfirm(false);
            }
        });

        var textEditClose = byId('bioinmed-admin-text-edit-close');
        if (textEditClose) {
            textEditClose.addEventListener('click', function () {
                showTextEdit(false);
            });
        }

        var textEditCancel = byId('bioinmed-admin-text-edit-cancel');
        if (textEditCancel) {
            textEditCancel.addEventListener('click', function () {
                showTextEdit(false);
            });
        }

        var textEditSave = byId('bioinmed-admin-text-edit-save');
        if (textEditSave) {
            textEditSave.addEventListener('click', function () {
                var hasText = state.activeBlockFields && state.activeBlockFields.length;
                if (!hasText) {
                    showTextEdit(false);
                    return;
                }

                var host = byId('bioinmed-admin-text-edit-fields');
                var fieldsToSave = collectFormEditedFields(host, state.activeBlockFields);
                var linksToSave = collectFormEditedLinks(host, state.activeBlockLinks);

                saveBlockChanges(fieldsToSave, linksToSave).then(function (success) {
                    if (success) {
                        showTextEdit(false);
                    }
                });
            });
        }

        var pricesEditClose = byId('bioinmed-admin-prices-edit-close');
        if (pricesEditClose) {
            pricesEditClose.addEventListener('click', function () {
                showPricesEdit(false);
            });
        }

        var pricesEditCancel = byId('bioinmed-admin-prices-edit-cancel');
        if (pricesEditCancel) {
            pricesEditCancel.addEventListener('click', function () {
                showPricesEdit(false);
            });
        }

        var pricesEditSave = byId('bioinmed-admin-prices-edit-save');
        if (pricesEditSave) {
            pricesEditSave.addEventListener('click', function () {
                saveActivePriceEdit();
            });
        }

        ensureEditableBlocks();
        syncEditableBlocks();
    }

    // Context menu handling for desktop
    function openAdminSettingsPanel() {
        var panel = byId('bioinmed-admin-settings-overlay');
        if (!panel) return;
        panel.classList.add('is-open');
        panel.style.display = '';
        var btn = byId('bioinmed-admin-settings-open');
        if (btn) btn.setAttribute('aria-expanded', 'true');
    }

    function closeAdminSettingsPanel() {
        var panel = byId('bioinmed-admin-settings-overlay');
        if (!panel) return;
        panel.classList.remove('is-open');
        panel.style.display = 'none';
        var btn = byId('bioinmed-admin-settings-open');
        if (btn) btn.setAttribute('aria-expanded', 'false');
    }

    // Wire context menu buttons
    var settingsOpenBtn = byId('bioinmed-admin-settings-open');
    if (settingsOpenBtn) {
        settingsOpenBtn.addEventListener('click', function (ev) {
            var open = document.querySelector('#bioinmed-admin-settings-overlay.is-open');
            if (open) {
                closeAdminSettingsPanel();
            } else {
                openAdminSettingsPanel();
            }
        });
    }

    var settingsClose = byId('bioinmed-admin-settings-close');
    if (settingsClose) settingsClose.addEventListener('click', function () { closeAdminSettingsPanel(); });

    // Desktop switches wiring
    var desktopEditToggle = byId('bioinmed-edit-toggle-desktop');
    if (desktopEditToggle) desktopEditToggle.addEventListener('click', function () { setEditMode(!state.editMode); });
    var desktopShowAllToggle = byId('bioinmed-show-all-toggle-desktop');
    if (desktopShowAllToggle) desktopShowAllToggle.addEventListener('click', function () { setShowAllEditableZones(!state.showAllEditableZones); });

    // sync desktop toggle UI states
    function syncDesktopToggles() {
        var dEdit = byId('bioinmed-edit-toggle-desktop');
        var dShow = byId('bioinmed-show-all-toggle-desktop');
        var onEdit = !!state.editMode;
        var onShow = !!state.showAllEditableZones;
        if (dEdit) {
            dEdit.classList.toggle('is-on', onEdit);
            dEdit.setAttribute('aria-checked', onEdit ? 'true' : 'false');
        }
        if (dShow) {
            dShow.classList.toggle('is-on', onShow);
            dShow.setAttribute('aria-checked', onShow ? 'true' : 'false');
        }
    }

    // call sync when toggles change
    var origSyncEdit = syncEditToggleUi;
    syncEditToggleUi = function () {
        origSyncEdit();
        syncDesktopToggles();
    };
    var origSyncShowAll = syncShowAllToggleUi;
    syncShowAllToggleUi = function () {
        origSyncShowAll();
        syncDesktopToggles();
    };

    var usersOpenDesktop = byId('bioinmed-admin-users-open-desktop');
    if (usersOpenDesktop) usersOpenDesktop.addEventListener('click', function () { closeAdminSettingsPanel(); openUsersPanel(); });

    var pinToggle = byId('bioinmed-pin-enabled-switch');
    if (pinToggle) {
        pinToggle.addEventListener('click', function () {
            var currentSettings = normalizePinSettings(state.config && state.config.pinSettings ? state.config.pinSettings : { enabled: true, pin: '' });
            var current = !!currentSettings.enabled;
            var nextEnabled = !current;
            var pinInput = byId('bioinmed-pin-input');
            if (!state.config.pinSettings) state.config.pinSettings = {};

            state.config.pinSettings.enabled = nextEnabled;
            syncPinSettingsUi();

            savePinSettings(pinInput ? (pinInput.value || '').trim() : (state.config.pinSettings.pin || ''), nextEnabled).then(function (resp) {
                if (!resp || !resp.ok) {
                    state.config.pinSettings.enabled = current;
                    syncPinSettingsUi();
                    showToast((resp && resp.error) || 'Не удалось сохранить PIN', 'error');
                    return;
                }

                if (resp.pinSettings) {
                    state.config.pinSettings = resp.pinSettings;
                }

                return loadSession().then(function () {
                    syncPinSettingsUi();
                    showToast('PIN-настройки сохранены');
                });
            });
        });
    }

    var pinInput = byId('bioinmed-pin-input');
    var pinAutosaveTimer = null;
    function schedulePinAutosave() {
        if (!pinInput) {
            return;
        }

        if (pinAutosaveTimer) {
            clearTimeout(pinAutosaveTimer);
        }

        pinAutosaveTimer = setTimeout(function () {
            pinAutosaveTimer = null;
            var enabled = !!normalizePinSettings(state.config && state.config.pinSettings ? state.config.pinSettings : { enabled: true, pin: '' }).enabled;
            var pinValue = (pinInput.value || '').trim();
            var defaultPinValue = '1290';

            if (enabled && pinValue === '') {
                pinValue = defaultPinValue;
            }

            if (enabled && pinValue !== '' && !isValidPinValue(pinValue)) {
                return;
            }

            savePinSettings(pinValue, enabled).then(function (resp) {
                if (!resp || !resp.ok) {
                    showToast((resp && resp.error) || 'Не удалось сохранить PIN', 'error');
                    return;
                }

                showToast('PIN-настройки сохранены');
            });
        }, 450);
    }

    if (pinInput) {
        pinInput.addEventListener('input', schedulePinAutosave);
        pinInput.addEventListener('blur', function () {
            if (pinAutosaveTimer) {
                clearTimeout(pinAutosaveTimer);
                pinAutosaveTimer = null;
            }
            schedulePinAutosave();
        });
    }

    // user badge menu
    var userBadge = byId('bioinmed-admin-user-badge');
    function openUserEditFromBadge() {
        // prefill user edit form with current user data from state.config.user
        var u = (state.config && state.config.user) ? state.config.user : null;
        if (!u) return;

        function populateFrom(uObj) {
            try {
                var editId = byId('bioinmed-admin-edit-id');
                var editName = byId('bioinmed-admin-edit-name');
                var editEmail = byId('bioinmed-admin-edit-email');
                var editRole = byId('bioinmed-admin-edit-role');
                var editActive = byId('bioinmed-admin-edit-active');
                if (editId) editId.value = uObj.id || (u.id || '');
                if (editName) editName.value = uObj.name || (u.name || '');
                if (editEmail) editEmail.value = uObj.email || (u.email || '');
                if (editRole) editRole.value = uObj.role || (u.role || 'editor');
                var activeFlag = !!(uObj && (uObj.is_active || uObj.active || (uObj.status && (uObj.status === 'active' || uObj.status === '1')) || uObj.is_active === 1 || uObj.active === 1 || uObj.is_active === '1' || uObj.active === '1'));
                if (editActive) editActive.value = activeFlag ? '1' : '0';
                var activeSwitch = byId('bioinmed-admin-edit-active-switch');
                if (activeSwitch) {
                    var isOn = (editActive.value === '1');
                    activeSwitch.classList.toggle('is-on', isOn);
                    activeSwitch.setAttribute('aria-checked', isOn ? 'true' : 'false');
                    console.debug('[badge] populateFrom: hidden=', editActive.value, 'visual is-on=', isOn, 'user', uObj && (uObj.id || uObj.email || uObj.name));
                }
            } catch (e) { /* ignore */ }
            var deleteBtn = byId('bioinmed-admin-user-edit-delete');
            if (deleteBtn) {
                if (state.config && state.config.user && String(state.config.user.id) === String(u.id)) {
                    deleteBtn.style.display = 'none';
                } else {
                    deleteBtn.style.display = '';
                }
            }
            showUserEdit(true);
        }

        // try cache first
        var cached = (state.userCache && state.userCache[String(u.id)]) ? state.userCache[String(u.id)] : null;
        if (cached) {
            populateFrom(cached);
            return;
        }

        // attempt to fetch authoritative user data
        var uid = encodeURIComponent(u.id || u.user_id || u.uid || '');
        if (!uid) {
            populateFrom(u);
            return;
        }

        fetch((state.config.apiBase || '/api/admin') + '/users.php?id=' + uid, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (resp) { return resp.json().catch(function () { return null; }); })
            .then(function (payload) {
                if (payload && payload.user) {
                    try { state.userCache = state.userCache || {}; state.userCache[String(payload.user.id)] = payload.user; } catch (e) { }
                    populateFrom(payload.user);
                    return;
                }
                if (payload && payload.users && Array.isArray(payload.users)) {
                    var found = payload.users.find(function (x) { return String(x.id) === String(u.id); });
                    if (found) {
                        try { state.userCache = state.userCache || {}; state.userCache[String(found.id)] = found; } catch (e) { }
                        populateFrom(found);
                        return;
                    }
                }
                console.debug('[badge] could not fetch user details, falling back to state.config.user');
                populateFrom(u);
            })
            .catch(function () {
                console.debug('[badge] fetch failed, falling back to state.config.user');
                populateFrom(u);
            });
    }
    if (userBadge) {
        userBadge.addEventListener('click', function (ev) {
            ev.stopPropagation();
            openUserEditFromBadge();
        });
    }

    // populate user menu actions
    var userProfileBtn = byId('bioinmed-user-menu-profile');
    if (userProfileBtn) userProfileBtn.addEventListener('click', function () { closeUserMenu(); alert('Открыть профиль администратора'); });
    var userSettingsBtn = byId('bioinmed-user-menu-settings');
    if (userSettingsBtn) userSettingsBtn.addEventListener('click', function () { closeUserMenu(); openAdminSettingsPanel(); });

    // expose context user label update for applyAuthUi
    function setContextUserLabel(text) {
        var el = byId('bioinmed-admin-settings-user');
        var mobile = byId('bioinmed-admin-mobile-user-badge');
        var badge = byId('bioinmed-admin-user-badge');
        if (el) el.textContent = text || '';
        if (mobile) mobile.textContent = text || '';
        if (badge) badge.textContent = text || '';
    }

    // handle user edit form submit (update own profile)
    var userEditForm = byId('bioinmed-admin-user-edit-form');
    if (userEditForm) {
        userEditForm.addEventListener('submit', function (ev) {
            ev.preventDefault();
            var id = byId('bioinmed-admin-edit-id').value || '';
            var name = byId('bioinmed-admin-edit-name').value || '';
            var email = byId('bioinmed-admin-edit-email').value || '';
            var password = byId('bioinmed-admin-edit-password').value || '';
            var activeVal = byId('bioinmed-admin-edit-active') ? byId('bioinmed-admin-edit-active').value : '1';
            callApi('/users/save.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ id: id, name: name, email: email, password: password, active: activeVal, csrf: state.config.csrf })
            }).then(function (resp) {
                if (!resp || !resp.ok) {
                    showToast((resp && resp.error) || 'Ошибка сохранения', 'error');
                    return;
                }
                showToast('Профиль сохранён');
                showUserEdit(false);
                // refresh session info
                loadSession();
            });
        });
    }

    var userEditDelete = byId('bioinmed-admin-user-edit-delete');
    if (userEditDelete) {
        userEditDelete.addEventListener('click', function () {
            if (!confirm('Вы уверены, что хотите удалить этого пользователя?')) return;
            var id = byId('bioinmed-admin-edit-id').value || '';
            callApi('/users/delete.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ id: id, csrf: state.config.csrf })
            }).then(function (resp) {
                if (!resp || !resp.ok) {
                    showToast((resp && resp.error) || 'Ошибка удаления', 'error');
                    return;
                }
                showToast('Пользователь удалён');
                showUserEdit(false);
                loadSession();
            });
        });
    }

    var userEditLogout = byId('bioinmed-admin-user-edit-logout');
    if (userEditLogout) {
        userEditLogout.addEventListener('click', function () {
            if (!confirm('Выйти из панели администратора?')) return;
            callApi('/auth-logout.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ csrf: state.config.csrf })
            }).then(function () {
                showUserEdit(false);
                loadSession();
            });
        });
    }

    // active switch toggle in edit form
    var activeSwitchBtn = byId('bioinmed-admin-edit-active-switch');
    if (activeSwitchBtn) {
        activeSwitchBtn.addEventListener('click', function () {
            var cur = activeSwitchBtn.classList.contains('is-on');
            activeSwitchBtn.classList.toggle('is-on', !cur);
            activeSwitchBtn.setAttribute('aria-checked', !cur ? 'true' : 'false');
            var hidden = byId('bioinmed-admin-edit-active');
            if (hidden) hidden.value = !cur ? '1' : '0';
        });
    }

    initEvents();
    ensureLinkIds();
    bindInlinePricesEditor();
    loadSession().then(function () {
        if (state.config && state.config.isAuthenticated) {
            return loadPinSettings();
        }
        syncPinSettingsUi();
        return null;
    });
    // keepalive: ping server periodically to keep session alive while admin is active
    setInterval(function () {
        if (!state.config || !state.config.isAuthenticated) return;
        fetch('/api/admin/session.php', {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).catch(function () { /* ignore */ });
    }, 1000 * 60 * 5); // every 5 minutes
})();
