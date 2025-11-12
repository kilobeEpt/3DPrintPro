# Database Rebuild Quick Reference

## 🚀 30-Second Recovery

```bash
# Step 1: Apply schema
mysql -u user -p dbname < database/schema.sql

# Step 2: Seed data
curl https://your-site.com/api/init-database.php

# Step 3: Verify
curl https://your-site.com/api/test.php

# ✅ Done!
```

## 📁 Key Files

| File | Purpose | Idempotent |
|------|---------|------------|
| `database/schema.sql` | Creates 7 tables with indexes | ✅ Yes |
| `database/seed-data.php` | Default content (source of truth) | N/A |
| `api/init-database.php` | Populates database with defaults | ✅ Yes |

## 🔄 Common Operations

### First Time Setup
```bash
# Production site
curl https://ch167436.tw1.ru/api/init-database.php

# Local development
curl http://localhost/api/init-database.php
```

### Update Default Data
1. Edit `database/seed-data.php`
2. Run: `curl https://your-site.com/api/init-database.php`
3. Changes applied automatically (no duplicates)

### Hard Reset (DANGER!)
```bash
# 1. Backup first!
mysqldump -u user -p dbname > backup.sql

# 2. Change token in api/init-database.php (line 21)
# 3. Run reset:
curl https://your-site.com/api/init-database.php?reset=YOUR_TOKEN

# ⚠️ This deletes services, portfolio, testimonials, faq, content_blocks
# ✅ Preserves orders and settings
```

## 📊 Expected Data Counts

| Table | Count | Description |
|-------|-------|-------------|
| services | 6 | FDM, SLA, modeling, prototyping, post-processing, consultation |
| portfolio | 4 | Architecture, prototype, figurine, industrial |
| testimonials | 4 | Customer reviews (all 5-star) |
| faq | 8 | Common questions |
| content_blocks | 3 | Hero, features, about |
| settings | 12+ | Configuration keys |
| orders | Variable | Customer orders (preserved) |

## ✅ Verification Commands

```bash
# Check tables exist
mysql -u user -p dbname -e "SHOW TABLES;"

# Check record counts
mysql -u user -p dbname -e "
  SELECT 'services' as tbl, COUNT(*) as cnt FROM services
  UNION SELECT 'portfolio', COUNT(*) FROM portfolio
  UNION SELECT 'testimonials', COUNT(*) FROM testimonials
  UNION SELECT 'faq', COUNT(*) FROM faq
  UNION SELECT 'content_blocks', COUNT(*) FROM content_blocks
  UNION SELECT 'settings', COUNT(*) FROM settings;"

# Check API works
curl https://your-site.com/api/services.php
curl https://your-site.com/api/portfolio.php
curl https://your-site.com/api/test.php
```

## 🐛 Troubleshooting

| Problem | Solution |
|---------|----------|
| "Database connection failed" | Check `api/config.php` exists and has correct credentials |
| "Table doesn't exist" | Run `database/schema.sql` |
| "No data returned" | Run `api/init-database.php` |
| "Duplicate entries" | Should never happen - script is idempotent! |

## 🔒 Security Checklist

- [ ] `api/config.php` exists and protected by .htaccess
- [ ] RESET_TOKEN changed from default (line 21 in init-database.php)
- [ ] Database user has minimum required privileges
- [ ] Backup system in place
- [ ] .gitignore includes api/config.php

## 📖 Full Documentation

- **Complete Guide:** `database/README.md`
- **Architecture:** `DATABASE_ARCHITECTURE.md`
- **Recovery Scenarios:** `DATABASE_FIX_INSTRUCTIONS.md`
- **Changelog:** `CHANGELOG_REBUILD_SYSTEM.md`

## 💡 Pro Tips

1. **Always test in development first**
2. **Backup before any reset operation**
3. **Run init-database.php after updating seed data**
4. **Use test.php to verify after changes**
5. **Keep seed-data.php in version control**

## 🎯 Recovery Time by Scenario

| Scenario | Time | Commands |
|----------|------|----------|
| Empty DB → Production | 30 sec | schema + seed + verify |
| Update defaults | 5 sec | seed only |
| Hard reset | 60 sec | backup + reset + verify |
| Schema changes | 45 sec | backup + schema + seed + verify |

---

**Version:** 2.0  
**Last Updated:** January 2025  
**Status:** ✅ Production Ready
