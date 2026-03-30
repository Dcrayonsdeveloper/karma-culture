# ForeverKids Production Deployment Guide

This document covers everything needed to deploy the ForeverKids Laravel e-commerce application to a production environment.

---

## Table of Contents

1. [Server Requirements](#1-server-requirements)
2. [Environment Setup](#2-environment-setup)
3. [Installation Steps](#3-installation-steps)
4. [SSL Configuration](#4-ssl-configuration)
5. [Nginx Configuration](#5-nginx-configuration)
6. [Queue Worker Setup](#6-queue-worker-setup)
7. [Scheduled Tasks](#7-scheduled-tasks)
8. [File Permissions](#8-file-permissions)
9. [Backup Strategy](#9-backup-strategy)
10. [Scaling Considerations](#10-scaling-considerations)

---

## 1. Server Requirements

### Minimum Hardware

| Resource | Minimum    | Recommended  |
|----------|------------|--------------|
| CPU      | 2 vCPUs    | 4 vCPUs      |
| RAM      | 4 GB       | 8 GB         |
| Disk     | 40 GB SSD  | 100 GB SSD   |

### Software Requirements

| Software      | Minimum Version | Notes                                     |
|---------------|-----------------|-------------------------------------------|
| PHP           | 8.2+            | Required by Laravel 12 and composer.json   |
| MySQL         | 8.0+            | Primary database                           |
| Redis         | 7.0+            | Cache, sessions, queues                    |
| Nginx         | 1.24+           | Recommended web server (Apache also works) |
| Node.js       | 20 LTS          | Asset compilation only (build step)        |
| Composer      | 2.7+            | PHP dependency management                  |
| Meilisearch   | 1.6+            | Full-text product search (Laravel Scout)   |
| Supervisor    | 4.0+            | Queue worker process management            |

### Required PHP Extensions

```
php8.2-bcmath
php8.2-ctype
php8.2-curl
php8.2-dom
php8.2-fileinfo
php8.2-gd
php8.2-intl
php8.2-mbstring
php8.2-mysql
php8.2-opcache
php8.2-redis
php8.2-tokenizer
php8.2-xml
php8.2-zip
```

Install on Ubuntu/Debian:

```bash
sudo apt update
sudo apt install php8.2 php8.2-fpm php8.2-bcmath php8.2-ctype php8.2-curl \
  php8.2-dom php8.2-fileinfo php8.2-gd php8.2-intl php8.2-mbstring \
  php8.2-mysql php8.2-opcache php8.2-redis php8.2-tokenizer php8.2-xml php8.2-zip
```

---

## 2. Environment Setup

Copy `.env.example` to `.env` and configure the following sections for production.

### Application

```dotenv
APP_NAME=ForeverKids
APP_ENV=production
APP_KEY=base64:GENERATE_WITH_ARTISAN_KEY_GENERATE
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=https://foreverkids.com
```

### Database

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=foreverkids_production
DB_USERNAME=foreverkids_user
DB_PASSWORD=<strong-random-password>
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

### Cache

```dotenv
CACHE_STORE=redis
CACHE_PREFIX=foreverkids_cache_
```

### Session

```dotenv
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=.foreverkids.com
```

### Queue

```dotenv
QUEUE_CONNECTION=redis
```

### Redis

```dotenv
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=<redis-password>
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_QUEUE_DB=2
```

### Mail

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@mg.foreverkids.com
MAIL_PASSWORD=<mailgun-api-key>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@foreverkids.com
MAIL_FROM_NAME="ForeverKids"
```

### Storage (S3-compatible)

```dotenv
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=<access-key>
AWS_SECRET_ACCESS_KEY=<secret-key>
AWS_DEFAULT_REGION=ap-south-1
AWS_BUCKET=foreverkids-production
AWS_URL=https://cdn.foreverkids.com
```

### Meilisearch

```dotenv
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=<meilisearch-master-key>
```

### Sanctum (API tokens)

```dotenv
SANCTUM_STATEFUL_DOMAINS=foreverkids.com,www.foreverkids.com
```

---

## 3. Installation Steps

### 3.1 Clone the Repository

```bash
cd /var/www
git clone git@github.com:your-org/foreverkids-laravel.git foreverkids
cd foreverkids
git checkout main
```

### 3.2 Install PHP Dependencies

```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

### 3.3 Configure Environment

```bash
cp .env.example .env
# Edit .env with production values (see Section 2)
php artisan key:generate
```

### 3.4 Install and Build Frontend Assets

```bash
npm ci --production=false
npm run build
```

After the build completes, the `node_modules` directory can be removed to save disk space:

```bash
rm -rf node_modules
```

### 3.5 Run Database Migrations

```bash
php artisan migrate --force
```

### 3.6 Seed Production Data (first deploy only)

```bash
php artisan db:seed --class=ProductionSeeder --force
```

### 3.7 Build Meilisearch Index

```bash
php artisan scout:import "App\Models\Product"
```

### 3.8 Cache Configuration, Routes, and Views

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan icons:cache    # if using Blade icons
```

### 3.9 Create Storage Symlink

```bash
php artisan storage:link
```

### 3.10 Verify Installation

```bash
php artisan about
php artisan route:list --compact
```

### Deployment Script (all-in-one)

Create `deploy.sh` in the project root:

```bash
#!/bin/bash
set -e

echo "Pulling latest code..."
git pull origin main

echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "Building assets..."
npm ci --production=false
npm run build
rm -rf node_modules

echo "Running migrations..."
php artisan migrate --force

echo "Clearing and rebuilding caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "Restarting queue workers..."
php artisan queue:restart

echo "Refreshing search index..."
php artisan scout:import "App\Models\Product"

echo "Deployment complete!"
```

---

## 4. SSL Configuration

### Install Certbot (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx
```

### Obtain Certificate

```bash
sudo certbot --nginx -d foreverkids.com -d www.foreverkids.com
```

### Auto-Renewal

Certbot installs a systemd timer automatically. Verify it is active:

```bash
sudo systemctl status certbot.timer
```

To test renewal:

```bash
sudo certbot renew --dry-run
```

### Manual Renewal Cron (fallback)

```cron
0 3 * * * /usr/bin/certbot renew --quiet --post-hook "systemctl reload nginx"
```

---

## 5. Nginx Configuration

Create `/etc/nginx/sites-available/foreverkids`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name foreverkids.com www.foreverkids.com;
    return 301 https://foreverkids.com$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name www.foreverkids.com;

    ssl_certificate /etc/letsencrypt/live/foreverkids.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/foreverkids.com/privkey.pem;

    return 301 https://foreverkids.com$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name foreverkids.com;

    root /var/www/foreverkids/public;
    index index.php;

    # SSL
    ssl_certificate /etc/letsencrypt/live/foreverkids.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/foreverkids.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 1d;
    ssl_session_tickets off;

    # HSTS
    add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Gzip
    gzip on;
    gzip_comp_level 5;
    gzip_min_length 256;
    gzip_types
        application/json
        application/javascript
        application/xml
        text/css
        text/plain
        text/javascript
        image/svg+xml;

    # Max upload size (product images, etc.)
    client_max_body_size 20M;

    # Static assets caching
    location ~* \.(css|js|ico|gif|jpeg|jpg|png|webp|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Laravel routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 60;
    }

    # Deny access to hidden files (.env, .git, etc.)
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Deny access to sensitive files
    location ~* (composer\.json|composer\.lock|package\.json|webpack\.mix\.js|\.env) {
        deny all;
    }

    access_log /var/log/nginx/foreverkids-access.log;
    error_log /var/log/nginx/foreverkids-error.log;
}
```

Enable and test:

```bash
sudo ln -s /etc/nginx/sites-available/foreverkids /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 6. Queue Worker Setup

ForeverKids uses queues for order notifications, email sending, search indexing, and other background tasks.

### Supervisor Configuration

Create `/etc/supervisor/conf.d/foreverkids-worker.conf`:

```ini
[program:foreverkids-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/foreverkids/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --memory=128
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/foreverkids-worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=3600
```

### Start Supervisor

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start foreverkids-worker:*
```

### Check Status

```bash
sudo supervisorctl status foreverkids-worker:*
```

### Restart Workers After Deploy

After every deployment, restart the queue workers so they pick up new code:

```bash
php artisan queue:restart
```

This sends a graceful restart signal. Workers finish their current job before restarting.

---

## 7. Scheduled Tasks

Laravel's task scheduler must be triggered by a single cron entry.

### Crontab Entry

```bash
sudo crontab -u www-data -e
```

Add:

```cron
* * * * * cd /var/www/foreverkids && php artisan schedule:run >> /dev/null 2>&1
```

### Verifying the Scheduler

```bash
php artisan schedule:list
```

---

## 8. File Permissions

### Set Ownership

```bash
sudo chown -R www-data:www-data /var/www/foreverkids
```

### Set Directory and File Permissions

```bash
# Directories: 755
sudo find /var/www/foreverkids -type d -exec chmod 755 {} \;

# Files: 644
sudo find /var/www/foreverkids -type f -exec chmod 644 {} \;
```

### Writable Directories

Laravel needs write access to the following:

```bash
sudo chmod -R 775 /var/www/foreverkids/storage
sudo chmod -R 775 /var/www/foreverkids/bootstrap/cache
```

### Protect Sensitive Files

```bash
chmod 600 /var/www/foreverkids/.env
```

---

## 9. Backup Strategy

### 9.1 Database Backups

#### Daily Automated Backup via Cron

Create `/usr/local/bin/foreverkids-db-backup.sh`:

```bash
#!/bin/bash
set -e

BACKUP_DIR="/var/backups/foreverkids/database"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
DB_NAME="foreverkids_production"
DB_USER="foreverkids_user"
DB_PASS="<db-password>"
RETENTION_DAYS=30

mkdir -p "$BACKUP_DIR"

# Dump database
mysqldump --user="$DB_USER" --password="$DB_PASS" \
  --single-transaction --routines --triggers \
  "$DB_NAME" | gzip > "$BACKUP_DIR/${DB_NAME}_${TIMESTAMP}.sql.gz"

# Remove backups older than retention period
find "$BACKUP_DIR" -name "*.sql.gz" -mtime +$RETENTION_DAYS -delete

echo "Database backup complete: ${DB_NAME}_${TIMESTAMP}.sql.gz"
```

```bash
chmod +x /usr/local/bin/foreverkids-db-backup.sh
```

Add to crontab:

```cron
# Daily database backup at 2:00 AM
0 2 * * * /usr/local/bin/foreverkids-db-backup.sh >> /var/log/foreverkids-backup.log 2>&1
```

### 9.2 Storage Backups

Create `/usr/local/bin/foreverkids-storage-backup.sh`:

```bash
#!/bin/bash
set -e

BACKUP_DIR="/var/backups/foreverkids/storage"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
STORAGE_PATH="/var/www/foreverkids/storage/app"
RETENTION_DAYS=14

mkdir -p "$BACKUP_DIR"

tar -czf "$BACKUP_DIR/storage_${TIMESTAMP}.tar.gz" -C "$STORAGE_PATH" .

find "$BACKUP_DIR" -name "storage_*.tar.gz" -mtime +$RETENTION_DAYS -delete

echo "Storage backup complete: storage_${TIMESTAMP}.tar.gz"
```

Add to crontab:

```cron
# Weekly storage backup on Sunday at 3:00 AM
0 3 * * 0 /usr/local/bin/foreverkids-storage-backup.sh >> /var/log/foreverkids-backup.log 2>&1
```

### 9.3 Offsite Backup

Sync local backups to an S3-compatible bucket:

```bash
# Install AWS CLI
sudo apt install awscli

# Sync backups daily
aws s3 sync /var/backups/foreverkids/ s3://foreverkids-backups/ --delete
```

### 9.4 Backup Restoration

Database restore:

```bash
gunzip < /var/backups/foreverkids/database/foreverkids_production_20260225_020000.sql.gz \
  | mysql -u foreverkids_user -p foreverkids_production
```

Storage restore:

```bash
tar -xzf /var/backups/foreverkids/storage/storage_20260225_030000.tar.gz \
  -C /var/www/foreverkids/storage/app
```

---

## 10. Scaling Considerations

### Redis for Cache, Sessions, and Queues

The application already uses Redis for all three concerns via separate database numbers (see Section 2). This avoids contention between cache eviction and session/queue data.

| Purpose  | Redis DB |
|----------|----------|
| Default  | 0        |
| Cache    | 1        |
| Queues   | 2        |

### PHP-FPM Tuning

Edit `/etc/php/8.2/fpm/pool.d/www.conf`:

```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500
```

### OPcache Tuning

Edit `/etc/php/8.2/fpm/conf.d/10-opcache.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.interned_strings_buffer=16
```

Note: With `validate_timestamps=0`, you must run `php artisan config:cache` (or restart PHP-FPM) after every deployment so new code is picked up.

### MySQL Tuning

Key parameters for `/etc/mysql/mysql.conf.d/mysqld.cnf`:

```ini
innodb_buffer_pool_size = 2G        # 50-70% of available RAM
innodb_log_file_size = 512M
innodb_flush_log_at_trx_commit = 2  # Slight durability trade-off for performance
max_connections = 200
query_cache_type = 0                # Disabled in MySQL 8.0+
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 1
```

### Horizontal Scaling

When a single server is no longer sufficient:

1. **Load Balancer**: Place an Nginx or cloud load balancer (AWS ALB, DigitalOcean LB) in front of multiple app servers.
2. **Shared Sessions**: Redis is already configured for sessions, so all app servers share session state.
3. **Shared Storage**: Move file uploads to S3 (already configured in .env) so all app servers access the same files.
4. **Dedicated Database Server**: Move MySQL to a managed service (AWS RDS, PlanetScale) or a dedicated server.
5. **Dedicated Redis Server**: Move Redis to a managed service (AWS ElastiCache, Redis Cloud) for higher throughput and failover.
6. **Dedicated Queue Server**: Run queue workers on separate servers to isolate background processing from web requests.
7. **Meilisearch**: Run Meilisearch on its own server or use Meilisearch Cloud for production-grade search.
8. **CDN**: Serve static assets and product images through a CDN (CloudFront, Cloudflare) to reduce server load and improve global latency.

### Zero-Downtime Deployment

For zero-downtime deploys, consider using:

- **Envoyer** (by Laravel) -- managed zero-downtime deployments
- **Deployer** (deployer.org) -- open-source PHP deployment tool
- Symlink-based releases with shared `storage/` and `.env`

A typical symlink structure:

```
/var/www/foreverkids/
  current -> /var/www/foreverkids/releases/20260225120000
  releases/
    20260225120000/
    20260224120000/
  shared/
    storage/
    .env
```
