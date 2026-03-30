# ForeverKids Deployment Guide — foreverkids.com on Hostinger

## Server Details

| Item | Value |
|------|-------|
| **Host** | 167.88.41.35 |
| **Port** | 65002 |
| **User** | u322703740 |
| **SSH Command** | `ssh -p 65002 u322703740@167.88.41.35` |
| **SSH Alias** | `ssh forverkids` (if configured) |
| **Server Path** | `~/domains/foreverkids.com/forverkids_laravel/` |
| **Web Root** | `~/domains/foreverkids.com/public_html/` |

## Database Details

| Item | Value |
|------|-------|
| **DB Host** | localhost |
| **DB Name** | u322703740_foreverkids |
| **DB User** | u322703740_foreverkids |
| **DB Password** | `n6p+3HwU&` |

---

## 1. SSH Key Setup

### Generate SSH Key Pair (on your local machine)

```bash
ssh-keygen -t ed25519 -C "foreverkids-deploy" -f ~/.ssh/foreverkids_deploy
```

This creates:
- **Private key**: `~/.ssh/foreverkids_deploy`
- **Public key**: `~/.ssh/foreverkids_deploy.pub`

### Copy Public Key to Server

```bash
ssh-copy-id -p 65002 -i ~/.ssh/foreverkids_deploy.pub u322703740@167.88.41.35
```

Or manually:
```bash
# Copy public key content
cat ~/.ssh/foreverkids_deploy.pub

# SSH into server and add it
ssh -p 65002 u322703740@167.88.41.35
mkdir -p ~/.ssh && chmod 700 ~/.ssh
echo "PASTE_PUBLIC_KEY_HERE" >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
```

### Configure SSH Alias (~/.ssh/config)

Add to `~/.ssh/config`:

```
Host foreverkids
    HostName 167.88.41.35
    Port 65002
    User u322703740
    IdentityFile ~/.ssh/foreverkids_deploy
```

Now you can use:
```bash
ssh foreverkids
scp file.txt foreverkids:~/path/
```

---

## 2. First-Time Deployment

### Step 1: Upload Project Files

```bash
# From project root
scp -r . foreverkids:~/domains/foreverkids.com/forverkids_laravel/
```

Or use rsync (faster for updates):
```bash
rsync -avz --exclude='.git' --exclude='node_modules' --exclude='vendor' \
  -e "ssh -p 65002" \
  . u322703740@167.88.41.35:~/domains/foreverkids.com/forverkids_laravel/
```

### Step 2: Server Setup

```bash
ssh foreverkids

cd ~/domains/foreverkids.com/forverkids_laravel

# Install dependencies
composer install --optimize-autoloader --no-dev

# Copy and configure .env
cp .env.example .env
nano .env
```

### Step 3: Configure .env on Server

```env
APP_NAME="Forever Kids"
APP_ENV=production
APP_KEY=  # will be generated
APP_DEBUG=false
APP_URL=https://foreverkids.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u322703740_foreverkids
DB_USERNAME=u322703740_foreverkids
DB_PASSWORD=n6p+3HwU&

# Facebook Pixel
FB_PIXEL_ID=3311261889043941
```

### Step 4: Laravel Setup

```bash
# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate --seed

# Create storage link
php artisan storage:link

# Set permissions
chmod -R 775 storage bootstrap/cache

# Build caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Step 5: Symlink public_html to public/

On Hostinger, web root is `public_html`. Create symlink:

```bash
cd ~/domains/foreverkids.com

# Remove default public_html
rm -rf public_html

# Symlink to Laravel's public directory
ln -s ~/domains/foreverkids.com/forverkids_laravel/public public_html
```

---

## 3. Deploying Updates

### Deploy a Single File

```bash
scp path/to/file.blade.php foreverkids:~/domains/foreverkids.com/forverkids_laravel/path/to/file.blade.php
```

### Deploy Multiple View Files

```bash
# Deploy all views
scp -r resources/views/ foreverkids:~/domains/foreverkids.com/forverkids_laravel/resources/views/
```

### Deploy and Clear Cache

```bash
# One-liner: deploy file + clear view cache
scp resources/views/home.blade.php foreverkids:~/domains/foreverkids.com/forverkids_laravel/resources/views/home.blade.php && \
ssh foreverkids "cd ~/domains/foreverkids.com/forverkids_laravel && php artisan view:clear"
```

### Full Deployment Script

```bash
#!/bin/bash
# deploy.sh - Run from project root

SERVER="foreverkids"
REMOTE_PATH="~/domains/foreverkids.com/forverkids_laravel"

echo "Deploying ForeverKids..."

# Sync files (exclude dev files)
rsync -avz --delete \
  --exclude='.git' \
  --exclude='node_modules' \
  --exclude='vendor' \
  --exclude='.env' \
  --exclude='storage/app' \
  --exclude='storage/logs' \
  --exclude='storage/framework/sessions' \
  --exclude='storage/framework/cache' \
  -e "ssh -p 65002" \
  . u322703740@167.88.41.35:$REMOTE_PATH/

# Run remote commands
ssh $SERVER "cd $REMOTE_PATH && \
  composer install --optimize-autoloader --no-dev && \
  php artisan migrate --force && \
  php artisan config:cache && \
  php artisan route:cache && \
  php artisan view:cache && \
  php artisan optimize"

echo "Deployment complete!"
```

---

## 4. Cache Commands Reference

```bash
# Clear all caches
ssh foreverkids "cd ~/domains/foreverkids.com/forverkids_laravel && \
  php artisan config:clear && \
  php artisan route:clear && \
  php artisan view:clear && \
  php artisan cache:clear"

# Rebuild all caches (production)
ssh foreverkids "cd ~/domains/foreverkids.com/forverkids_laravel && \
  php artisan config:cache && \
  php artisan route:cache && \
  php artisan view:cache && \
  php artisan optimize"
```

---

## 5. Database Operations

### Export Database (from server)

```bash
ssh foreverkids "mysqldump -u u322703740_foreverkids -p'n6p+3HwU&' u322703740_foreverkids > ~/backup.sql"
scp foreverkids:~/backup.sql ./backup_$(date +%Y%m%d).sql
```

### Import Database (to server)

```bash
scp backup.sql foreverkids:~/backup.sql
ssh foreverkids "mysql -u u322703740_foreverkids -p'n6p+3HwU&' u322703740_foreverkids < ~/backup.sql"
```

### Run Migrations

```bash
ssh foreverkids "cd ~/domains/foreverkids.com/forverkids_laravel && php artisan migrate --force"
```

---

## 6. Cron Job Setup (Hostinger Panel)

Add in Hostinger hPanel > Cron Jobs:

```
* * * * * cd ~/domains/foreverkids.com/forverkids_laravel && php artisan schedule:run >> /dev/null 2>&1
```

This runs the Laravel scheduler every minute for:
- Drip review generator (daily at 2:00 AM)
- Any other scheduled tasks

---

## 7. Domain & SSL

### DNS Setup (foreverkids.com)

Point your domain to Hostinger:
- **A Record**: `@` → `167.88.41.35`
- **CNAME**: `www` → `foreverkids.com`

Or use Hostinger nameservers:
- `ns1.dns-parking.com`
- `ns2.dns-parking.com`

### SSL Certificate

Enable SSL in Hostinger hPanel > SSL. Hostinger provides free Let's Encrypt SSL.

After SSL is active, update `.env`:
```env
APP_URL=https://foreverkids.com
```

Then rebuild config cache:
```bash
ssh foreverkids "cd ~/domains/foreverkids.com/forverkids_laravel && php artisan config:cache"
```

---

## 8. Troubleshooting

### Check Laravel Logs

```bash
ssh foreverkids "tail -50 ~/domains/foreverkids.com/forverkids_laravel/storage/logs/laravel.log"
```

### Fix Permission Issues

```bash
ssh foreverkids "cd ~/domains/foreverkids.com/forverkids_laravel && chmod -R 775 storage bootstrap/cache"
```

### 500 Error After Deploy

```bash
ssh foreverkids "cd ~/domains/foreverkids.com/forverkids_laravel && \
  php artisan config:clear && \
  php artisan cache:clear && \
  php artisan view:clear && \
  composer dump-autoload"
```

### Check PHP Version

```bash
ssh foreverkids "php -v"
```

---

## 9. Quick Reference

| Action | Command |
|--------|---------|
| SSH into server | `ssh foreverkids` |
| Deploy single file | `scp file foreverkids:~/domains/foreverkids.com/forverkids_laravel/file` |
| Clear view cache | `ssh foreverkids "cd ~/domains/foreverkids.com/forverkids_laravel && php artisan view:clear"` |
| Clear all caches | `ssh foreverkids "cd ~/domains/foreverkids.com/forverkids_laravel && php artisan optimize:clear"` |
| Rebuild caches | `ssh foreverkids "cd ~/domains/foreverkids.com/forverkids_laravel && php artisan optimize"` |
| View logs | `ssh foreverkids "tail -f ~/domains/foreverkids.com/forverkids_laravel/storage/logs/laravel.log"` |
| DB backup | `ssh foreverkids "mysqldump -u u322703740_foreverkids -p'n6p+3HwU&' u322703740_foreverkids > ~/backup.sql"` |
| Run migrations | `ssh foreverkids "cd ~/domains/foreverkids.com/forverkids_laravel && php artisan migrate --force"` |
