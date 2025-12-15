# OpsCenter

![PHP Version](https://img.shields.io/badge/PHP-8.4+-blue.svg)
![Laravel Version](https://img.shields.io/badge/Laravel-12.0+-red.svg)
![License](https://img.shields.io/badge/License-MIT-green.svg)

Event-driven webhook processing platform built with Laravel 12.

## Features

- **Webhook Ingestion** - Receive webhooks from GitHub, GitLab, Stripe, and custom sources
- **Signature Validation** - HMAC SHA256/SHA512 signature verification for security
- **Multi-Source Support** - Configure multiple webhook providers with secret keys
- **Event Storage** - Store complete webhook payloads with headers and metadata
- **API Endpoints** - RESTful API for webhook handling

## Quick Start

```bash
# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run database migrations
php artisan migrate

# Start development server
php artisan serve
```

## API Usage

### Create Webhook Source
```php
use App\Models\WebhookSource;

WebhookSource::create([
    'name' => 'GitHub',
    'slug' => 'github',
    'secret_key' => 'your_webhook_secret',
    'is_active' => true,
]);
```

### Receive Webhooks
```bash
POST /api/webhooks/github
Content-Type: application/json
X-GitHub-Event: push
X-Hub-Signature-256: sha256=your_signature

{
    "ref": "refs/heads/main",
    "repository": {"name": "my-repo"},
    "commits": [...]
}
```

## Supported Providers

- **GitHub** - `X-Hub-Signature-256` header, `X-GitHub-Event` type
- **GitLab** - `X-Gitlab-Token` header, `X-Gitlab-Event` type
- **Stripe** - `Stripe-Signature` header, `type` field
- **Custom** - `X-Signature` header, `event` field

## Testing

```bash
# Run test suite
php artisan test

# Test webhook endpoints
php artisan http:handle tests/HttpRequests/handle.http
```

## Configuration

The application uses these database tables:
- `webhook_sources` - Provider configurations
- `webhook_events` - Incoming webhook logs
- `alerts` - Processed events (if implemented)
- `notification_logs` - Notification history (if implemented)

## Project Structure

```
app/
├── Http/Controllers/WebhookController.php
├── Services/WebhookService.php
├── Services/WebhookSignatureValidator.php
├── Models/WebhookSource.php
├── Models/WebhookEvent.php
└── Enums/WebhookEventStatus.php
```

## Requirements

- PHP 8.4+
- Laravel 12.0+
- MySQL 8.0+ or PostgreSQL 12+
- Redis (optional, for queues)

## License

MIT License