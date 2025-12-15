# OpsCenter — Event-Driven Webhook Processing Platform

A modern architecture demonstrating senior-level backend engineering skills.

---

## 1. Overview

OpsCenter is a backend-focused project built to demonstrate real-world architectural thinking using:
- Event-Driven Architecture
- Async Job Processing
- Webhook Ingestion
- Redis-backed Queues & Pub/Sub
- Real-Time Dashboards via WebSockets
- A polyglot backend using Laravel + Node.js

The system acts as a centralized event monitor, capable of receiving webhook events from external services (GitHub, payment gateways, CI tools), processing them asynchronously, and broadcasting them in real time to a dashboard.

## 2. Why This Architecture?

Modern backend systems require:
- High throughput webhook handling
- Zero-latency response times
- Asynchronous pipelines
- Decoupled real-time notification systems

This project demonstrates those concepts by splitting responsibilities:

| Layer | Technology | Responsibility |
|-------|------------|----------------|
| Ingress Layer | Laravel | Receives webhooks fast, validates security, dispatches background jobs. |
| Async Worker Layer | Laravel Queues (Redis) | Processes heavy logic without blocking incoming requests. |
| Real-Time Layer | Node.js + Redis Pub/Sub | Pushes instant UI updates via WebSockets. |
| UI Layer | FilamentPHP Dashboard | Displays live activity feed and alert widgets. |

Using Node.js as a separate service shows strong architectural maturity, and demonstrates understanding of distributed systems, Pub/Sub patterns, and WebSocket servers.

## 3. System Flow

```
┌──────────────────────────┐         1. POST /webhook
│ External Service (GitHub │ ──────────────────────────►
└──────────────────────────┘                             │
                                                          ▼
                                                 ┌──────────────────┐
                                                 │ Laravel API       │
                                                 │ - Validates       │
                                                 │ - Stores raw JSON │
                                                 │ - Dispatches Job  │
                                                 └─────────┬────────┘
                                                           │
                                                           ▼
                                                 ┌──────────────────┐
                                                 │ Redis Queue       │
                                                 └─────────┬────────┘
                                                           │ (async)
                                                           ▼
                                                 ┌──────────────────┐
                                                 │ Laravel Worker    │
                                                 │ - Process payload │
                                                 │ - Save to DB      │
                                                 │ - Publish event   │
                                                 └─────────┬────────┘
                                                           │
                                                           ▼  (Pub/Sub)
                                        ┌────────────────────────────────────┐
                                        │ Redis Channel: "alerts"            │
                                        └──────────────────┬─────────────────┘
                                                           │ (real-time)
                                                           ▼
                                                 ┌──────────────────────────┐
                                                 │ Node.js WebSocket Server │
                                                 │ - Listens to Redis       │
                                                 │ - Broadcasts to Clients  │
                                                 └──────────┬──────────────┘
                                                           │
                                                           ▼
                                        ┌──────────────────────────────────────┐
                                        │ Filament Dashboard (Browser Clients)│
                                        │ - Live Activity Feed                 │
                                        │ - Real-time charts & alerts          │
                                        └──────────────────────────────────────┘
```

## 4. Why Use a Separate Node.js Service?

Laravel has tools like Reverb or Pusher, but using Node.js as an independent WebSocket server provides interview advantages:

### 4.1 Demonstrates Real Architecture

You show that you understand:
- Process separation
- Cross-language communication
- Redis Pub/Sub as a transport layer
- Why WebSockets work better outside PHP's request-response lifecycle

### 4.2 Realistic Production Pattern

Large companies often run:
- Laravel (API) +
- Node.js (WebSockets) +
- Redis (Message Bus)

This mirrors real systems like Slack, GitHub, Stripe, and many microservices platforms.

### 4.3 Polyglot Skills

You demonstrate that you're not locked into PHP—you can integrate multiple runtimes.

---

## 5. Components Breakdown

### Laravel (API Gateway + Worker Layer)

**Responsibilities:**

- Receive webhook events
- Validate HMAC signatures
- Save raw event data
- Push to Redis Queue
- Worker processes JSON payload
- Publish structured alerts to Redis Pub/Sub
- Send Telegram/Email notifications

Laravel excels here because of its:
- Request-handling speed
- Built-in queue system
- First-class Redis integration
- Elegant Notifications API

---

### Node.js (Real-Time WebSocket Layer)

**Responsibilities:**

- Connect to Redis as a subscriber
- Listen on alerts channel
- Broadcast events to clients via WebSockets
- Maintain persistent connections

A lightweight example WebSocket server is only ~50 lines of code—perfect for interviews.

Node.js shines at:
- Long-lived connections
- High concurrency
- Event-driven patterns

---

### Redis (Backbone of the System)

Used for two roles:

1. **Queues**
   - Laravel → Redis → Laravel Worker
2. **Pub/Sub**
   - Laravel Worker → Redis Channel → Node.js

This decouples the system and increases scalability.

---

### FilamentPHP (Admin Dashboard)

**Responsibilities:**

- Show event logs
- Real-time alerts feed
- Charts and analytics
- Internal chat/wall for incidents (optional feature)

Filament provides:
- A fast TALL-stack admin panel
- Custom widgets for JS-based WebSocket listeners
- Beautiful UI with low development effort

---

## 6. Key Engineering Decisions

### 1. Queue Everything

Webhook endpoints must return 200 OK immediately.
Processing is moved to background jobs to avoid timeouts.

### 2. Decouple Real-Time From Backend

WebSocket logic is not tied to Laravel's lifecycle.
This increases reliability and horizontal scalability.

### 3. Use Pub/Sub to Broadcast Events

Redis Pub/Sub is simple, fast, and widely used in distributed architectures.

### 4. Technology chosen intentionally

- Laravel → structured API + workers
- Node.js → sockets + concurrency
- Redis → central message backbone

---

## 7. Project Goals (For Interview Use)

This project demonstrates:

### Webhook Architecture

Signature validation, security, retry logic, idempotency.

### Async Processing

Background jobs to ensure scalable webhook ingestion.

### Distributed System Thinking

Clear separation of concerns between HTTP, workers, and real-time sockets.

### Real-Time Data Streaming

Dashboard updates without refresh.

### Polyglot Experience

Integrating PHP + Node.js through Redis.

### DevOps Awareness

Docker, queue workers, environment variables, CI/CD pipeline (optional).

---

## 8. Tech Stack Summary

| Layer | Technology |
|-------|------------|
| API Gateway | Laravel 11 |
| Admin Panel | FilamentPHP |
| Database | MySQL |
| Queue & Pub/Sub | Redis |
| Real-Time Server | Node.js + Socket.IO |
| Notifications | Telegram / Email |
| Deployment | Docker / GitHub Actions |

