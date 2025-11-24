# Storage Directory

This directory contains runtime files and caches that should not be committed to version control.

## Structure

```
storage/
├── cache/              # Application cache files
│   └── settings.json   # Settings cache (5-minute TTL)
└── README.md           # This file
```

## Cache Directory

The `cache/` directory stores JSON cache files for performance optimization.

### Settings Cache

- **File**: `cache/settings.json`
- **Purpose**: Caches all application settings for fast retrieval
- **TTL**: 5 minutes (300 seconds)
- **Format**: JSON with pretty-print formatting
- **Invalidation**: Automatic on all write operations

### Permissions

Ensure the web server has write permissions:

```bash
chmod 755 storage/cache
chown www-data:www-data storage/cache  # Or your web server user
```

## Git Configuration

This directory is configured to:

- **Track structure**: `.gitkeep` files preserve empty directories
- **Ignore contents**: `storage/cache/` is in `.gitignore`
- **Preserve hierarchy**: Directory structure is committed, files are not

## Maintenance

### Clear Cache

To manually clear the cache:

```bash
# Remove cache files
rm -f storage/cache/settings.json

# Or via PHP
php -r "require 'vendor/autoload.php'; require 'bootstrap/eloquent.php'; use App\Services\SettingsService; (new SettingsService())->invalidateCache();"
```

### Warm Cache

To pre-populate the cache:

```bash
php -r "require 'vendor/autoload.php'; require 'bootstrap/eloquent.php'; use App\Services\SettingsService; (new SettingsService())->warmCache();"
```

### Check Cache Status

To view cache file information:

```bash
# Check if cache exists
ls -lh storage/cache/settings.json

# View cache contents
cat storage/cache/settings.json | python -m json.tool

# Check cache age
stat storage/cache/settings.json
```

## Troubleshooting

### Permission Errors

**Symptom**: Cache file cannot be created or written

**Solution**:
```bash
sudo chmod -R 755 storage/cache
sudo chown -R www-data:www-data storage/cache
```

### Cache Not Working

**Symptom**: Settings load slowly despite cache enabled

**Solution**:
1. Check directory exists: `ls -la storage/cache`
2. Check write permissions: `touch storage/cache/test.txt && rm storage/cache/test.txt`
3. Check PHP error logs for permission errors
4. Verify cache file is being created: `ls -lh storage/cache/`

### Stale Cache

**Symptom**: Old values returned after update

**Solution**:
```bash
# Clear cache manually
rm -f storage/cache/settings.json

# Or use invalidate API
curl -X POST https://your-site.com/api/settings.php?invalidate_cache=1
```

## Security

- **No Sensitive Data**: Cache files should not contain unencrypted sensitive information
- **Permissions**: Use restrictive permissions (755 for directories, 644 for files)
- **Public Access**: Ensure cache directory is not accessible via web server
- **Backup Exclusion**: Exclude cache directory from backups

## Future Expansion

This directory can be extended to support:

- **Logs**: Application-specific logs
- **Temp Files**: Temporary file storage
- **Uploads**: Temporary upload processing
- **Sessions**: Custom session storage
- **Compiled**: Compiled views or templates
