# ForeverKids — Hostinger VPS Deployment Guide

**Domain:** `forverkids.dcryaons.app`
**Server IP:** `167.88.41.35`
**SSH Port:** `65002`
**SSH User:** `u322703740`

---

## Table of Contents

1. [SSH Key Setup & Connection](#1-ssh-key-setup--connection)
2. [Server Environment Setup](#2-server-environment-setup)
3. [Project Upload & Installation](#3-project-upload--installation)
4. [Production Environment Configuration](#4-production-environment-configuration)
5. [Web Server Configuration](#5-web-server-configuration)
6. [SSL Certificate](#6-ssl-certificate)
7. [File Permissions](#7-file-permissions)
8. [Queue Workers & Cron Jobs](#8-queue-workers--cron-jobs)
9. [Deployment Script (Future Updates)](#9-deployment-script-future-updates)
10. [SCP Quick Reference](#10-scp-quick-reference)
11. [Database Backup & Restore](#11-database-backup--restore)
12. [Troubleshooting](#12-troubleshooting)

---

## 1. SSH Key Setup & Connection

### 1.1 Generate SSH Key Pair (Local Machine)

**Windows (Git Bash / PowerShell):**

```bash
ssh-keygen -t ed25519 -C "forverkids-hostinger"
```

When prompted:
- Save location: Press Enter for default (`~/.ssh/id_ed25519`) or specify a custom path like `~/.ssh/forverkids_hostinger`
- Passphrase: Enter a passphrase (recommended) or press Enter for none

This creates two files:
- **Private key:** `~/.ssh/id_ed25519` (NEVER share this)
- **Public key:** `~/.ssh/id_ed25519.pub` (this goes on the server)

**Linux / macOS:**

```bash
ssh-keygen -t ed25519 -C "forverkids-hostinger"
```

### 1.2 Copy Public Key to Server

**Option A — Using ssh-copy-id (Linux/macOS/Git Bash):**

```bash
ssh-copy-id -p 65002 u322703740@167.88.41.35
```

Enter your password when prompted. After this, password-less login is enabled.

**Option B — Manual copy (Windows PowerShell):**

```powershell
# Display your public key
cat ~/.ssh/id_ed25519.pub

# SSH into server with password
ssh -p 65002 u322703740@167.88.41.35

# On the server, add the key
mkdir -p ~/.ssh
chmod 700 ~/.ssh
echo "PASTE_YOUR_PUBLIC_KEY_HERE" >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
exit
```

### 1.3 SSH Config (Convenience)

Add this to `~/.ssh/config` so you can just type `ssh forverkids`:

```
Host forverkids
    HostName 167.88.41.35
    Port 65002
    User u322703740
    IdentityFile ~/.ssh/id_ed25519
```

Now you can connect with:

```bash
ssh forverkids
```

### 1.4 Test Connection

```bash
ssh -p 65002 u322703740@167.88.41.35
# OR if you added the SSH config:
ssh forverkids
```

You should be logged in without being asked for a password.

---

## 2. Server Environment Setup

SSH into the server first:

```bash
ssh -p 65002 u322703740@167.88.41.35
```

### 2.1 Check PHP Version & Extensions

```bash
php -v
# Should show PHP 8.2+

php -m
# Check for required extensions
```

**Required PHP extensions:**

```
bcmath, ctype, curl, dom, fileinfo, gd, intl,
mbstring, mysql (mysqli/pdo_mysql), openssl,
tokenizer, xml, zip
```

If using Hostinger VPS with root access, install missing extensions:

```bash
sudo apt update
sudo apt install php8.2-bcmath php8.2-ctype php8.2-curl php8.2-dom \
  php8.2-fileinfo php8.2-gd php8.2-intl php8.2-mbstring php8.2-mysql \
  php8.2-xml php8.2-zip php8.2-redis
```

> **Hostinger Shared Hosting:** If you're on shared hosting, use hPanel > Advanced > PHP Configuration to enable extensions. Most are enabled by default.

### 2.2 MySQL Database Setup

```bash
mysql -u root -p
```

```sql
CREATE DATABASE dcommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'forverkids'@'localhost' IDENTIFIED BY 'YOUR_STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON dcommerce.* TO 'forverkids'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

> **Hostinger hPanel:** Go to Databases > MySQL Databases to create the database and user through the GUI.

### 2.3 Install Composer

```bash
# Check if already installed
composer --version

# If not installed:
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2.4 Install Node.js 20 LTS

```bash
# Check if already installed
node -v

# If not installed:
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### 2.5 Install Meilisearch (Product Search)

```bash
curl -L https://install.meilisearch.com | sh
sudo mv ./meilisearch /usr/local/bin/

# Create a systemd service
sudo tee /etc/systemd/system/meilisearch.service > /dev/null <<EOF
[Unit]
Description=Meilisearch
After=network.target

[Service]
User=u322703740
ExecStart=/usr/local/bin/meilisearch --http-addr 127.0.0.1:7700 --master-key YOUR_MEILI_MASTER_KEY
Restart=always

[Install]
WantedBy=multi-user.target
EOF

sudo systemctl enable meilisearch
sudo systemctl start meilisearch
```

### 2.6 Install Supervisor (Queue Workers)

```bash
sudo apt install supervisor
sudo systemctl enable supervisor
```

---

## 3. Project Upload & Installation

### 3.1 Upload via SCP

From your **local machine** (Windows/Git Bash):

```bash
# Upload the entire project (first deployment)
scp -P 65002 -r d:/projects/forverkids_laravel u322703740@167.88.41.35:~/

# OR if you set up SSH config:
scp -r d:/projects/forverkids_laravel forverkids:~/
```

> **Exclude unnecessary files** to speed up transfer. Create a tarball first:
>
> ```bash
> cd d:/projects
> tar --exclude='node_modules' --exclude='.git' --exclude='vendor' \
>     -czf forverkids.tar.gz forverkids_laravel
> scp -P 65002 forverkids.tar.gz u322703740@167.88.41.35:~/
> ```
>
> Then on the server:
> ```bash
> cd ~
> tar -xzf forverkids.tar.gz
> rm forverkids.tar.gz
> ```

### 3.2 Alternative: Git Clone

If your repo is on GitHub/GitLab:

```bash
ssh -p 65002 u322703740@167.88.41.35
cd ~
git clone https://github.com/YOUR_USERNAME/forverkids_laravel.git
```

### 3.3 Install Dependencies

```bash
cd ~/forverkids_laravel

# PHP dependencies (production only)
composer install --no-dev --optimize-autoloader

# Node dependencies & build assets
npm install
npm run build
```

### 3.4 Laravel Setup

```bash
cd ~/forverkids_laravel

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate --force

# Create storage symlink
php artisan storage:link

# Import products into Meilisearch
php artisan scout:import "App\Models\Product"

# Cache everything for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## 4. Production Environment Configuration

Edit the `.env` file on the server:

```bash
nano ~/forverkids_laravel/.env
```

Set the following values:

```env
APP_NAME=ForeverKids
APP_ENV=production
APP_DEBUG=false
APP_URL=https://forverkids.dcryaons.app

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dcommerce
DB_USERNAME=forverkids
DB_PASSWORD=YOUR_STRONG_PASSWORD_HERE

# Cache & Queue (database driver works out of the box)
CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database

# Mail (configure with your SMTP provider)
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=info@forverkids.dcryaons.app
MAIL_PASSWORD=YOUR_EMAIL_PASSWORD
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=info@forverkids.dcryaons.app
MAIL_FROM_NAME="ForeverKids"

# Meilisearch
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=YOUR_MEILI_MASTER_KEY

# Sanctum (API)
SANCTUM_STATEFUL_DOMAINS=forverkids.dcryaons.app
SESSION_DOMAIN=.dcryaons.app
```

After editing, rebuild the config cache:

```bash
php artisan config:cache
```

---

## 5. Web Server Configuration

### Hostinger VPS with LiteSpeed/Apache

Hostinger VPS typically uses LiteSpeed. The project already has `public/.htaccess` configured for Laravel.

**Set the document root** to point to the `public/` directory:

```
/home/u322703740/forverkids_laravel/public
```

**Via Hostinger hPanel:**
1. Go to **Websites** > your domain
2. Set **Document Root** to `/home/u322703740/forverkids_laravel/public`

**Via Apache virtual host** (if you have root access):

```bash
sudo nano /etc/apache2/sites-available/forverkids.conf
```

```apache
<VirtualHost *:80>
    ServerName forverkids.dcryaons.app
    DocumentRoot /home/u322703740/forverkids_laravel/public

    <Directory /home/u322703740/forverkids_laravel/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/forverkids-error.log
    CustomLog ${APACHE_LOG_DIR}/forverkids-access.log combined
</VirtualHost>
```

```bash
sudo a2ensite forverkids.conf
sudo a2enmod rewrite
sudo systemctl reload apache2
```

### PHP Version

Ensure PHP 8.2 is selected:

**Hostinger hPanel:** Go to Advanced > PHP Configuration > PHP Version > Select 8.2

---

## 6. SSL Certificate

### Via Hostinger hPanel (Recommended)

1. Go to **Security** > **SSL**
2. Select **forverkids.dcryaons.app**
3. Click **Install** for the free Let's Encrypt certificate
4. Enable **Force HTTPS**

### Via Certbot (VPS with root access)

```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d forverkids.dcryaons.app

# Auto-renewal (already set up by certbot, verify with):
sudo certbot renew --dry-run
```

> The `SecurityHeaders` middleware in the app already adds HSTS, X-Frame-Options, CSP, and other security headers automatically in production.

---

## 7. File Permissions

```bash
cd ~/forverkids_laravel

# Set ownership
chown -R u322703740:u322703740 .

# Storage and cache must be writable
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Protect .env file
chmod 600 .env

# Ensure public files are readable
chmod -R 755 public
```

---

## 8. Queue Workers & Cron Jobs

### 8.1 Supervisor Configuration (Queue Worker)

```bash
sudo nano /etc/supervisor/conf.d/forverkids-worker.conf
```

```ini
[program:forverkids-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/u322703740/forverkids_laravel/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=u322703740
numprocs=2
redirect_stderr=true
stdout_logfile=/home/u322703740/forverkids_laravel/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start forverkids-worker:*
```

Verify workers are running:

```bash
sudo supervisorctl status
```

### 8.2 Cron Job (Laravel Scheduler)

```bash
crontab -e
```

Add this line:

```
* * * * * cd /home/u322703740/forverkids_laravel && php artisan schedule:run >> /dev/null 2>&1
```

---

## 9. Deployment Script (Future Updates)

Create a reusable deployment script on the server:

```bash
nano ~/forverkids_laravel/deploy.sh
```

```bash
#!/bin/bash
set -e

PROJECT_DIR="/home/u322703740/forverkids_laravel"
cd "$PROJECT_DIR"

echo "==> Pulling latest code..."
git pull origin main

echo "==> Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Installing Node dependencies & building assets..."
npm install
npm run build

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

echo "==> Rebuilding caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> Restarting queue workers..."
php artisan queue:restart

echo "==> Warming application cache..."
php artisan cache:warm --all

echo ""
echo "========================================="
echo "  Deployment complete!"
echo "========================================="
```

```bash
chmod +x ~/forverkids_laravel/deploy.sh
```

**To deploy future updates:**

```bash
ssh -p 65002 u322703740@167.88.41.35
cd ~/forverkids_laravel
./deploy.sh
```

**Or run remotely in one command:**

```bash
ssh -p 65002 u322703740@167.88.41.35 'cd ~/forverkids_laravel && ./deploy.sh'
```

---

## 10. SCP Quick Reference

All commands run from your **local machine**.

### Upload Files

```bash
# Upload a single file
scp -P 65002 ./file.txt u322703740@167.88.41.35:~/forverkids_laravel/

# Upload a directory
scp -P 65002 -r ./public/images u322703740@167.88.41.35:~/forverkids_laravel/public/

# Upload .env file
scp -P 65002 ./.env.production u322703740@167.88.41.35:~/forverkids_laravel/.env

# Upload a tarball (faster for large transfers)
tar -czf deploy.tar.gz --exclude='node_modules' --exclude='.git' --exclude='vendor' -C d:/projects forverkids_laravel
scp -P 65002 deploy.tar.gz u322703740@167.88.41.35:~/
```

### Download Files

```bash
# Download a file from server
scp -P 65002 u322703740@167.88.41.35:~/forverkids_laravel/storage/logs/laravel.log ./

# Download database backup
scp -P 65002 u322703740@167.88.41.35:~/backups/dcommerce_backup.sql ./

# Download entire storage directory
scp -P 65002 -r u322703740@167.88.41.35:~/forverkids_laravel/storage/app/public ./storage-backup/
```

### With SSH Config (shorter)

If you set up `~/.ssh/config` with host `forverkids`:

```bash
scp ./file.txt forverkids:~/forverkids_laravel/
scp forverkids:~/backups/backup.sql ./
```

---

## 11. Database Backup & Restore

### Create Backup (on server)

```bash
# Manual backup
mysqldump -u forverkids -p dcommerce > ~/backups/dcommerce_$(date +%Y%m%d_%H%M%S).sql

# Compressed backup
mysqldump -u forverkids -p dcommerce | gzip > ~/backups/dcommerce_$(date +%Y%m%d_%H%M%S).sql.gz
```

### Automated Daily Backup

```bash
mkdir -p ~/backups

# Add to crontab
crontab -e
```

```
# Daily database backup at 2 AM
0 2 * * * mysqldump -u forverkids -pYOUR_PASSWORD dcommerce | gzip > /home/u322703740/backups/dcommerce_$(date +\%Y\%m\%d).sql.gz

# Keep only last 30 days of backups
0 3 * * * find /home/u322703740/backups -name "*.sql.gz" -mtime +30 -delete
```

### Restore Backup

```bash
# From .sql file
mysql -u forverkids -p dcommerce < ~/backups/dcommerce_20260225.sql

# From .gz file
gunzip < ~/backups/dcommerce_20260225.sql.gz | mysql -u forverkids -p dcommerce
```

### Download Backup to Local Machine

```bash
scp -P 65002 u322703740@167.88.41.35:~/backups/dcommerce_20260225.sql.gz ./
```

---

## 12. Troubleshooting

### Check Application Logs

```bash
# Laravel application log
tail -f ~/forverkids_laravel/storage/logs/laravel.log

# Queue worker log
tail -f ~/forverkids_laravel/storage/logs/worker.log

# Apache/LiteSpeed error log
tail -f /var/log/apache2/forverkids-error.log
```

### Common Issues

| Issue | Solution |
|-------|----------|
| 500 Internal Server Error | Check `storage/logs/laravel.log`. Usually permissions or missing `.env` |
| 403 Forbidden | Document root not pointing to `public/` directory |
| Class not found | Run `composer install` and `composer dump-autoload` |
| Styles/JS not loading | Run `npm run build` and check `public/build/manifest.json` exists |
| Queue jobs not processing | Check supervisor: `sudo supervisorctl status` |
| Session/cache issues | Run `php artisan config:cache` after `.env` changes |
| Search not working | Check Meilisearch: `curl http://127.0.0.1:7700/health` |
| Migration errors | Check DB credentials in `.env`, then `php artisan migrate:status` |

### Useful Debug Commands

```bash
cd ~/forverkids_laravel

# Check application status
php artisan about

# List all routes
php artisan route:list

# Check migration status
php artisan migrate:status

# Check queue status
php artisan queue:monitor database

# Clear all caches
php artisan optimize:clear

# Rebuild all caches
php artisan optimize

# Check PHP configuration
php -i | grep -i "loaded configuration"

# Test database connection
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Connected!';"

# Test mail configuration
php artisan tinker --execute="Mail::raw('Test', fn(\$m) => \$m->to('test@example.com')->subject('Test'));"
```

### Restart Services

```bash
# Restart queue workers
php artisan queue:restart

# Restart supervisor
sudo supervisorctl restart forverkids-worker:*

# Restart web server
sudo systemctl restart apache2
# OR for LiteSpeed:
sudo systemctl restart lsws

# Restart Meilisearch
sudo systemctl restart meilisearch
```

---

## First Deployment Checklist

Run through this after the initial setup:

- [ ] SSH key authentication working (no password prompt)
- [ ] PHP 8.2+ installed with all required extensions
- [ ] MySQL database `dcommerce` created with user
- [ ] Composer and Node.js installed
- [ ] Project files uploaded to `~/forverkids_laravel`
- [ ] `composer install --no-dev` completed
- [ ] `npm install && npm run build` completed
- [ ] `.env` configured with production values
- [ ] `php artisan key:generate` run
- [ ] `php artisan migrate --force` run
- [ ] `php artisan storage:link` run
- [ ] Document root set to `public/` directory
- [ ] SSL certificate installed and HTTPS forced
- [ ] File permissions set (775 storage, 600 .env)
- [ ] Supervisor queue workers running
- [ ] Cron job added for scheduler
- [ ] Meilisearch running and products indexed
- [ ] Site loads at `https://forverkids.dcryaons.app`
- [ ] Admin panel accessible at `/admin`
- [ ] POS system accessible at `/pos`
- [ ] API responding at `/api/v1/home`
