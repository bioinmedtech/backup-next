(function initBlogWeather() {
    var config = window.bioinmedBlogWeatherConfig || {};
    var roots = Array.prototype.slice.call(document.querySelectorAll('[data-blog-weather]'));
    var mobile = document.querySelector('[data-blog-weather-mobile]');
    var mobileToggle = document.querySelector('[data-weather-mobile-toggle]');
    var widgetVersion = config.version || '20260901-weather-v6';
    var storageKey = 'bioinmedBlogWeatherMobileOpen';
    var geoStorageKey = 'bioinmedBlogWeatherGeo:' + widgetVersion;
    var geoCacheTtl = 24 * 60 * 60 * 1000;
    var weatherStorageKey = 'bioinmedBlogWeatherForecast:' + widgetVersion;
    var weatherCacheTtl = 3 * 60 * 60 * 1000;
    var weatherEndpointVersion = String(config.weatherEndpointVersion || widgetVersion);
    var fallbackLocation = { city: 'Москва', latitude: 55.7558, longitude: 37.6173, fallback: true };
    var descriptions = {
        0: 'Ясно', 1: 'Преимущественно ясно', 2: 'Переменная облачность', 3: 'Пасмурно',
        45: 'Туман', 48: 'Изморозь', 51: 'Морось', 53: 'Морось', 55: 'Морось',
        61: 'Небольшой дождь', 63: 'Дождь', 65: 'Сильный дождь', 71: 'Небольшой снег',
        73: 'Снег', 75: 'Сильный снег', 80: 'Ливень', 81: 'Ливень', 82: 'Сильный ливень',
        95: 'Гроза', 96: 'Гроза', 99: 'Гроза'
    };

    if (!roots.length || !window.fetch) {
        return;
    }

    function setText(node, value) {
        if (node) node.textContent = value;
    }

    function forgetOldWeatherCache() {
        try {
            window.localStorage.removeItem('bioinmedBlogWeatherGeo');
            window.localStorage.removeItem('bioinmedBlogWeatherForecast');
        } catch (error) {}
    }

    function logWeatherEvent(event, details) {
        var payload = Object.assign({
            event: event,
            page: window.location.href,
            version: widgetVersion
        }, details || {});

        try {
            var body = JSON.stringify(payload);
            var pixel = new Image();
            pixel.src = '/api/weather-widget-log/?event=' + encodeURIComponent(payload.event || '') +
                '&page=' + encodeURIComponent(payload.page || '') +
                '&city=' + encodeURIComponent(payload.city || '') +
                '&latitude=' + encodeURIComponent(payload.latitude || '') +
                '&longitude=' + encodeURIComponent(payload.longitude || '') +
                '&status=' + encodeURIComponent(payload.status || '') +
                '&message=' + encodeURIComponent(payload.message || payload.version || '') +
                '&t=' + encodeURIComponent(Date.now());

            fetch('/api/weather-widget-log/', {
                method: 'POST',
                credentials: 'omit',
                headers: { 'Content-Type': 'application/json' },
                body: body,
                keepalive: true
            }).catch(function() {});
        } catch (error) {}
    }

    function fetchJson(url, timeout) {
        var controller = window.AbortController ? new AbortController() : null;
        var timer = controller ? window.setTimeout(function() {
            controller.abort();
        }, timeout) : null;
        return fetch(url, {
            credentials: 'omit',
            signal: controller ? controller.signal : undefined
        }).then(function(response) {
            if (timer) window.clearTimeout(timer);
            if (!response.ok) {
                var requestError = new Error('request');
                requestError.status = response.status;
                throw requestError;
            }
            return response.json();
        }, function(error) {
            if (timer) window.clearTimeout(timer);
            throw error;
        });
    }

    function eachWeatherNode(selector, callback) {
        roots.forEach(function(root) {
            var node = root.querySelector(selector);
            if (node) callback(node, root);
        });
    }

    function isValidLocation(location) {
        if (!location) return false;
        var latitude = Number(location.latitude);
        var longitude = Number(location.longitude);
        return Number.isFinite(latitude) && Number.isFinite(longitude) && latitude >= -90 && latitude <= 90 && longitude >= -180 && longitude <= 180;
    }

    function hasRealCity(location) {
        var city = location && typeof location.city === 'string' ? location.city.trim() : '';
        return city !== '' && city !== 'Ваш город';
    }

    function readCachedGeo() {
        try {
            var cached = JSON.parse(window.localStorage.getItem(geoStorageKey) || 'null');
            if (cached && isValidLocation(cached.location) && hasRealCity(cached.location) && cached.savedAt && Date.now() - cached.savedAt < geoCacheTtl) {
                return cached.location;
            }
        } catch (error) {}
        return null;
    }

    function writeCachedGeo(location) {
        try {
            window.localStorage.setItem(geoStorageKey, JSON.stringify({
                savedAt: Date.now(),
                location: location
            }));
        } catch (error) {}
    }

    function weatherCacheKey(location) {
        if (!isValidLocation(location)) return '';
        return Number(location.latitude).toFixed(2) + '_' + Number(location.longitude).toFixed(2);
    }

    function readCachedWeather(location) {
        try {
            var cached = JSON.parse(window.localStorage.getItem(weatherStorageKey) || 'null');
            if (!cached || !cached.data || !cached.savedAt || cached.key !== weatherCacheKey(location)) {
                return null;
            }

            if (Date.now() - cached.savedAt < weatherCacheTtl) {
                return cached.data;
            }
        } catch (error) {}
        return null;
    }

    function writeCachedWeather(location, data) {
        try {
            window.localStorage.setItem(weatherStorageKey, JSON.stringify({
                key: weatherCacheKey(location),
                savedAt: Date.now(),
                data: data
            }));
        } catch (error) {}
    }

    function weatherSvg(code) {
        if ([61,63,65,80,81,82].indexOf(code) !== -1) return '<svg viewBox="0 0 72 72"><path d="M23 45h27a11 11 0 0 0 1-22 18 18 0 0 0-34 7A8 8 0 0 0 23 45Z" fill="#c7dcff"/><path d="M24 53l-4 9M36 53l-4 9M48 53l-4 9" stroke="#5b9dff" stroke-width="5" stroke-linecap="round"/></svg>';
        if ([71,73,75].indexOf(code) !== -1) return '<svg viewBox="0 0 72 72"><path d="M23 43h27a11 11 0 0 0 1-22 18 18 0 0 0-34 7A8 8 0 0 0 23 43Z" fill="#d6e7ff"/><g fill="#9ec6ff"><circle cx="24" cy="55" r="3"/><circle cx="36" cy="59" r="3"/><circle cx="48" cy="55" r="3"/></g></svg>';
        if ([95,96,99].indexOf(code) !== -1) return '<svg viewBox="0 0 72 72"><path d="M23 42h27a11 11 0 0 0 1-22 18 18 0 0 0-34 7A8 8 0 0 0 23 42Z" fill="#4b5568"/><path d="M37 40l-8 16h8l-4 13 13-20h-8l5-9Z" fill="#f4b73f"/></svg>';
        if ([45,48].indexOf(code) !== -1) return '<svg viewBox="0 0 72 72"><path d="M23 38h27a11 11 0 0 0 1-22 18 18 0 0 0-34 7A8 8 0 0 0 23 38Z" fill="#c7dcff"/><path d="M14 50h44M19 59h34" stroke="#a8b4c2" stroke-width="5" stroke-linecap="round"/></svg>';
        if ([2,3,51,53,55].indexOf(code) !== -1) return '<svg viewBox="0 0 72 72"><circle cx="26" cy="25" r="12" fill="#f7b743"/><path d="M23 46h30a12 12 0 0 0 1-24 19 19 0 0 0-36 8A9 9 0 0 0 23 46Z" fill="#c7dcff"/></svg>';
        return '<svg viewBox="0 0 72 72"><circle cx="36" cy="36" r="13" fill="#f7b743"/><g stroke="#f7b743" stroke-width="5" stroke-linecap="round"><path d="M36 9v8M36 55v8M9 36h8M55 36h8M17 17l6 6M49 49l6 6M55 17l-6 6M23 49l-6 6"/></g></svg>';
    }

    function setIcon(node, code) {
        if (node) node.innerHTML = weatherSvg(Number(code || 0));
    }

    function setMobileOpen(open) {
        if (!mobile || !mobileToggle) return;
        mobile.classList.toggle('is-open', open);
        mobileToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        try {
            window.localStorage.setItem(storageKey, open ? '1' : '0');
        } catch (error) {}
    }

    function formatDate(value, index) {
        var date = new Date(value + 'T12:00:00');
        if (Number.isNaN(date.getTime())) return index === 0 ? 'Сегодня' : '';
        if (index === 0) return 'Сегодня';
        return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long' });
    }

    function dayLabel(value, index) {
        var date = new Date(value + 'T12:00:00');
        if (Number.isNaN(date.getTime())) return '';
        var label = date.toLocaleDateString('ru-RU', { weekday: 'long' });
        return label.charAt(0).toLocaleUpperCase('ru-RU') + label.slice(1);
    }

    function renderForecast(daily) {
        if (!daily || !Array.isArray(daily.time)) return;
        var html = daily.time.slice(0, 7).map(function(date, index) {
            var code = Number((daily.weather_code || [])[index] || 0);
            var max = Math.round(Number((daily.temperature_2m_max || [])[index] || 0));
            var min = Math.round(Number((daily.temperature_2m_min || [])[index] || 0));
            return '<div class="blog-weather-card__day"><div><div class="blog-weather-card__date">' + formatDate(date, index) + '</div><div class="blog-weather-card__label">' + dayLabel(date, index) + '</div></div><div class="blog-weather-card__day-icon" aria-hidden="true">' + weatherSvg(code) + '</div><div class="blog-weather-card__range"><strong>' + max + '°</strong><span>' + min + '°</span></div></div>';
        }).join('');
        eachWeatherNode('[data-weather-forecast]', function(node) {
            node.innerHTML = html;
        });
    }

    function renderWeather(location, data, cacheState) {
        var current = data && data.current ? data.current : {};
        var code = Number(current.weather_code || 0);
        eachWeatherNode('[data-weather-temp]', function(node) { setText(node, Math.round(Number(current.temperature_2m || 0)) + '°'); });
        eachWeatherNode('[data-weather-summary]', function(node) { setText(node, descriptions[code] || 'Погода сейчас'); });
        eachWeatherNode('[data-weather-feels]', function(node) { setText(node, Math.round(Number(current.apparent_temperature || current.temperature_2m || 0)) + '°'); });
        eachWeatherNode('[data-weather-wind]', function(node) { setText(node, Math.round(Number(current.wind_speed_10m || 0)) + ' м/с'); });
        eachWeatherNode('[data-weather-humidity]', function(node) { setText(node, Math.round(Number(current.relative_humidity_2m || 0)) + '%'); });
        eachWeatherNode('[data-weather-precip]', function(node) { setText(node, Math.round(Number(current.precipitation || 0)) + ' мм'); });
        eachWeatherNode('[data-weather-updated]', function(node) { setText(node, 'Обновлено: ' + new Date().toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }) + (cacheState === 'cache' ? ' · кэш' : '')); });
        eachWeatherNode('[data-weather-icon]', function(node) { setIcon(node, code); });
        renderForecast(data.daily);
    }

    function loadWeather(location) {
        if (!isValidLocation(location)) {
            location = fallbackLocation;
        }

        var cityName = hasRealCity(location) ? location.city : fallbackLocation.city;
        eachWeatherNode('[data-weather-city]', function(node) { setText(node, cityName); });
        eachWeatherNode('[data-weather-place]', function(node) { setText(node, 'Прогноз для вашего города'); });

        var cachedWeather = readCachedWeather(location);
        if (cachedWeather) {
            renderWeather(location, cachedWeather, 'cache');
            logWeatherEvent('weather.cache', {
                city: cityName,
                latitude: Number(location.latitude),
                longitude: Number(location.longitude)
            });
            return Promise.resolve();
        }

        var url = '/api/weather-forecast/?latitude=' + encodeURIComponent(location.latitude) + '&longitude=' + encodeURIComponent(location.longitude) + '&v=' + encodeURIComponent(weatherEndpointVersion);
        logWeatherEvent('weather.request', {
            city: cityName,
            latitude: Number(location.latitude),
            longitude: Number(location.longitude)
        });
        return fetchJson(url, 4500).then(function(data) {
            writeCachedWeather(location, data);
            renderWeather(location, data, 'network');
            logWeatherEvent('weather.success', {
                city: cityName,
                latitude: Number(location.latitude),
                longitude: Number(location.longitude)
            });
        }).catch(function(error) {
            logWeatherEvent('weather.error', {
                city: cityName,
                latitude: Number(location.latitude),
                longitude: Number(location.longitude),
                status: error && error.status ? error.status : 0,
                message: error && error.name ? error.name : 'weather_error'
            });
            throw error;
        });
    }

    function localizeCity(location) {
        if (!location || !location.city || /[А-Яа-яЁё]/.test(location.city)) {
            return Promise.resolve(location || fallbackLocation);
        }

        var url = '/api/weather-geocode/?city=' + encodeURIComponent(location.city) +
            '&countryCode=' + encodeURIComponent(location.countryCode || '') +
            '&v=' + encodeURIComponent(weatherEndpointVersion);
        logWeatherEvent('geocode.request', {
            city: location.city,
            latitude: Number(location.latitude),
            longitude: Number(location.longitude)
        });

        return fetchJson(url, 3000).then(function(data) {
            if (data && data.city) {
                location.city = data.city;
            }
            logWeatherEvent('geocode.success', {
                city: location.city,
                latitude: Number(location.latitude),
                longitude: Number(location.longitude)
            });
            return location;
        }).catch(function(error) {
            logWeatherEvent('geocode.error', {
                city: location.city,
                status: error && error.status ? error.status : 0,
                message: error && error.name ? error.name : 'geocode_error'
            });
            return location;
        });
    }

    function getGeoLocation() {
        var cachedGeo = readCachedGeo();
        if (cachedGeo) {
            logWeatherEvent('geo.cache', {
                city: cachedGeo.city || '',
                latitude: Number(cachedGeo.latitude),
                longitude: Number(cachedGeo.longitude)
            });
            return Promise.resolve(cachedGeo);
        }

        logWeatherEvent('geo.request');
        return fetchJson('/api/weather-geoip/?v=' + encodeURIComponent(weatherEndpointVersion), 3500).then(function(geo) {
            if (!geo) throw new Error('geo');
            var location = {
                city: geo.city || fallbackLocation.city,
                countryCode: geo.country_code || '',
                latitude: Number(geo.latitude || fallbackLocation.latitude),
                longitude: Number(geo.longitude || fallbackLocation.longitude),
                fallback: !!geo.fallback
            };
            if (!isValidLocation(location)) throw new Error('geo');
            if (!location.fallback) {
                writeCachedGeo(location);
            }
            logWeatherEvent('geo.success', {
                city: location.city,
                latitude: Number(location.latitude),
                longitude: Number(location.longitude)
            });
            return location;
        }).catch(function(error) {
            logWeatherEvent('geo.error', {
                status: error && error.status ? error.status : 0,
                message: error && error.name ? error.name : 'geo_error'
            });
            return fallbackLocation;
        });
    }

    if (mobile && mobileToggle) {
        var savedOpen = false;
        try {
            savedOpen = window.localStorage.getItem(storageKey) === '1';
        } catch (error) {}
        setMobileOpen(savedOpen);
        mobileToggle.addEventListener('click', function() {
            setMobileOpen(!mobile.classList.contains('is-open'));
        });
    }

    forgetOldWeatherCache();
    logWeatherEvent('widget.init');
    getGeoLocation()
        .then(localizeCity)
        .then(loadWeather)
        .catch(function() {
            eachWeatherNode('[data-weather-summary]', function(node) { setText(node, 'Погода недоступна'); });
            eachWeatherNode('[data-weather-place]', function(node) { setText(node, 'Попробуем обновить позже'); });
            logWeatherEvent('widget.error');
        });
})();
