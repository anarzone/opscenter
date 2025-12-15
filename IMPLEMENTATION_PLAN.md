# OpsCenter Implementation Plan

## 📊 Database Models & Schema

### 1. **WebhookSource** (Webhook Providers)
Stores configuration for each webhook provider (GitHub, Stripe, etc.)

**Fields:**
- `id` (bigint, PK)
- `name` (string) - Display name: "GitHub", "Stripe", etc.
- `slug` (string, unique, indexed) - URL-friendly identifier: "github", "stripe"
- `secret_key` (string, encrypted) - For HMAC signature validation
- `is_active` (boolean, default: true)
- `timestamps`

**Relationships:**
- `hasMany` WebhookEvent

---

### 2. **WebhookEvent** (Raw Incoming Events)
Stores every incoming webhook with full payload for audit trail

**Fields:**
- `id` (bigint, PK)
- `webhook_source_id` (FK to WebhookSource)
- `event_type` (string) - e.g., "push", "payment.success", "pull_request"
- `payload` (json) - Complete raw webhook data
- `headers` (json) - HTTP headers from request
- `signature` (string, nullable) - HMAC signature for verification
- `ip_address` (string, indexed)
- `status` (enum: pending, processing, processed, failed)
- `processed_at` (timestamp, nullable)
- `timestamps`

**Indexes:**
- `webhook_source_id`
- `status`
- `created_at`

**Relationships:**
- `belongsTo` WebhookSource
- `hasOne` Alert

---

### 3. **Alert** (Processed Events for Dashboard)
Human-readable alerts extracted from webhook events

**Fields:**
- `id` (bigint, PK)
- `webhook_event_id` (FK to WebhookEvent)
- `title` (string) - Short summary: "New push to main", "Payment failed"
- `message` (text) - Detailed description
- `severity` (enum: info, warning, error, critical)
- `metadata` (json, nullable) - Extracted useful data (repo name, amount, user, etc.)
- `is_read` (boolean, default: false)
- `timestamps`

**Indexes:**
- `severity`
- `is_read`
- `created_at`

**Relationships:**
- `belongsTo` WebhookEvent
- `hasMany` NotificationLog

---

### 4. **NotificationLog** (Sent Notifications)
Tracks all notifications sent via Telegram, Email, Slack

**Fields:**
- `id` (bigint, PK)
- `alert_id` (FK to Alert)
- `channel` (enum: telegram, email, slack)
- `recipient` (string) - Chat ID, email address, webhook URL
- `status` (enum: sent, failed)
- `error_message` (text, nullable)
- `timestamps`

**Indexes:**
- `alert_id`
- `status`

**Relationships:**
- `belongsTo` Alert

---

## 🔧 Laravel Backend Components

### Controllers

#### 1. WebhookController
**Location:** `app/Http/Controllers/Api/WebhookController.php`

**Route:** `POST /api/webhooks/{source}`

**Method:** `handle(Request $request, string $source)`

**Responsibilities:**
1. Find WebhookSource by slug
2. Validate HMAC signature using WebhookSignatureValidator
3. Store WebhookEvent with status=pending
4. Dispatch ProcessWebhookJob to queue
5. Return `200 OK` immediately (< 100ms response time)

**Error Handling:**
- 404 if source not found
- 401 if signature invalid
- 500 for other errors (still log the attempt)

---

### Jobs (Queue Workers)

#### 1. ProcessWebhookJob
**Location:** `app/Jobs/ProcessWebhookJob.php`

**Queue:** `webhooks` (high priority)

**Responsibilities:**
1. Update WebhookEvent status to "processing"
2. Parse payload based on webhook source type
3. Extract meaningful data (title, message, severity)
4. Create Alert record
5. Publish event to Redis Pub/Sub channel "alerts"
6. Update WebhookEvent status to "processed"
7. Dispatch SendNotificationJob if severity >= warning

**Error Handling:**
- Retry 3 times with exponential backoff
- Mark WebhookEvent as "failed" on final failure
- Log errors with full context

---

#### 2. SendNotificationJob
**Location:** `app/Jobs/SendNotificationJob.php`

**Queue:** `notifications` (lower priority)

**Responsibilities:**
1. Load Alert with relationships
2. Send via configured channels (Telegram/Email)
3. Create NotificationLog entry with result
4. Handle rate limiting (max 20 Telegram msgs/min)

**Error Handling:**
- Retry 2 times
- Log failure but don't block system
- Store error message in NotificationLog

---

### Services

#### 1. WebhookSignatureValidator
**Location:** `app/Services/WebhookSignatureValidator.php`

**Methods:**
- `validate(string $payload, string $signature, string $secret, string $algorithm = 'sha256'): bool`

**Supported Algorithms:**
- SHA256 (GitHub, Stripe)
- SHA512 (custom webhooks)
- MD5 (legacy systems)

**Implementation:**
```php
hash_hmac($algorithm, $payload, $secret) === $signature
```

---

#### 2. RedisPublisher
**Location:** `app/Services/RedisPublisher.php`

**Methods:**
- `publish(string $channel, array $data): bool`

**Responsibilities:**
- Connect to Redis
- Publish JSON-encoded data to channel
- Handle connection failures gracefully
- Log publish events

**Usage:**
```php
RedisPublisher::publish('alerts', [
    'id' => $alert->id,
    'title' => $alert->title,
    'severity' => $alert->severity,
    'created_at' => $alert->created_at
]);
```

---

### Notifications

#### 1. AlertNotification
**Location:** `app/Notifications/AlertNotification.php`

**Channels:**
- Telegram (via `notifiable->routeNotificationForTelegram()`)
- Email (via `notifiable->email`)

**Structure:**
```php
public function toTelegram($notifiable)
{
    return TelegramMessage::create()
        ->content("🚨 {$this->alert->title}\n\n{$this->alert->message}");
}

public function toMail($notifiable)
{
    return (new MailMessage)
        ->subject($this->alert->title)
        ->line($this->alert->message);
}
```

---

## 🚀 Node.js WebSocket Server

### Structure
**Location:** `/websocket-server/`

**Files:**
- `index.js` - Main server file
- `package.json` - Dependencies
- `.env` - Configuration

### Dependencies
```json
{
  "express": "^4.18.0",
  "socket.io": "^4.6.0",
  "redis": "^4.6.0",
  "dotenv": "^16.0.0"
}
```

### Core Implementation

**Redis Subscriber Flow:**
1. Connect to Redis
2. Subscribe to "alerts" channel
3. On message received → broadcast via Socket.IO
4. Handle reconnection automatically

**Socket.IO Server:**
1. Listen on port 3000
2. CORS enabled for Laravel domain
3. Emit "new-alert" event to all connected clients
4. Log connections/disconnections

**Sample Code Structure:**
```javascript
const express = require('express');
const { Server } = require('socket.io');
const { createClient } = require('redis');

// Setup Express + Socket.IO
// Setup Redis subscriber
// On Redis message → io.emit('new-alert', data)
```

---

## 🎨 Filament Admin Dashboard

### Installation
```bash
composer require filament/filament:"^3.0"
php artisan filament:install --panels
```

### Resources

#### 1. WebhookSourceResource
**Location:** `app/Filament/Resources/WebhookSourceResource.php`

**Features:**
- List view with name, slug, is_active status
- Create/Edit form with encrypted secret_key field
- Delete action with confirmation
- Toggle is_active inline

**Fields:**
- TextInput: name (required)
- TextInput: slug (required, unique, alphanumeric)
- TextInput: secret_key (password, required)
- Toggle: is_active

---

#### 2. WebhookEventResource
**Location:** `app/Filament/Resources/WebhookEventResource.php`

**Features:**
- List view with source, event_type, status, created_at
- Filter by source, status, date range
- View-only (no create/edit)
- JSON viewer modal for payload inspection
- Color-coded status badges

**Columns:**
- TextColumn: webhook_source.name
- TextColumn: event_type
- BadgeColumn: status (color-coded)
- TextColumn: created_at (date time ago)

**Filters:**
- SelectFilter: webhook_source_id
- SelectFilter: status
- DateRangeFilter: created_at

---

#### 3. AlertResource
**Location:** `app/Filament/Resources/AlertResource.php`

**Features:**
- List view with title, severity, is_read, created_at
- Filter by severity, is_read
- Bulk "Mark as Read" action
- View details with full message + metadata
- Color-coded severity badges

**Columns:**
- TextColumn: title
- BadgeColumn: severity (color: critical=red, error=orange, warning=yellow, info=blue)
- IconColumn: is_read (checkmark/x)
- TextColumn: created_at

**Actions:**
- Action: "Mark as Read" (single)
- BulkAction: "Mark Selected as Read"

---

### Widgets

#### 1. RecentAlertsWidget (Custom)
**Location:** `app/Filament/Widgets/RecentAlertsWidget.php`

**Type:** Blade component with custom JS

**Features:**
- Shows last 10 alerts in real-time
- Auto-updates via Socket.IO client
- Color-coded by severity
- Click to view full alert
- Sound notification for critical alerts (optional)

**Implementation:**
- Blade view with Socket.IO client script
- Connects to WebSocket server
- Listens to "new-alert" event
- Prepends new alert to list
- Plays sound if severity === 'critical'

---

#### 2. AlertStatsWidget (Stats Overview)
**Location:** `app/Filament/Widgets/AlertStatsWidget.php`

**Type:** StatsOverviewWidget

**Metrics:**
- Total webhooks today
- Critical alerts (last 24h)
- Average processing time
- Failed events (last 24h)

**Color Coding:**
- Green if all good
- Yellow if warnings exist
- Red if critical alerts or failures

---

#### 3. SystemHealthWidget (Custom)
**Location:** `app/Filament/Widgets/SystemHealthWidget.php`

**Type:** Widget with health checks

**Checks:**
- Redis connection (ping)
- Queue worker status (check last processed job time)
- WebSocket server status (HTTP health endpoint)
- Database connection

**Display:**
- Green dot + "Operational" if healthy
- Red dot + "Down" if unhealthy
- Yellow dot + "Degraded" if slow

---

## 📝 Implementation Steps

### Phase 1: Database & Models (Est: 30 min)

**Step 1:** Create migrations
```bash
php artisan make:migration create_webhook_sources_table
php artisan make:migration create_webhook_events_table
php artisan make:migration create_alerts_table
php artisan make:migration create_notification_logs_table
```

**Step 2:** Define schema in each migration file
- Add all fields as specified above
- Add indexes for performance
- Add foreign key constraints

**Step 3:** Create models
```bash
php artisan make:model WebhookSource
php artisan make:model WebhookEvent
php artisan make:model Alert
php artisan make:model NotificationLog
```

**Step 4:** Configure models
- Add `$fillable` arrays
- Add `$casts` for json/boolean/enum fields
- Define relationships
- Add encrypted casting for secret_key

**Step 5:** Run migrations
```bash
php artisan migrate
```

---

### Phase 2: Webhook Ingestion (Est: 45 min)

**Step 6:** Create WebhookSignatureValidator service
```bash
php artisan make:class Services/WebhookSignatureValidator
```
- Implement `validate()` method
- Support SHA256, SHA512, MD5

**Step 7:** Create WebhookController
```bash
php artisan make:controller Api/WebhookController
```
- Implement `handle()` method
- Use WebhookSignatureValidator
- Store event with status=pending
- Return 200 immediately

**Step 8:** Add API route
In `routes/api.php`:
```php
Route::post('/webhooks/{source}', [WebhookController::class, 'handle']);
```

**Step 9:** Test with cURL
```bash
curl -X POST http://localhost:8000/api/webhooks/github \
  -H "Content-Type: application/json" \
  -d '{"event": "push", "repo": "test"}'
```

---

### Phase 3: Queue Processing (Est: 30 min)

**Step 10:** Configure Redis queue
In `.env`:
```
QUEUE_CONNECTION=redis
```

**Step 11:** Create ProcessWebhookJob
```bash
php artisan make:job ProcessWebhookJob
```
- Accept WebhookEvent in constructor
- Parse payload
- Create Alert
- Publish to Redis

**Step 12:** Create RedisPublisher service
```bash
php artisan make:class Services/RedisPublisher
```
- Implement `publish()` method
- Use `Redis::publish()`

**Step 13:** Dispatch job from WebhookController
```php
ProcessWebhookJob::dispatch($webhookEvent);
```

**Step 14:** Test queue worker
```bash
php artisan queue:work
```

---

### Phase 4: Node.js WebSocket Server (Est: 30 min)

**Step 15:** Create directory and initialize
```bash
mkdir websocket-server
cd websocket-server
npm init -y
```

**Step 16:** Install dependencies
```bash
npm install express socket.io redis dotenv
```

**Step 17:** Create `index.js`
- Setup Express server
- Setup Socket.IO with CORS
- Connect to Redis as subscriber
- Subscribe to "alerts" channel
- Broadcast messages to Socket.IO clients

**Step 18:** Create `.env`
```
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
PORT=3000
```

**Step 19:** Test WebSocket server
```bash
node index.js
```

**Step 20:** Test Redis Pub/Sub
```bash
redis-cli PUBLISH alerts '{"test": "message"}'
```

---

### Phase 5: Notifications (Est: 30 min)

**Step 21:** Install notification packages
```bash
composer require laravel-notification-channels/telegram
```

**Step 22:** Configure Telegram
In `.env`:
```
TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_CHAT_ID=your_chat_id
```

**Step 23:** Create AlertNotification
```bash
php artisan make:notification AlertNotification
```
- Implement `toTelegram()` method
- Implement `toMail()` method

**Step 24:** Create SendNotificationJob
```bash
php artisan make:job SendNotificationJob
```
- Accept Alert in constructor
- Send notification
- Log result in NotificationLog

**Step 25:** Test notification
```php
$alert = Alert::first();
Notification::route('telegram', env('TELEGRAM_CHAT_ID'))
    ->notify(new AlertNotification($alert));
```

---

### Phase 6: Filament Dashboard (Est: 60 min)

**Step 26:** Install Filament
```bash
composer require filament/filament:"^3.0"
php artisan filament:install --panels
php artisan make:filament-user
```

**Step 27:** Create Resources
```bash
php artisan make:filament-resource WebhookSource --generate
php artisan make:filament-resource WebhookEvent --generate --view-only
php artisan make:filament-resource Alert --generate
```

**Step 28:** Customize resources
- Configure fields as specified
- Add filters
- Add actions (Mark as Read)
- Add color-coded badges

**Step 29:** Create Widgets
```bash
php artisan make:filament-widget RecentAlertsWidget
php artisan make:filament-widget AlertStatsWidget --stats
php artisan make:filament-widget SystemHealthWidget
```

**Step 30:** Implement RecentAlertsWidget
- Create Blade view
- Add Socket.IO client script
- Connect to WebSocket server
- Listen for "new-alert" events
- Update UI in real-time

**Step 31:** Register widgets in panel
In `app/Filament/Pages/Dashboard.php`:
```php
protected function getHeaderWidgets(): array
{
    return [
        AlertStatsWidget::class,
        SystemHealthWidget::class,
        RecentAlertsWidget::class,
    ];
}
```

---

### Phase 7: Testing & Polish (Est: 30 min)

**Step 32:** Create seeder with sample data
```bash
php artisan make:seeder WebhookSourceSeeder
```
- Seed GitHub, Stripe, custom sources

**Step 33:** Test end-to-end flow
1. Send webhook → Controller receives
2. Job queued → Worker processes
3. Alert created → Redis publishes
4. WebSocket broadcasts → Dashboard updates
5. Notification sent → Telegram/Email received

**Step 34:** Add Docker Compose for Redis
Create `docker-compose.yml`:
```yaml
services:
  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
```

**Step 35:** Document setup in README
- Installation steps
- Environment variables
- Running services (Laravel, Queue, WebSocket)
- Testing webhooks

---

## 🔑 Environment Variables

```env
# Application
APP_NAME=OpsCenter
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=opscenter
DB_USERNAME=root
DB_PASSWORD=

# Queue & Cache
QUEUE_CONNECTION=redis
CACHE_STORE=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Telegram
TELEGRAM_BOT_TOKEN=your_bot_token_here
TELEGRAM_CHAT_ID=your_chat_id_here

# Email
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@opscenter.test

# WebSocket Server
WEBSOCKET_URL=http://localhost:3000
WEBSOCKET_PORT=3000
```

---

## 🧪 Testing Flow

### 1. Send Test Webhook
```bash
curl -X POST http://localhost:8000/api/webhooks/github \
  -H "Content-Type: application/json" \
  -H "X-Hub-Signature-256: sha256=YOUR_HMAC" \
  -d '{
    "event": "push",
    "repository": "test-repo",
    "pusher": {"name": "john"},
    "commits": 5
  }'
```

### 2. Verify Database
```sql
SELECT * FROM webhook_events ORDER BY id DESC LIMIT 1;
SELECT * FROM alerts ORDER BY id DESC LIMIT 1;
```

### 3. Check Queue Processing
```bash
php artisan queue:work --verbose
```

### 4. Monitor Redis Pub/Sub
```bash
redis-cli SUBSCRIBE alerts
```

### 5. Check WebSocket Logs
```bash
cd websocket-server
node index.js
# Should see: "Published to all clients: {alert data}"
```

### 6. View in Dashboard
- Open browser: http://localhost:8000/admin
- See alert in RecentAlertsWidget
- Check AlertStatsWidget updates
- Verify SystemHealthWidget shows green

### 7. Verify Notification
- Check Telegram bot for message
- Check email inbox (Mailtrap)

---

## 📦 Package Requirements

### Laravel (composer.json)
```json
{
  "require": {
    "php": "^8.4",
    "laravel/framework": "^12.0",
    "filament/filament": "^3.0",
    "predis/predis": "^2.2",
    "laravel-notification-channels/telegram": "^5.0"
  }
}
```

### Node.js (websocket-server/package.json)
```json
{
  "dependencies": {
    "express": "^4.18.0",
    "socket.io": "^4.6.0",
    "redis": "^4.6.0",
    "dotenv": "^16.0.0"
  }
}
```

---

## ⏱️ Time Estimates

| Phase | Tasks | Estimated Time |
|-------|-------|----------------|
| 1. Database & Models | 5 steps | 30 min |
| 2. Webhook Ingestion | 4 steps | 45 min |
| 3. Queue Processing | 5 steps | 30 min |
| 4. Node.js WebSocket | 6 steps | 30 min |
| 5. Notifications | 5 steps | 30 min |
| 6. Filament Dashboard | 6 steps | 60 min |
| 7. Testing & Polish | 4 steps | 30 min |
| **Total** | **35 steps** | **~4 hours** |

---

## 🎯 Success Criteria

✅ Webhook endpoint responds in < 100ms
✅ Events processed asynchronously via queue
✅ Real-time dashboard updates without refresh
✅ Notifications sent for critical alerts
✅ Full audit trail of all webhooks
✅ Clean, maintainable code structure
✅ Demonstrable in interview setting

---

## 🚀 Running the Application

```bash
# Terminal 1: Laravel Server
php artisan serve

# Terminal 2: Queue Worker
php artisan queue:work --verbose

# Terminal 3: WebSocket Server
cd websocket-server && node index.js

# Terminal 4: Frontend (if needed)
npm run dev
```

---

## 📚 Additional Resources

- [Laravel Queues Documentation](https://laravel.com/docs/queues)
- [Redis Pub/Sub Guide](https://redis.io/docs/interact/pubsub/)
- [Socket.IO Documentation](https://socket.io/docs/)
- [Filament Documentation](https://filamentphp.com/docs)
- [Webhook Security Best Practices](https://webhooks.fyi/)
