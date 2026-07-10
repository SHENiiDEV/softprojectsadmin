# Архитектура проекта CRM Compliance Hub (softproject-admin)

Данный документ содержит детальное описание архитектуры, структуры базы данных, связей между моделями, функционала и ключевых процессов проекта.

---

## 1. Общий обзор системы
Проект представляет собой CRM-систему (Compliance Hub) для администрирования проектов, клиентов, задач и комплаенса. 
Основные цели системы:
- Управление онбордингом и комплаенсом проектов/компаний (KYB, CFS, верификация банков и реестров).
- Трекинг задач с использованием Kanban-доски и детальным логированием рабочего времени сотрудников.
- Интеграция с Telegram для оперативных уведомлений (двусторонняя связь через Webhook, привязка аккаунтов).
- Предоставление защищенного клиентского портала для внешних пользователей без необходимости полноценной регистрации в системе.
- Безопасное зашифрованное хранение учетных данных (`Credential Vault`).

---

## 2. Модели и связи базы данных

Ниже приведена детальная схема отношений между сущностями:

```mermaid
erDiagram
    User ||--o{ Task : "creator / assignee"
    User ||--o{ TaskTimeLog : "logs time"
    User ||--o{ Project : "manages"
    User ||--o{ ActivityLog : "performs actions"

    Client ||--o{ Project : "owns"
    Client ||--o{ Comment : "writes"

    Project ||--o{ Website : "has"
    Project ||--o{ Credential : "has"
    Project ||--o{ Task : "has"
    Project ||--o{ ActivityLog : "has logs"
    Project ||--o1 Director : "has"
    Project ||--o1 Boarding : "has"
    Project ||--o1 Report : "has"

    Website ||--o{ Credential : "has"
    Website ||--o| Company : "associated with"

    Company ||--o{ Credential : "has"
    Company ||--o{ Project : "has"

    Task ||--o{ Comment : "has"
    Task ||--o{ TaskTimeLog : "has"
    Task ||--o{ ActivityLog : "has"
    Task ||--o{ Media : "attaches files"
    
    Comment ||--o{ Comment : "replies (parent_id)"
```

### Подробное описание моделей:

1. **User (Пользователи/Сотрудники)**
   - Хранит данные сотрудников, `telegram_id`, настройки уведомлений (`notification_settings`), токен привязки (`tg_link_token`).
   - Использует `Spatie\Permission\Traits\HasRoles` для авторизации (роли: `admin`, `manager`, `curator`).
   - Связи:
     - `createdTasks()`: `HasMany` к `Task` (созданные задачи).
     - `assignedTasks()`: `HasMany` к `Task` (назначенные задачи).

2. **Client (Клиенты)**
   - Владельцы проектов. Имеют поле `hash` для авторизации на гостевом портале.
   - Связи:
     - `companies()`: `HasMany` к `Project` (проекты клиента, внешние компании).

3. **Project (Проекты / Компании в контексте комплаенса)**
   - Основная сущность комплаенса и рабочего процесса. Хранит статус, тип MCC, телефоны, email-адреса, контакты UBO (конечного бенефициара).
   - Связи:
     - `client()`: `BelongsTo` к `Client`.
     - `manager()`: `BelongsTo` к `User` (менеджер проекта).
     - `websites()`: `HasMany` к `Website`.
     - `director()`: `HasOne` к `Director` (директор компании).
     - `boarding()`: `HasOne` к `Boarding` (статусы верификации).
     - `credentials()`: `HasMany` к `Credential`.
     - `report()`: `HasOne` к `Report`.
     - `tasks()`: `HasMany` к `Task`.

4. **Company (Юридические лица)**
   - Вспомогательная модель. Используется для хранения официальных реквизитов компаний и расчета их независимого индекса здоровья.
   - Связи:
     - `website()`: `BelongsTo` к `Website`.
     - `credentials()`: `HasMany` к `Credential`.
     - `projects()`: `HasMany` к `Project`.

5. **Task (Задачи)**
   - Карточки задач на Kanban-доске. Поддерживают приоритеты (`low`, `medium`, `high`, `critical`), статусы (`todo`, `in_progress`, `review`, `done`), дедлайны (`due_date`) и ручную сортировку (`order`).
   - Реализует медиа-коллекцию `documents` через `Spatie\MediaLibrary`.
   - При создании/изменении полей триггерит системные события (`ActivityLog`) и отправляет уведомления через `NotificationService`.
   - Связи:
     - `project()`: `BelongsTo` к `Project`.
     - `creator()`: `BelongsTo` к `User`.
     - `assignee()`: `BelongsTo` к `User`.
     - `comments()`: `HasMany` к `Comment` (корневые комментарии).
     - `timeLogs()`: `HasMany` к `TaskTimeLog`.

6. **Credential (Учетные данные / Доступы)**
   - Безопасное хранилище паролей и токенов. Поле `password` шифруется встроенным механизмом Laravel (`encrypted` cast). Поддерживает флаг `is_secret`.
   - Связи:
     - `project()`: `BelongsTo` к `Project`.
     - `website()`: `BelongsTo` к `Website`.

7. **Boarding (Верификационный статус)**
   - Хранит даты прохождения KYB (`kyb_completed_at`), онбординга (`boarding_completed_at`) и верификации (CFS, Bank, Companies House).

8. **Director, Website, Report**
   - Вспомогательные комплаенс-сущности для хранения информации о директорах компаний, адресах сайтов (и их статусах) и отчётах соответственно.

9. **TaskTimeLog (Логирование времени)**
   - Учет рабочего времени сотрудников. Содержит поля `started_at`, `stopped_at` и `duration_seconds`.
   - Таймер может быть запущен только один раз на одного пользователя параллельно.

10. **Comment (Комментарии к задачам)**
    - Поддерживают вложенность через связь `parent_id`.
    - Могут быть приватными (`is_private`), видимыми только сотрудникам.
    - Поддерживают упоминания пользователей в формате `@имя`.

11. **ActivityLog (Логи активности)**
    - Фиксирует действия пользователей: создание задач, смена исполнителя, изменение статуса, запуск/остановка таймеров.

---

## 3. Функциональные модули приложения

### A. Управление задачами (Kanban Board)
- **Файл**: [KanbanBoard.php](file:///Users/mihailssegins/softproject-admin/app/Livewire/Tasks/KanbanBoard.php)
- **Возможности**:
  - Drag-and-drop перетаскивание задач между колонками (статусами).
  - Быстрое создание задач.
  - Поиск и фильтрация по проектам, приоритетам, исполнителям.
  - Детальное модальное окно задачи с комментариями, вложенными файлами, историей изменений и запуском/остановкой трекера времени.

### B. Личный кабинет сотрудника (My Work)
- **Файл**: [MyWork.php](file:///Users/mihailssegins/softproject-admin/app/Livewire/MyWork.php)
- **Возможности**:
  - Агрегатор личных задач сотрудника (назначенные задачи, просроченные дедлайны).
  - Сводка залогированного времени за день, неделю и месяц.
  - Список последних действий пользователя.

### C. Центр контроля дедлайнов (Deadline Center)
- **Файл**: [DeadlineCenter.php](file:///Users/mihailssegins/softproject-admin/app/Livewire/DeadlineCenter.php)
- **Возможности**:
  - Отслеживание критических сроков по задачам и комплаенс-процессам.
  - Цветовая маркировка приближающихся сроков.

### D. Здоровье компании (Company Health Score)
- **Файл**: [CompanyHealthScore.php](file:///Users/mihailssegins/softproject-admin/app/Livewire/CompanyHealthScore.php)
- **Алгоритм**:
  - Расчет индекса здоровья проекта (0–100%) на основе заполненности комплаенс-данных.
  - Проверяются 9 критериев:
    1. Наличие привязанного сайта.
    2. Заполненность данных директора.
    3. Завершение KYB верификации.
    4. Завершение онбординга.
    5. Прохождение CFS верификации.
    6. Прохождение банковской верификации.
    7. Прохождение проверки Companies House.
    8. Наличие сгенерированного отчета.
    9. Наличие хотя бы одних учетных данных (`credentials`).

### E. Сейф паролей (Credential Vault)
- **Файл**: [CredentialVault.php](file:///Users/mihailssegins/softproject-admin/app/Livewire/CredentialVault.php)
- **Возможности**:
  - Просмотр, добавление и редактирование паролей проектов.
  - Маскирование секретных паролей.
  - Хранение дополнительных кастомных полей в формате JSON.

---

## 4. Система уведомлений и интеграция с Telegram

Уведомления отправляются в реальном времени внутри интерфейса (In-app notifications) и дублируются в Telegram.

### Схема работы Telegram Webhook:
1. Администратор или менеджер создает ссылку для привязки Telegram в личном профиле. Ссылка содержит токен `tg_link_token` (например, `https://t.me/BotName?start=TOKEN`).
2. При переходе по ссылке Telegram отправляет команду `/start TOKEN` на вебхук `/telegram/webhook`.
3. Контроллер `TelegramWebhookController` вызывает `TelegramService::handleUpdate()`.
4. `TelegramService` находит пользователя с данным `tg_link_token` и записывает его `telegram_id` и `telegram_username` в базу данных. Аккаунт успешно привязан.
5. При возникновении событий отправки уведомлений (`NotificationService`):
   - Проверяются настройки пользователя (`tg_notify_task_assigned`, `tg_notify_task_status_updated`, `tg_notify_new_comment`, `tg_notify_timer_action` и т.д.).
   - Формируется форматированное сообщение (с экранированием MarkdownV2).
   - В очередь ставится джоба `SendTelegramMessageJob` для асинхронной отправки.

---

## 5. Гостевой клиентский портал (Client Portal)
- **Роут**: `/portal/{hash}` (компонент `ClientPortal.php`)
- **Как это работает**:
  - Клиентам отправляется персональная ссылка, содержащая уникальный хэш.
  - Клиент получает доступ к ограниченному интерфейсу без логина и пароля.
  - Клиент видит только свои проекты (компании) и задачи по ним.
  - Клиент может создавать новые задачи (которые отображаются у менеджеров как "Support Tickets").
  - Клиент может писать комментарии к задачам (приватные комментарии сотрудников скрыты от клиента).
