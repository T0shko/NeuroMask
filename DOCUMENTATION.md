# Документация на проекта

## 1. Заглавие
**NeuroMask** — уеб платформа за AI смяна на лица (face swap).

## 2. Автор
Теодор Василев

## 3. Тематика
Онлайн магазин за AI услуга — потребителите купуват абонамент и качват снимки, върху които системата сменя лица с помощта на изкуствен интелект. Продуктите са абонаментните планове (Basic, Pro, Ultra), а „поръчките“ са обработените снимки.

## 4. Използвани технологии
- **Frontend:** HTML, CSS, Vanilla JavaScript, face-api.js
- **Backend:** PHP 8 (MVC структура)
- **База данни:** MySQL 8
- **AI engine:** Python 3.12 — InsightFace (`inswapper_128.onnx`), GFPGAN, RealESRGAN
- **Плащания:** Stripe Checkout (чрез cURL, без Composer)
- **Сървър:** Laragon (Apache + MySQL на Windows)

## 5. Страници и функции

### Публична част (`public/`)
- `index.php` — начална страница с кратко представяне.
- `register.php` / `login.php` — регистрация и вход. Логинът поддържа и **face login** през уебкамера (автоматично влизане след 4 последователни разпознавания).
- `plans.php` — абонаментни планове. Плащанията минават през Stripe.
- `upload.php` — качване на две снимки (source face + target) и избор на режим Fast или HQ.
- `dashboard.php` — последни поръчки (jobs) на потребителя.
- `jobs.php` — цялата история от обработки със статус (pending / processing / completed / failed).
- `profile.php` — редакция на профил + регистрация на лице за face login.
- `contact.php` — форма за контакти.

### Админ панел (`admin/`)
- `index.php` — статистики.
- `users.php` — CRUD на потребители.
- `jobs.php` — преглед на всички обработки.
- `subscriptions.php` — CRUD на абонаментни планове.
- `contacts.php` — съобщения от контактната форма.

### AI услуга (`ai/process.py`)
- **Fast mode** (~3–5s): 2-pass inswapper + GFPGAN.
- **HQ mode** (~15–30s): 3-pass inswapper + RealESRGAN ×2 + GFPGAN + LAB color match + Poisson seamless clone.

## 6. База данни

База: `neuromax` (MySQL, utf8mb4).

| Таблица | За какво е |
|---|---|
| `users` | акаунти (id, name, email, password, role: user/admin, avatar) |
| `face_data` | биометрични дескриптори за face login, свързани с user |
| `subscriptions` | планове — име, цена, features (JSON), max_jobs |
| `user_subscriptions` | връзка user ↔ план, start_date, end_date, status + Stripe полета |
| `jobs` | AI поръчки — source_path, file_path, result_path, effect, status, error_msg |
| `contacts` | съобщения от контактната форма |

Релации: `face_data.user_id`, `user_subscriptions.user_id`, `user_subscriptions.subscription_id`, `jobs.user_id` — всички с `ON DELETE CASCADE`.

## 7. Екранни кадри

- `docs/screenshots/home.png` — начална страница
- `docs/screenshots/login.png` — face login с уебкамера
- `docs/screenshots/plans.png` — абонаментни планове
- `docs/screenshots/upload.png` — качване на снимки
- `docs/screenshots/dashboard.png` — потребителски dashboard
- `docs/screenshots/result.png` — резултат от face swap
- `docs/screenshots/admin.png` — админ панел

## 8. Изводи и самооценка

Проектът покрива всичко, което се иска — MVC, база с релации, две роли, реални плащания през Stripe и нещо повече: работещ AI engine на локална GPU и вход с лице.

Най-трудното беше AI частта. Пробвах дифузионни модели (InstantID + SDXL), но те **генерират** лице, което прилича, не е същото. Затова останах на `inswapper` + enhancement pipeline — идентичността се пази 1:1. Също пробвах CUDA с Python + PyTorch, но счупих Python-а и бих се гръмнал. 

Какво научих: Python + ONNX + CUDA, как да комбинирам няколко AI модела в един pipeline. Да се гръмна

Какво може по-добре: да има опашка за jobs (в момента обработката е синхронна), по-сериозно rate limiting и тестове.

**Самооценка: Отличен 6.** Проектът работи end-to-end, има реален AI, реални опции за абонаментни планове и плащане с Stripe, и реален биометричен вход — не е просто CRUD.
