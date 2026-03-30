# ForeverKids Monitoring and Alerting Guide

This document describes how to monitor the ForeverKids application in production, track errors, measure performance, and configure alerts.

---

## Table of Contents

1. [Application Health Check](#1-application-health-check)
2. [Log Management](#2-log-management)
3. [Error Tracking](#3-error-tracking)
4. [Performance Monitoring](#4-performance-monitoring)
5. [Database Monitoring](#5-database-monitoring)
6. [Queue Monitoring](#6-queue-monitoring)
7. [Uptime Monitoring](#7-uptime-monitoring)
8. [Alerting Setup](#8-alerting-setup)
9. [Key Metrics to Track](#9-key-metrics-to-track)

---

## 1. Application Health Check

Laravel 12 provides a built-in health check endpoint.

### Default Endpoint

```
GET /up
```

This endpoint returns HTTP `200` when the application is healthy and `500` when something is wrong. It verifies that the application can boot, the database is reachable, and cached configuration is valid.

### Custom Health Check

To add custom checks (Redis, Meilisearch, disk space), extend the health check in `app/Providers/AppServiceProvider.php`:

```php
use Illuminate\Support\Facades\Health;
use Illuminate\Health\Checks\DatabaseCheck;
use Illuminate\Health\Checks\CacheCheck;

public function boot(): void
{
    Health::checks([
        DatabaseCheck::new(),
        CacheCheck::new(),
    ]);
}
```

### Usage

Point load balancers and uptime monitors to:

```
https://foreverkids.com/up
```

---

## 2. Log Management

### Laravel Log Configuration

ForeverKids uses Laravel's logging system. Production configuration in `config/logging.php`:

```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'stderr'],
        'ignore_exceptions' => false,
    ],

    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'warning'),
        'days' => 14,
    ],
],
```

Set in `.env`:

```dotenv
LOG_CHANNEL=stack
LOG_LEVEL=warning
```

### Log Rotation

The `daily` driver automatically rotates logs. Files are kept for 14 days by default. Verify with:

```bash
ls -la /var/www/foreverkids/storage/logs/
```

For system-level rotation (Nginx, PHP-FPM, Supervisor), use logrotate. Create `/etc/logrotate.d/foreverkids`:

```
/var/www/foreverkids/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0664 www-data www-data
}

/var/log/nginx/foreverkids-*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    sharedscripts
    postrotate
        [ -f /var/run/nginx.pid ] && kill -USR1 $(cat /var/run/nginx.pid)
    endscript
}
```

### Centralized Logging (optional)

For multi-server or advanced analysis, forward logs to a centralized service:

**Option A: Papertrail**

```dotenv
LOG_CHANNEL=stack
```

Add a syslog channel in `config/logging.php`:

```php
'papertrail' => [
    'driver' => 'monolog',
    'level' => env('LOG_LEVEL', 'warning'),
    'handler' => \Monolog\Handler\SyslogUdpHandler::class,
    'handler_with' => [
        'host' => env('PAPERTRAIL_URL'),
        'port' => env('PAPERTRAIL_PORT'),
    ],
],
```

**Option B: ELK Stack (Elasticsearch + Logstash + Kibana)**

Use Filebeat to ship Laravel logs to an ELK stack. Install Filebeat and configure `/etc/filebeat/filebeat.yml`:

```yaml
filebeat.inputs:
  - type: log
    paths:
      - /var/www/foreverkids/storage/logs/*.log
    multiline.pattern: '^\[\d{4}-\d{2}-\d{2}'
    multiline.negate: true
    multiline.match: after

output.elasticsearch:
  hosts: ["https://elk.internal:9200"]
  index: "foreverkids-logs-%{+yyyy.MM.dd}"
```

---

## 3. Error Tracking

### Sentry Integration

Sentry provides real-time error tracking with stack traces, breadcrumbs, and release tracking.

**Step 1: Install the SDK**

```bash
composer require sentry/sentry-laravel
```

**Step 2: Configure**

```dotenv
SENTRY_LARAVEL_DSN=https://examplePublicKey@o0.ingest.sentry.io/0
SENTRY_TRACES_SAMPLE_RATE=0.1
```

The package auto-discovers its service provider. Verify in `config/sentry.php`:

```php
return [
    'dsn' => env('SENTRY_LARAVEL_DSN'),
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),
    'send_default_pii' => false,
    'environment' => env('APP_ENV', 'production'),
    'release' => trim(exec('git log --pretty="%h" -n1 HEAD')),
];
```

**Step 3: Test**

```bash
php artisan sentry:test
```

### Bugsnag Integration (alternative)

```bash
composer require bugsnag/bugsnag-laravel
```

```dotenv
BUGSNAG_API_KEY=your-api-key
```

### What Gets Tracked

- Unhandled exceptions (500 errors)
- Queue job failures
- Validation errors (optionally)
- Slow database queries (via Sentry performance)
- User context (user ID, email -- with PII controls)

---

## 4. Performance Monitoring

### Local Development: Laravel Telescope

Telescope is already available as a dev dependency pattern. Install if not present:

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

Access at `https://foreverkids.test/telescope`. Telescope shows:

- All incoming requests and response times
- Database queries with bindings
- Queue jobs and their payloads
- Mail sent
- Cache operations
- Scheduled tasks
- Exceptions

**Important**: Do NOT enable Telescope in production. It adds overhead and stores sensitive data locally.

### Production: New Relic

**Step 1: Install the PHP agent**

```bash
sudo apt install newrelic-php5
sudo newrelic-install install
```

**Step 2: Configure** `/etc/php/8.2/fpm/conf.d/newrelic.ini`:

```ini
newrelic.appname = "ForeverKids Production"
newrelic.license = "<your-license-key>"
newrelic.distributed_tracing_enabled = true
newrelic.transaction_tracer.threshold = 500
```

**Step 3: Restart PHP-FPM**

```bash
sudo systemctl restart php8.2-fpm
```

### Production: Datadog (alternative)

**Step 1: Install the Datadog agent**

```bash
DD_API_KEY=<your-api-key> DD_SITE="datadoghq.com" bash -c \
  "$(curl -L https://install.datadoghq.com/scripts/install_script_agent7.sh)"
```

**Step 2: Enable PHP APM tracing**

```bash
sudo apt install datadog-php-tracer
```

**Step 3: Configure** `/etc/php/8.2/fpm/conf.d/98-ddtrace.ini`:

```ini
datadog.service = foreverkids
datadog.env = production
datadog.trace.enabled = 1
```

---

## 5. Database Monitoring

### Slow Query Log

Enable in `/etc/mysql/mysql.conf.d/mysqld.cnf`:

```ini
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 1
log_queries_not_using_indexes = 1
```

Restart MySQL:

```bash
sudo systemctl restart mysql
```

Analyze slow queries:

```bash
mysqldumpslow -s t -t 10 /var/log/mysql/slow.log
```

### Connection Pool Monitoring

Check active connections:

```sql
SHOW STATUS LIKE 'Threads_connected';
SHOW STATUS LIKE 'Max_used_connections';
SHOW VARIABLES LIKE 'max_connections';
```

Set up alerts when `Threads_connected` approaches `max_connections`:

```bash
# Simple monitoring script
CONNECTED=$(mysql -u monitor -p'<password>' -e "SHOW STATUS LIKE 'Threads_connected';" -s --skip-column-names | awk '{print $2}')
MAX=$(mysql -u monitor -p'<password>' -e "SHOW VARIABLES LIKE 'max_connections';" -s --skip-column-names | awk '{print $2}')
THRESHOLD=$(echo "$MAX * 0.8" | bc | cut -d. -f1)

if [ "$CONNECTED" -gt "$THRESHOLD" ]; then
    echo "WARNING: MySQL connections at $CONNECTED / $MAX" | mail -s "ForeverKids DB Alert" ops@foreverkids.com
fi
```

### InnoDB Status

```sql
SHOW ENGINE INNODB STATUS\G
```

### Query Performance Schema

Enable and query the performance schema for the heaviest queries:

```sql
SELECT
    DIGEST_TEXT,
    COUNT_STAR AS exec_count,
    ROUND(SUM_TIMER_WAIT / 1000000000000, 2) AS total_sec,
    ROUND(AVG_TIMER_WAIT / 1000000000000, 4) AS avg_sec
FROM performance_schema.events_statements_summary_by_digest
ORDER BY SUM_TIMER_WAIT DESC
LIMIT 10;
```

---

## 6. Queue Monitoring

### Failed Jobs Table

Laravel stores failed queue jobs in the `failed_jobs` table. Check for failures:

```bash
php artisan queue:failed
```

Retry a specific failed job:

```bash
php artisan queue:retry <job-id>
```

Retry all failed jobs:

```bash
php artisan queue:retry all
```

Flush all failed jobs:

```bash
php artisan queue:flush
```

### Monitor Queue Size

Check the number of pending jobs in Redis:

```bash
redis-cli -n 2 LLEN queues:default
```

### Laravel Horizon (recommended)

Horizon provides a dashboard and metrics for Redis-powered queues.

**Step 1: Install**

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan migrate
```

**Step 2: Configure** `config/horizon.php`:

```php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default', 'notifications', 'orders'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 10,
            'maxTime' => 3600,
            'maxJobs' => 1000,
            'memory' => 128,
            'tries' => 3,
            'nice' => 0,
        ],
    ],
],
```

**Step 3: Supervisor config** (replace the basic queue worker):

```ini
[program:foreverkids-horizon]
process_name=%(program_name)s
command=php /var/www/foreverkids/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/supervisor/foreverkids-horizon.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=3600
```

**Step 4: Access the dashboard**

```
https://foreverkids.com/horizon
```

Protect Horizon in production by restricting access in `app/Providers/HorizonServiceProvider.php`:

```php
protected function gate(): void
{
    Gate::define('viewHorizon', function ($user) {
        return in_array($user->email, [
            'admin@foreverkids.com',
        ]);
    });
}
```

### Queue Health Alerts

Horizon can notify you via Slack or email when:

- A job fails
- Queue wait time exceeds a threshold
- A queue is paused

Configure in `config/horizon.php`:

```php
'waits' => [
    'redis:default' => 60, // Alert if any job waits > 60 seconds
],
```

---

## 7. Uptime Monitoring

Use an external uptime monitoring service to detect outages from outside your infrastructure.

### Recommended Services

| Service        | Free Tier          | Features                                |
|----------------|--------------------|-----------------------------------------|
| UptimeRobot    | 50 monitors, 5 min | HTTP checks, keyword, ping, port        |
| Better Uptime  | 10 monitors        | Status pages, incident management       |
| Pingdom        | Paid only          | Advanced RUM, transaction checks        |
| Oh Dear        | Paid, Laravel-made | Certificate monitoring, broken links    |

### What to Monitor

| Endpoint                                 | Check Type     | Interval |
|------------------------------------------|----------------|----------|
| `https://foreverkids.com/up`             | HTTP 200       | 1 min    |
| `https://foreverkids.com`                | HTTP 200       | 1 min    |
| `https://foreverkids.com/api/v1/home`    | HTTP 200 + JSON| 5 min    |
| `https://foreverkids.com:443`            | SSL validity   | Daily    |

### Status Page

Set up a public status page at `status.foreverkids.com` using Better Uptime or Cachet to communicate outages to customers.

---

## 8. Alerting Setup

### Email Alerts for Application Errors

Configure a dedicated log channel that sends emails on critical errors. In `config/logging.php`:

```php
'emergency_mail' => [
    'driver' => 'monolog',
    'handler' => \Monolog\Handler\NativeMailerHandler::class,
    'handler_with' => [
        'to' => 'ops@foreverkids.com',
        'subject' => '[ForeverKids] CRITICAL ERROR',
        'from' => 'alerts@foreverkids.com',
    ],
    'level' => 'critical',
],
```

Add to the stack channel:

```php
'stack' => [
    'driver' => 'stack',
    'channels' => ['daily', 'emergency_mail'],
],
```

### Disk Space Alerts

Create `/usr/local/bin/foreverkids-disk-check.sh`:

```bash
#!/bin/bash
THRESHOLD=85
USAGE=$(df / | tail -1 | awk '{print $5}' | sed 's/%//')

if [ "$USAGE" -gt "$THRESHOLD" ]; then
    echo "ALERT: Disk usage at ${USAGE}% on $(hostname)" | \
      mail -s "[ForeverKids] Disk Space Warning" ops@foreverkids.com
fi
```

Add to crontab:

```cron
*/15 * * * * /usr/local/bin/foreverkids-disk-check.sh
```

### Memory Alerts

Create `/usr/local/bin/foreverkids-memory-check.sh`:

```bash
#!/bin/bash
THRESHOLD=90
USAGE=$(free | grep Mem | awk '{printf "%.0f", $3/$2 * 100}')

if [ "$USAGE" -gt "$THRESHOLD" ]; then
    echo "ALERT: Memory usage at ${USAGE}% on $(hostname)" | \
      mail -s "[ForeverKids] Memory Warning" ops@foreverkids.com
fi
```

Add to crontab:

```cron
*/15 * * * * /usr/local/bin/foreverkids-memory-check.sh
```

### Slack Notifications

For real-time Slack alerts, add a Slack logging channel in `config/logging.php`:

```php
'slack' => [
    'driver' => 'slack',
    'url' => env('LOG_SLACK_WEBHOOK_URL'),
    'username' => 'ForeverKids Alerts',
    'emoji' => ':warning:',
    'level' => 'error',
],
```

Set in `.env`:

```dotenv
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXX
```

### Queue Failure Notifications

In `app/Providers/AppServiceProvider.php`:

```php
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Mail;

public function boot(): void
{
    Queue::failing(function (JobFailed $event) {
        Mail::raw(
            "Queue job failed:\n\nConnection: {$event->connectionName}\nJob: {$event->job->resolveName()}\nException: {$event->exception->getMessage()}",
            fn($message) => $message
                ->to('ops@foreverkids.com')
                ->subject('[ForeverKids] Queue Job Failed')
        );
    });
}
```

---

## 9. Key Metrics to Track

### Application Metrics

| Metric                  | Target             | Alert Threshold        |
|-------------------------|--------------------|------------------------|
| Response time (p50)     | < 200ms            | > 500ms                |
| Response time (p95)     | < 800ms            | > 2000ms               |
| Response time (p99)     | < 2000ms           | > 5000ms               |
| Error rate (5xx)        | < 0.1%             | > 1%                   |
| Error rate (4xx)        | < 5%               | > 15%                  |
| Requests per second     | Baseline varies    | > 2x baseline (DDoS)   |

### Queue Metrics

| Metric                  | Target             | Alert Threshold        |
|-------------------------|--------------------|------------------------|
| Queue depth (default)   | < 100 jobs         | > 500 jobs             |
| Queue wait time         | < 10 seconds       | > 60 seconds           |
| Failed jobs per hour    | 0                  | > 5                    |
| Worker memory usage     | < 100MB            | > 128MB                |

### Database Metrics

| Metric                  | Target             | Alert Threshold        |
|-------------------------|--------------------|------------------------|
| Query time (p95)        | < 100ms            | > 500ms                |
| Slow queries per hour   | < 5                | > 20                   |
| Active connections      | < 50               | > 160 (80% of max)     |
| Replication lag         | < 1 second         | > 5 seconds            |

### Cache Metrics

| Metric                  | Target             | Alert Threshold        |
|-------------------------|--------------------|------------------------|
| Cache hit rate          | > 90%              | < 75%                  |
| Redis memory usage      | < 70% of max       | > 85%                  |
| Redis connected clients | < 100              | > 200                  |
| Evicted keys per hour   | 0                  | > 100                  |

### Infrastructure Metrics

| Metric                  | Target             | Alert Threshold        |
|-------------------------|--------------------|------------------------|
| CPU usage               | < 60%              | > 85% for 5 min        |
| Memory usage            | < 70%              | > 90%                  |
| Disk usage              | < 70%              | > 85%                  |
| Disk I/O wait           | < 10%              | > 30%                  |

### Monitoring Dashboard

Create a unified dashboard (Grafana, Datadog, or New Relic) that displays:

1. **Overview panel**: Request rate, error rate, response time (p50/p95/p99)
2. **Queue panel**: Queue depth by queue name, throughput, failed jobs
3. **Database panel**: Query rate, slow queries, connection count, replication lag
4. **Cache panel**: Hit rate, memory usage, evictions
5. **Infrastructure panel**: CPU, memory, disk, network
6. **Business panel**: Orders per hour, cart conversion rate, active users

### Health Check Script (all-in-one)

Create `/usr/local/bin/foreverkids-health.sh` for a quick manual check:

```bash
#!/bin/bash
echo "=== ForeverKids Health Check ==="
echo ""

# Application
echo "--- Application ---"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://foreverkids.com/up)
echo "Health endpoint: HTTP $HTTP_CODE"

# PHP-FPM
echo ""
echo "--- PHP-FPM ---"
systemctl is-active php8.2-fpm

# Nginx
echo ""
echo "--- Nginx ---"
systemctl is-active nginx

# MySQL
echo ""
echo "--- MySQL ---"
systemctl is-active mysql
CONNECTIONS=$(mysql -u monitor -p'<pass>' -e "SHOW STATUS LIKE 'Threads_connected';" -s --skip-column-names | awk '{print $2}')
echo "Active connections: $CONNECTIONS"

# Redis
echo ""
echo "--- Redis ---"
redis-cli ping

# Queue
echo ""
echo "--- Queue ---"
QUEUE_SIZE=$(redis-cli -n 2 LLEN queues:default)
echo "Default queue size: $QUEUE_SIZE"
FAILED=$(mysql -u monitor -p'<pass>' foreverkids_production -e "SELECT COUNT(*) FROM failed_jobs;" -s --skip-column-names)
echo "Failed jobs: $FAILED"

# Meilisearch
echo ""
echo "--- Meilisearch ---"
MEILI_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:7700/health)
echo "Meilisearch health: HTTP $MEILI_STATUS"

# Disk
echo ""
echo "--- Disk ---"
df -h / | tail -1

# Memory
echo ""
echo "--- Memory ---"
free -h | head -2

echo ""
echo "=== Check Complete ==="
```
