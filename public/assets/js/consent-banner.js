(function () {
    'use strict';

    var consentKey = 'bioinmed:legal-consent:v1';
    var cookieName = 'bioinmed_legal_consent';

    function hasConsent() {
        try {
            if (window.localStorage.getItem(consentKey) === 'accepted') {
                return true;
            }
        } catch (error) {
            // Cookies remain available when storage is restricted.
        }

        return document.cookie.split(';').some(function (item) {
            return item.trim().indexOf(cookieName + '=accepted') === 0;
        });
    }

    function rememberConsent() {
        try {
            window.localStorage.setItem(consentKey, 'accepted');
        } catch (error) {
            // A first-party cookie provides the fallback.
        }

        var secure = window.location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = cookieName + '=accepted; Max-Age=31536000; Path=/; SameSite=Lax' + secure;
    }

    function renderBanner() {
        if (hasConsent() || document.getElementById('bioinmed-consent')) {
            return;
        }

        var banner = document.createElement('aside');
        banner.id = 'bioinmed-consent';
        banner.className = 'bioinmed-consent';
        banner.setAttribute('role', 'dialog');
        banner.setAttribute('aria-label', 'Согласие на обработку персональных данных');
        banner.innerHTML =
            '<p class="bioinmed-consent__text">' +
                'Нажимая «Согласен», вы даёте согласие на обработку персональных данных в соответствии с ' +
                '<a href="/privacy">Политикой конфиденциальности</a> и принимаете ' +
                '<a href="/user-agreement">Пользовательское соглашение</a>.' +
            '</p>' +
            '<button class="bioinmed-consent__button" type="button">Согласен</button>';

        banner.querySelector('button').addEventListener('click', function () {
            rememberConsent();
            banner.remove();
            window.dispatchEvent(new CustomEvent('bioinmed:legal-consent', {
                detail: { accepted: true }
            }));
        });

        document.body.appendChild(banner);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', renderBanner, { once: true });
    } else {
        renderBanner();
    }
}());
