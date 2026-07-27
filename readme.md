# yii2-cms-maillog

Модуль просмотра писем, которые Yii2 складывает в файлы при `useFileTransport => true`
(файловый транспорт мейлера). Только чтение, без внешних зависимостей — весь разбор `.eml`
выполняется средствами ядра PHP.

## Возможности

- Список писем с пагинацией; новые сверху.
- Рекурсивный обход каталога: поддерживаются и плоская раскладка (`@runtime/mail/*.eml`),
  и шардирование по подпапкам (`@runtime/mail/Y-m-d/H/*.eml`).
- Разбор заголовков (unfolding, MIME-encoded-words в `Subject`/`From`/`To`).
- Декодирование тела: `quoted-printable`, `base64`, `7bit`/`8bit`; любой charset → UTF-8.
- `multipart/*` (alternative/mixed/related) — извлекаются `text/plain` и `text/html`.
- HTML-часть показывается в изолированном `sandbox`-iframe с жёстким `Content-Security-Policy`
  (скрипты и активные ресурсы отключены).

Про «шифр» вида `=D0=A1=D1=82...`: это не шифрование, а штатная кодировка **Quoted-Printable**,
снимается встроенной `quoted_printable_decode()`.

## Каталог писем

Путь берётся в следующем порядке приоритета:

1. Настройка `modules.Maillog.params.path` (модуль настроек `yii2-cms-config`) — абсолютный путь
   или Yii-алиас.
2. Если не задана — из конфигурации мейлера: `Yii::$app->mailer->fileTransportPath`
   (по умолчанию `@runtime/mail`).

## Чего модуль намеренно НЕ делает

- Не удаляет письма и не чистит каталог — этим занимается `yii2-cms-clear-manager`.
- Не отправляет письма — это просмотрщик уже сохранённых файлов.

## Маршруты (backend)

- `Maillog/backend/mail/index` — список.
- `Maillog/backend/mail/view?id=<slug>` — письмо.
- `Maillog/backend/mail/html?id=<slug>` — сырая HTML-часть для iframe (внутренний).
