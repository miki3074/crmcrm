# Карта проекта

CRM на **Laravel 10 + Inertia.js + Vue 3 + Tailwind**. Компании → проекты → задачи → подзадачи, клиенты и сделки, медиапланы, встречи, опросы, база знаний, хранилище файлов, внутренний чат, поддержка, Telegram-уведомления.

Общие инструкции по запуску и командам — в [CLAUDE.md](CLAUDE.md). Этот файл отвечает на вопрос «где что лежит».

---

## 1. Как устроен запрос

```
Браузер ──► routes/web.php ──► Inertia::render('Папка/Страница') ──► resources/js/Pages/Папка/Страница.vue
                                                                              │
                                                                              ▼  onMounted → axios
                                                         routes/api.php ──► app/Http/Controllers/API/*Controller
                                                                              │
                                                                              ▼
                                                                         app/Models/*  (MySQL)
```

- Большинство страниц — **оболочки**: маршрут в `web.php` передаёт максимум `id`, а данные страница сама грузит через `axios` из `/api/*`.
- Авторизация в API — **сессионная** (cookie), хотя в `api.php` встречается `auth:sanctum`.
- Глобальные props для всех страниц (`auth.user`, `auth.roles`, `unreadSupportCount`, `unreadChatCount`) — в [app/Http/Middleware/HandleInertiaRequests.php](app/Http/Middleware/HandleInertiaRequests.php).

---

## 2. Структура репозитория

| Путь | Что там |
|---|---|
| `app/Http/Controllers/` | Контроллеры страниц (Inertia): Klient*, Knowledge*, Meeting*, MediaPlan, Poll, Chat*, Profile |
| `app/Http/Controllers/API/` | JSON-контроллеры (39 шт.) — основная бизнес-логика |
| `app/Http/Controllers/Admin/`, `Support/` | Управление пользователями, админка поддержки |
| `app/Http/Middleware/` | `AdminOnly` (`admin.only`), `SupportOnly` (`support`), `CheckUserEmail` (`check.email`), `HandleInertiaRequests` |
| `app/Models/` | 63 модели (см. раздел 5) |
| `app/Policies/` | Политики: Client, Company, Klient, KlientDeal, Poll, Project, StorageFile, Subtask, Support, Task, TaskTemplate, User |
| `app/Services/` | `CompanyAccessService`, `KnowledgeAccessService`, `KnowledgeFolderAccessService`, `TaskActivityService`, `TelegramService` |
| `app/Jobs/` | 24 job'а рассылки уведомлений (`Send*Notification`) |
| `app/Notifications/` | 27 классов уведомлений (mail/database), база — `BaseNotification` |
| `app/Console/Commands/` | `SendTaskDueReminders` (`tasks:due-reminders`, каждую минуту), `MigrateCompanyRoles` |
| `app/Events/` | `FlutterMessageSent` (broadcast) |
| `routes/` | `web.php`, `api.php`, `auth.php`, `channels.php`, `console.php` |
| `database/migrations/` | 136 миграций |
| `resources/js/Pages/` | Страницы Inertia (раздел 3) |
| `resources/js/Layouts/` | `AuthenticatedLayout.vue` (сайдбар, тёмная тема, бейджи), `GuestLayout.vue` |
| `resources/js/Components/` | Старые общие компоненты (кнопки, инпуты, Modal, RichEditor, TaskChat, …) |
| `resources/js/Pages/AAA/Components/` | **Актуальные** доменные компоненты (раздел 4) |
| `resources/js/Pages/v2/`, `resources/js/NewComponents/` | Альтернативные/экспериментальные версии |
| `resources/js/Composables/` | `useDashboard.js`, `useToast.js` |
| `docker-compose.yml`, `Dockerfile`, `docker/` | Сервисы `app` + `db` |

> Файлы в корне `id()`, `onDelete('cascade')`, `string('name')`, `timestamps()`, `unsignedBigInteger('created_by')` — пустой мусор от ошибки в shell, не код.

---

## 3. Страницы: URL → Vue-файл

Все пути страниц относительно `resources/js/Pages/`.

### Основное
| URL | Страница | Примечание |
|---|---|---|
| `/` | `Welcome.vue` | |
| `/dashboard` | `Dashboard.vue` | **Рабочий стол** (живой) |
| `/dashboardold` | `Dashboard22.vue` | Старая версия |
| `/home` | `v2/Dashboard` | Альтернативная версия |
| `/companies/{id}` | `Companies/Show.vue` | `/companies2/{id}` → `Show22` |
| `/projects/{id}` | `Projects/Show.vue` | `/projects2/{id}` → `Show22` |
| `/subprojects/{id}` | `Subprojects/Show.vue` | |
| `/tasks/{id}` | `Tasks/Show.vue` | `/tasks2/{id}` → `Show22` |
| `/subtasks/{id}` | `Subtasks/Show.vue` | `/subtasks2/{id}` → `Show22` |
| `/calendar` | `Calendar/Index.vue` | |
| `/taskpull` | `Taskspull/TaskSummary.vue` | |
| `/completed` | `Tasks/CompletedList.vue` | через `CompletedTasksController` |
| `/watching` | `WatchingPage.vue` | Задачи, где пользователь наблюдатель |
| `/employees` | `Employees.vue` | |
| `/mapdiagram` | `CompanyDiagram.vue` | Схема компании (vue-flow + dagre) |
| `/task-templates` | `TaskTemplates/Indexs.vue` | |
| `/profile` | `Profile/Edit.vue` | |

### Клиенты и продажи
| URL | Страница |
|---|---|
| `/klients`, `/klients/create`, `/klients/{id}`, `/klients/{id}/edit` | `Klients/Index`, `Create`, `Show`, `Edit` |
| `/deals`, `/klients/{id}/deals/create`, `/klient-deals/{id}`, `/klient-deals/{id}/edit` | `Klients/Deals/Index`, `Create`, `Show`, `Edit` |
| `/klient-tasks/{id}` | `Klients/Tasks/Show` |
| `/media-plans/{id}` | через `MediaPlanController@show` → `MediaPlans/*` |
| `/clients`, `/clients/{id}` | `Clients/Index`, `Clients/Show` (**старая** система клиентов) |
| `/contracts` | `Contracts/Index` |
| `/sales-standards` | `SalesStandards/Index` (данные — `resources/js/Data/salesStandards.js`) |

### Встречи, опросы, документы
| URL | Страница |
|---|---|
| `/meetings`, `/meetings/create`, `/meetings/{id}` | `Meetings/Index`, `Create`, `Show` |
| `/meeting-documents` | `MeetingDocuments/*` |
| `/polls/*` (resource) | через `PollController` |

### База знаний (`KnowledgeBaseController` и др.)
| URL | Страница |
|---|---|
| `/knowledge` | `Knowledge/Companies` |
| `/companies/{c}/knowledge` | `Knowledge/Index` |
| `/companies/{c}/knowledge/folders/{f}` | `Knowledge/Folder` |
| `/companies/{c}/knowledge/articles/{a}` (+ `/create`, `/edit`) | `Knowledge/Articles/Show`, `Create`, `Edit` |
| `/companies/{c}/knowledge/access` | `Knowledge/Access` |

### Хранилище, чат, поддержка
| URL | Страница |
|---|---|
| `/file-storage`, `/file-storage/companies/{id}` | `Storage/Index`, `Storage/Company` |
| `/chat/{type?}/{targetId?}` | `Chat/Index` (через `ChatController`) |
| `/chatai` | `Chat.vue` |
| `/supportmessages` | `Supporttwo/Chat` — обращение пользователя |
| `/support/admin` | `Supporttwo/Admin` — админка поддержки (`support`) |
| `/support-history` | `SupportHistory/supporthistory` |

### Администрирование и прочее
| URL | Страница |
|---|---|
| `/admin/users/*` | `Admin/Users/Index`, `Create`, `Edit`, `Show` |
| `/users` | `Users/Index` — доступ только для одного захардкоженного email |
| `/ruk` | `Ruk.vue` |
| `/formzak` | `LegislationForm.vue` |
| `/docs`, `/licen` | Blade-шаблоны `resources/views/docs`, `license-terms` (не Inertia) |
| `/forgot-password`, `/reset-password/{token}` + `routes/auth.php` | `Auth/*` |

---

## 4. Компоненты фронтенда

Одноимённые компоненты лежат в нескольких местах — **сначала смотрите импорт в странице**.

| Папка | Содержимое | Кто использует |
|---|---|---|
| `Pages/AAA/Components/Dashboard/` | StatCard, CompanySection, ProjectsSummary, TasksSummary, SubtasksSection, TasksBoard, ActivityFeed, SearchOverlay, CreateCompanyModal, EmailVerificationModal, TelegramBinding | `Dashboard.vue`, `Dashboard22.vue` |
| `Pages/AAA/Components/Company/` | CompanyHero, CompanyMembersModal, CompanyReminderModal, CreateProjectModal, Poll*Modal, ProjectGrid, ProjectDurationChart, TaskProgressChart | `Companies/Show` |
| `Pages/AAA/Components/Project/` | ProjectHeader, ProjectMenu, ProjectSidebar, ProjectTasks, ProjectActionPanel | `Projects/Show` |
| `Pages/AAA/Components/Task/` | TaskHero, TaskSidebar, TaskStats, TaskSubtasks, TaskResults, TaskFilesHub, TaskAttachments, TaskDocumentApproval, TaskControlPanel, AddResultModal, SelectReviewerModal, ConfirmDialog, `Modals/` | `Tasks/Show` |
| `Pages/AAA/Components/Subtask/` | SubtaskHeader, SubtaskDescription, SubtaskFiles, SubtaskMembers, SubtaskProgress, SubtaskChildren | `Subtasks/Show` |
| `Pages/AAA/Components/Meeting/` | MeetingHeader, MeetingAgenda, MeetingParticipants, MeetingProtocol, MeetingMyStatus, MeetingDocumentss, MeetingSettingsModal | `Meetings/Show` |
| `Pages/AAA/Components/Klients/` | CreateTaskDrawer, TaskCard | `Klients/*` |
| `Components/` | Базовые UI (PrimaryButton, TextInput, Modal, Dropdown…), RichEditor, MentionInput, TaskChat, TaskChecklists, SubtaskChecklist/Comments, PollCreateModal, SupportButton, ToastContainer, `Company/`, `Dashboard/`, `Telegram/`, `Shared/` | Разные страницы, layout |
| `Pages/v2/Components/` | Копия дерева AAA (старое/альтернативное) | `v2/*` |
| `NewComponents/Dashboard/` | Ещё один вариант дашборда | — |

---

## 5. Доменная модель

### Основная иерархия работ
```
Company ─┬─ Project ─┬─ Subproject ── Task
         │           └─ Task ─┬─ Subtask ── Subtask (вложенные)
         │                    │     ├─ SubtaskChecklist / SubtaskComment / SubtaskFile
         │                    ├─ TaskChecklist (+ TaskChecklistFile)
         │                    ├─ TaskComment
         │                    ├─ TaskFile (+ FileComment, согласование)
         │                    ├─ TaskResult
         │                    └─ TaskActivity  (лента событий дашборда)
         ├─ User (участники компании; роли через spatie/permission)
         └─ StorageFile (хранилище)
```
Участники задачи: исполнители, ответственные, наблюдатели, создатель (`belongsToMany User`).
`TaskTemplate` (+ `TaskTemplateFile`) — шаблоны задач.

### Клиенты — **две параллельные системы**
| Новая (`Klient*`) — актуальная | Старая |
|---|---|
| `Klient` → `KlientContactPerson`, `KlientFile`, `KlientTask` (+ `KlientTaskFile`), `KlientDeal` (+ `KlientDealItem`, `KlientDealFile`), `MediaPlan` (+ `MediaPlanItem`) | `Client`, `Interaction`, `Deal`, `Contract` (+ `ContractFile`) |
| Страницы `Klients/*`, `/deals` | Страницы `Clients/*`, `/contracts` |

Справочники для медиапланов: `RadioStation`, `City`, `Producer`, `Buyer`.

### Остальные домены
| Домен | Модели |
|---|---|
| Встречи | `Meeting` → `MeetingDocument`, `MeetingDocumenttwo`; связана с `Task`/`Subtask` |
| Опросы | `Poll` → `PollParticipant`, `PollProblem` → `PollProblemComment` |
| База знаний | `KnowledgeFolder`, `KnowledgeArticle`, `KnowledgeFile`, `KnowledgeFolderRole`, `KnowledgeCompanyRole` |
| Чат | `Chat` → `ChatMessage`, `ChatGroup`, `Message`, `FlutterMessage` |
| Поддержка | `SupportThread` → `SupportMessagetwo`, `SupportAttachmenttwo`; старые `SupportMessage`, `SupportReply`, `SupportReplyAttachment`, `SupportAttachment` |
| Календарь | `Event` |

---

## 6. API (`routes/api.php`)

Основные группы маршрутов (в скобках — примерное число эндпоинтов):

| Префикс | Контроллер | Назначение |
|---|---|---|
| `/api/tasks` (37) | `TaskController`, `TaskCommentController`, `TaskChecklistController`, `TaskDescriptionController` | CRUD задач, файлы, результаты, участники, старт/завершение, комментарии, чек-листы |
| `/api/subtasks`, `/api/subtask-files` (16) | `SubtaskController`, `SubtaskChecklistController` | Подзадачи, файлы и их согласование, участники |
| `/api/files`, `/api/file-comments` | `TaskController` | Согласование файлов задач |
| `/api/companies` (21) | `CompanyController`, `CompanyKnowledgeController` | Компании, участники, логотипы |
| `/api/projects`, `/api/subprojects` (23) | `ProjectController`, `SubprojectController` | Проекты |
| `/api/dashboard` (4) | `CompanyController@summary`, `TaskController` | `summary` для дашборда, `activities` (лента событий) |
| `/api/polls` (15) | `PollController` | Опросы |
| `/api/task-templates` (13) | `TaskTemplateController` | Шаблоны задач |
| `/api/contracts` (11) | `ContractController` | Договоры |
| `/api/support`, `/api/threads` (18) | `Support*Controller` | Поддержка; `/api/support/admin/*` — middleware `support` |
| `/api/calendar` (5) | `CalendarController`, `TaskCalendarController` | Календарь |
| `/api/storage/*` | `StorageController` | Файловое хранилище |
| `/api/clients`, `/api/producers`, `/api/buyers` | `ClientController`, `ProducerBuyerController` | Старые клиенты, справочники |
| `/api/employees`, `/api/users` | `EmployeeController`, `UserController`, `UserManagementController` | Сотрудники |
| `/api/chats`, `/api/messages` | `ChatListController`, `FlutterMessageController` | Чат |
| `/api/email/*`, `/api/password/*`, `/api/login`, `/api/register` | `EmailVerificationController`, `PasswordResetController`, `AuthController` | Аутентификация и подтверждение email |
| `/api/telegram/webhook` | `TelegramController@handle` | Webhook Telegram-бота |
| `/api/mapdiagram/{company}/map` | `CompanyMapController` | Данные для схемы |

Часть «API» для клиентов, базы знаний и медиапланов живёт в **`web.php`** (Inertia-формы и `router.post/put`), а не в `api.php`.

---

## 7. Фоновые процессы и уведомления

| Механизм | Где | Что делает |
|---|---|---|
| Планировщик | `app/Console/Kernel.php` | `tasks:due-reminders` каждую минуту — напоминания о сроках |
| Очередь | `app/Jobs/*` | Рассылка уведомлений о назначениях, комментариях, встречах, опросах, поддержке и т.п. Нужен `php artisan queue:work` |
| Уведомления | `app/Notifications/*` | Почта/БД-уведомления |
| Telegram | `app/Services/TelegramService.php`, job `SendTelegramNotification` | Сообщения в Telegram (нужен `telegram_chat_id` у пользователя) |
| Лента событий | `app/Services/TaskActivityService.php` → `task_activities` | События по задачам для блока «События» на дашборде |
| WebSockets | Laravel Reverb, `routes/channels.php` (`App.Models.User.{id}`), event `FlutterMessageSent` | Чат и поддержка в реальном времени. Нужен `php artisan reverb:start` |
| PDF | `barryvdh/laravel-dompdf` | Экспорт документов |

---

## 8. Доступы

- **Роли** — `spatie/laravel-permission` (`admin`, роль поддержки и др.); на фронте — `usePage().props.auth.roles`.
- **Middleware**: `auth`, `verified`, `admin.only`, `support`, `check.email`.
- **Политики** — `app/Policies/*` (Company, Project, Task, Subtask, Klient, KlientDeal, Poll, StorageFile, Support, TaskTemplate, User, Client).
- **Доступ к базе знаний и компаниям** — `CompanyAccessService`, `KnowledgeAccessService`, `KnowledgeFolderAccessService`.

---

## 9. Ловушки

1. **Дубли страниц**: `*/Show.vue` (живые) и `*/Show22.vue` (старые, на маршрутах `*2/{id}`); `Dashboard.vue` и `Dashboard22.vue`; дерево `v2/`. Меняя «страницу задачи», правьте `Tasks/Show.vue`.
2. **Дубли компонентов**: `Components/`, `Pages/AAA/Components/`, `Pages/v2/Components/`, `NewComponents/` — проверяйте импорт.
3. **Две системы клиентов** — `Klient*` (новая) и `Client`/`Deal` (старая).
4. **Две системы поддержки** — модели с суффиксом `two` (`SupportMessagetwo`, …) и `Supporttwo/*` — актуальные.
5. **Страницы без маршрута**: `Pages/12.vue` (конструктор законопроекта) и `Pages/fdsfsd.vue` (редактор) нигде не подключены — черновики. Плюс пустые файлы-артефакты в корне репозитория.
6. **Проверка корректности фронтенда** — только `npm run build`: линтера и тестов для JS нет.

---

## 10. Уведомления

Универсальная система: одно событие → сайт (колокольчик и всплывающие окна), push на ПК/Android/iPhone, Telegram, почта — по настройкам каждого получателя.

### Как отправить из любого места
```php
use App\Notify\Notify;

Notify::type('task.file_added')          // «категория.событие»
    ->to($userIds)                        // id или коллекция id
    ->title($task->title)                 // название сущности
    ->message('Добавлен файл «отчёт.pdf»')
    ->link("/tasks/{$task->id}")          // куда вести по клику (можно null)
    ->from(auth()->id())                  // автор — ему не отправляется
    ->except($alreadyNotifiedIds)         // необязательно
    ->collapse()                          // необязательно: заменить своё непрочитанное (чат)
    ->send();
```
- Новый тип работает сразу. Чтобы он появился в настройках пользователя — добавьте его в `types` в [config/notify.php](config/notify.php) (там же можно задать каналы по умолчанию для типа).
- Списки участников (задача, подзадача, сделка, команда клиента, медиаплан) — в [SiteNotificationService.php](app/Services/SiteNotificationService.php); его `send()` — короткая форма того же вызова.
- Новая категория — добавьте её в `categories` в конфиге и в `CATEGORIES`/`CATEGORY_LABELS` в [NotificationsModal.vue](resources/js/Components/Notifications/NotificationsModal.vue).

### Из чего состоит
| Часть | Где |
|---|---|
| Ядро: построитель, настройки получателей | `app/Notify/` (`Notify`, `PendingNotification`, `NotificationPreferences`) |
| Каналы push / Telegram / почта | `app/Notify/Channels/`, доставка — job `DeliverNotification` (без воркера очереди выполняется после ответа) |
| Хранение | `site_notifications`, `push_subscriptions`, `notification_preferences` |
| API | `/api/notifications*`, `/api/push-subscriptions*` (`SiteNotificationController`, `PushSubscriptionController`, `NotificationSettingsController`) |
| Фронтенд | `Composables/useNotifications.js` (опрос раз в 20 с, всплывающие), `Composables/usePush.js`, `Components/Notifications/*`, страница `/notifications/settings` |
| PWA / push в браузере | `public/sw.js`, `public/manifest.webmanifest`, `public/icons/` |
| Напоминания о сроках по клиентам | команда `klients:due-reminders` (ежечасно) |

### Развёртывание push
1. Ключи: `php artisan notify:vapid` → `VAPID_PUBLIC_KEY` / `VAPID_PRIVATE_KEY` в `.env`. Не менять после запуска — все подписки перестанут работать.
2. Сайт по **HTTPS** (кроме `localhost`/`127.0.0.1` для разработки).
3. **Windows/XAMPP**: системная переменная окружения `OPENSSL_CONF=D:\xampp\php\extras\ssl\openssl.cnf`, затем перезапуск Apache. Без неё PHP не может шифровать push (страница настроек это показывает). На Linux не нужно.
4. Рекомендуется `QUEUE_CONNECTION=database` + `php artisan queue:work`, чтобы push/почта не задерживали ответы.
5. iPhone: iOS 16.4+, сайт добавлен «На экран Домой» и запущен с иконки.

### Telegram и почта
Каналы есть, но по умолчанию выключены: многие события ещё шлют их напрямую из контроллеров (`TelegramService::sendMessage`, `Send*Notification`). Переводя такое место на `Notify`, удалите прямой вызов и включите канал для типа через `'defaults' => ['telegram' => true]`.
