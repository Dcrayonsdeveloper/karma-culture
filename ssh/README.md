# SSH Connection Details - ForeverKids

## Server Info
- **Host**: 167.88.41.35
- **Port**: 65002
- **User**: u322703740
- **SSH Alias**: `forverkids` (configured in ~/.ssh/config)
- **Identity File**: ~/.ssh/id_ed25519

## Quick Commands

### Connect
```bash
ssh forverkids
```

### Deploy a file
```bash
scp forverkids:~/domains/foreverkids.dcrayons.app/forverkids_laravel/<path> <local-file>
```

### Clear caches after deploy
```bash
ssh forverkids "cd ~/domains/foreverkids.dcrayons.app/forverkids_laravel && php artisan view:clear && php artisan cache:clear && php artisan config:clear"
```

## Server Paths
| Path | Description |
|------|-------------|
| `~/domains/foreverkids.dcrayons.app/forverkids_laravel/` | Laravel project root |
| `~/domains/foreverkids.dcrayons.app/public_html/` | Web document root (public-facing) |
| `~/domains/foreverkids.dcrayons.app/public_html/images/` | Public images folder |
| `~/domains/foreverkids.dcrayons.app/forverkids_laravel/storage/app/public/` | Storage (linked to public) |

## Common Deploy Examples

### Deploy a Blade view
```bash
scp forverkids:~/domains/foreverkids.dcrayons.app/forverkids_laravel/resources/views/home.blade.php d:/projects/forverkids_laravel/resources/views/home.blade.php
```

### Deploy a controller
```bash
scp forverkids:~/domains/foreverkids.dcrayons.app/forverkids_laravel/app/Http/Controllers/MyController.php d:/projects/forverkids_laravel/app/Http/Controllers/MyController.php
```

### Run artisan commands
```bash
ssh forverkids "cd ~/domains/foreverkids.dcrayons.app/forverkids_laravel && php artisan migrate"
```

## Notes
- Old IP `217.21.88.10` is dead — do NOT use
- Always run `php artisan view:clear` after deploying blade templates
- Public images go to `public_html/images/`, NOT `forverkids_laravel/public/images/`
