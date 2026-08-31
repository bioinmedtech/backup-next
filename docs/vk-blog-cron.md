# VK Blog Cron

Страница `/blog` читает кэш из `data/blog/posts.json`. RSS доступен по `/blog-rss.php`. Кэш обновляет CLI-скрипт:

Канонические адреса постов имеют формат `/blog/post/{vk_id}-{slug}/`, например `/blog/post/135-legkie-eto-ne-tolko-dyhanie/`. Старый формат `/blog/post/?id=135` остаётся совместимым и отдаёт 301 на ЧПУ.

```bash
BIOINMED_VK_SERVICE_TOKEN="..." php /var/www/bioinmed-next/scripts/fetch-vk-blog.php
```

Рекомендуемая cron-запись для обновления раз в 15 минут:

```cron
*/15 * * * * cd /var/www/bioinmed-next && /usr/bin/php scripts/fetch-vk-blog.php >> data/logs/vk-blog-cron.log 2>&1
```

Ключ можно хранить в переменной окружения `BIOINMED_VK_SERVICE_TOKEN` или в локальном файле `.env`:

```dotenv
BIOINMED_VK_SERVICE_TOKEN="..."
```

Файл `.env` уже игнорируется git.

Дополнительные настройки:

```dotenv
BIOINMED_VK_POST_COUNT=100
BIOINMED_VK_FETCH_COMMENTS=1
BIOINMED_VK_COMMENTS_POST_LIMIT=20
BIOINMED_VK_COMMENTS_COUNT=5
BIOINMED_VK_LOG_MAX_BYTES=524288
```

`wall.get` возвращает до 100 записей за запрос. Скрипт обновляет эти свежие записи и затем объединяет их с уже сохранённым кэшем, поэтому старые публикации из локального архива не удаляются при очередном запуске cron.

Комментарии подтягиваются только для последних публикаций, чтобы cron не создавал лишнюю нагрузку на VK API. Лог `data/logs/vk-blog-cron.log` автоматически обрезается при запуске скрипта, когда превышает `BIOINMED_VK_LOG_MAX_BYTES`; по умолчанию лимит 512 KB.
