# ForeverKids Go-Live Checklist

Complete every item below before launching the ForeverKids e-commerce platform to production. Each section must be fully verified. Mark items with `[x]` as they are completed.

---

## Environment Configuration

- [ ] `APP_ENV` is set to `production`
- [ ] `APP_DEBUG` is set to `false`
- [ ] `APP_URL` is set to the correct production domain (e.g., `https://foreverkids.com`)
- [ ] `APP_KEY` has been generated and is unique to production
- [ ] `APP_TIMEZONE` is set correctly
- [ ] `.env` file permissions are `600` (readable only by the application user)
- [ ] All placeholder/example values in `.env` have been replaced with real credentials
- [ ] `SANCTUM_STATEFUL_DOMAINS` includes the production domain

---

## Database

- [ ] All migrations have been run (`php artisan migrate --force`)
- [ ] Production seeders have been executed (admin users, roles/permissions, default settings)
- [ ] Database indexes have been verified on high-traffic tables (`products`, `orders`, `users`, `categories`)
- [ ] Foreign key constraints are in place
- [ ] Database character set is `utf8mb4` with collation `utf8mb4_unicode_ci`
- [ ] Database user has only the permissions needed (no GRANT, DROP DATABASE, etc.)
- [ ] Database connection pooling is configured (if applicable)
- [ ] Test data has been removed from the production database

---

## Security

- [ ] SSL certificate is installed and valid
- [ ] SSL auto-renewal is configured (certbot timer or cron)
- [ ] HTTP to HTTPS redirect is in place
- [ ] Security headers are configured in Nginx (HSTS, X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy)
- [ ] CSRF protection is enabled (default in Laravel)
- [ ] API rate limiting is configured in `routes/api.php` or via middleware
- [ ] `.env` file is not accessible via web (Nginx denies `/\.` paths)
- [ ] `composer.json`, `package.json`, `.git/` are not accessible via web
- [ ] File upload validation is in place (mime types, file size limits)
- [ ] SQL injection protection is verified (Eloquent ORM / parameterized queries)
- [ ] XSS protection is verified (Blade `{{ }}` escaping)
- [ ] Admin panel access is restricted (IP whitelist or VPN, if applicable)
- [ ] Default admin passwords have been changed
- [ ] `APP_DEBUG=false` confirmed (double-check -- debug mode leaks sensitive data)
- [ ] Sensitive data is not logged (passwords, tokens, credit card numbers)

---

## Performance

- [ ] Configuration cache is built (`php artisan config:cache`)
- [ ] Route cache is built (`php artisan route:cache`)
- [ ] View cache is built (`php artisan view:cache`)
- [ ] Event cache is built (`php artisan event:cache`)
- [ ] Composer autoloader is optimized (`composer install --optimize-autoloader`)
- [ ] OPcache is enabled and configured
- [ ] Queue worker is running via Supervisor
- [ ] Frontend assets are compiled and minified (`npm run build`)
- [ ] Images are optimized (compressed, appropriate dimensions)
- [ ] Static assets are served with cache headers (30-day expiry)
- [ ] Gzip compression is enabled in Nginx
- [ ] Database queries are optimized (N+1 queries resolved, proper eager loading)
- [ ] Redis is configured for cache and sessions

---

## Email

- [ ] Mail driver is configured for production (SMTP, Mailgun, SES, etc.)
- [ ] `MAIL_FROM_ADDRESS` and `MAIL_FROM_NAME` are set correctly
- [ ] Transactional emails have been tested:
  - [ ] Registration welcome email
  - [ ] Order confirmation email
  - [ ] Order shipped notification
  - [ ] Order cancelled notification
  - [ ] Password reset email
- [ ] Email templates are branded and reviewed
- [ ] SPF, DKIM, and DMARC DNS records are configured for email deliverability
- [ ] Email sending does not block web requests (sent via queue)

---

## Payment Gateway

- [ ] Production API keys are configured (not sandbox/test keys)
- [ ] Webhook endpoints are registered with the payment provider
- [ ] Webhook signature verification is implemented
- [ ] Payment flow has been tested end-to-end with a real transaction
- [ ] Refund flow has been tested
- [ ] Payment failure handling is graceful (user-friendly error messages)
- [ ] PCI compliance requirements are met (no raw card data stored)
- [ ] Payment confirmation updates order status correctly

---

## Search

- [ ] Meilisearch is running in production with a master key set
- [ ] Product search index has been built (`php artisan scout:import "App\Models\Product"`)
- [ ] Search results are accurate and relevant
- [ ] Search performance is acceptable (< 200ms response time)
- [ ] Meilisearch API key is restricted (use a search-only key for the frontend)
- [ ] Search suggestions endpoint is working

---

## Storage

- [ ] S3 (or equivalent object storage) is configured for production file uploads
- [ ] Storage symlink is created (`php artisan storage:link`)
- [ ] Product image uploads are working correctly
- [ ] File upload size limits are configured (Nginx `client_max_body_size` and PHP `upload_max_filesize`)
- [ ] Uploaded files are served through the CDN (if configured)
- [ ] Old/orphaned files cleanup is scheduled (if applicable)
- [ ] Storage bucket permissions are properly scoped (no public write access)

---

## Monitoring

- [ ] Error tracking is configured (Sentry or Bugsnag)
- [ ] Error tracking has been tested (`php artisan sentry:test` or equivalent)
- [ ] Uptime monitoring is active on `https://foreverkids.com/up`
- [ ] Uptime monitoring is active on the homepage
- [ ] Log rotation is set up for Laravel logs, Nginx logs, and Supervisor logs
- [ ] Slack or email notifications are configured for critical errors
- [ ] Server resource monitoring is in place (CPU, memory, disk)
- [ ] Queue failure notifications are configured

---

## Backup

- [ ] Automated database backup is scheduled (daily via cron)
- [ ] Storage backup is scheduled (weekly via cron)
- [ ] Backups are stored offsite (S3 or equivalent)
- [ ] Backup restoration has been tested successfully
- [ ] Backup retention policy is in place (e.g., 30 days for DB, 14 days for storage)
- [ ] Backup scripts are executable and owned by the correct user
- [ ] Backup logs are being written and reviewed

---

## DNS and Domain

- [ ] A records are configured and pointing to the production server IP
- [ ] AAAA records are configured (IPv6, if applicable)
- [ ] `www` subdomain redirects to the bare domain (or vice versa)
- [ ] SSL certificate covers both `foreverkids.com` and `www.foreverkids.com`
- [ ] SSL auto-renewal is verified (`certbot renew --dry-run`)
- [ ] DNS TTL has been lowered before the switch (for quick rollback)
- [ ] CDN DNS is configured (if using CloudFront, Cloudflare, etc.)
- [ ] MX records are configured for email
- [ ] SPF, DKIM, and DMARC records are configured

---

## Testing

- [ ] Smoke tests have passed on the production URL:
  - [ ] Homepage loads correctly
  - [ ] Product listing page works
  - [ ] Product detail page works
  - [ ] Search returns results
  - [ ] User registration works
  - [ ] User login works
  - [ ] Add to cart works
  - [ ] Checkout completes successfully
  - [ ] Order confirmation is received
- [ ] Critical user flows have been verified end-to-end:
  - [ ] Browse -> Add to Cart -> Checkout -> Order Confirmation
  - [ ] Register -> Browse -> Purchase -> View Order History
  - [ ] Search -> Filter -> Add to Cart
  - [ ] Wishlist add/remove
- [ ] Mobile responsive layout has been verified on:
  - [ ] iPhone (Safari)
  - [ ] Android (Chrome)
  - [ ] Tablet
- [ ] API endpoints have been tested (Postman or equivalent)
- [ ] Load testing has been performed (target: sustain 100 concurrent users)
- [ ] 404 page displays correctly
- [ ] 500 error page displays correctly (custom, not debug)

---

## Rollback Plan

- [ ] Previous working version is tagged in Git (e.g., `v1.0.0-pre-launch`)
- [ ] Database rollback script is ready (`php artisan migrate:rollback --step=N`)
- [ ] If using destructive migrations, a manual SQL rollback script exists
- [ ] Deployment can be reverted in under 5 minutes
- [ ] Rollback procedure has been documented and communicated to the team
- [ ] DNS TTL is low enough to allow quick DNS changes (if needed)
- [ ] Previous database backup is available for restoration

---

## Post-Launch

- [ ] Cache warming has been run:
  - [ ] `php artisan config:cache`
  - [ ] `php artisan route:cache`
  - [ ] `php artisan view:cache`
  - [ ] Visit key pages to populate application cache (home, categories, featured products)
- [ ] Search index has been refreshed (`php artisan scout:import "App\Models\Product"`)
- [ ] First-day monitoring plan is in place:
  - [ ] Team member assigned to watch error tracking dashboard
  - [ ] Team member assigned to watch server metrics
  - [ ] Team member assigned to monitor customer support channels
  - [ ] Check queue processing is running smoothly
  - [ ] Check email delivery is working
  - [ ] Check payment processing is working
- [ ] Social media and marketing are aligned on launch timing
- [ ] Customer support team is briefed and ready
- [ ] Incident response plan is documented (who to contact, escalation path)

---

## Sign-Off

| Role                | Name | Date | Approved |
|---------------------|------|------|----------|
| Lead Developer      |      |      | [ ]      |
| DevOps / SysAdmin   |      |      | [ ]      |
| QA Lead             |      |      | [ ]      |
| Product Owner       |      |      | [ ]      |
| Security Review     |      |      | [ ]      |
