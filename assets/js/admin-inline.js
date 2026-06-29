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
    };

    var EDIT_MODE_KEY = 'bioinmed:edit-mode';
    var SHOW_ALL_BLOCKS_KEY = 'bioinmed:show-all-edit-zones';

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
                '<textarea data-block-field-index="' + item.index + '" rows="1">' + esc(item.value) + '</textarea>',
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
        host.querySelectorAll('textarea[data-block-field-index]').forEach(function (ta) {
            var idx = parseInt(ta.getAttribute('data-block-field-index') || '-1', 10);
            if (idx < 0 || idx >= originalFields.length) return;

            var field = originalFields[idx];
            var nextValue = ta.value || '';
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
