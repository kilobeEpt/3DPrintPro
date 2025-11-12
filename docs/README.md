# Documentation

Complete documentation suite for 3D Print Pro.

## Core Guides

These are the primary documents you'll need for setup, deployment, and maintenance:

### 🚀 [SETUP_GUIDE.md](SETUP_GUIDE.md)
Complete installation and configuration guide.
- Prerequisites and requirements
- Database setup
- Backend configuration
- Admin credentials setup
- Frontend configuration
- Verification steps
- Troubleshooting installation issues

### 📦 [DEPLOYMENT.md](DEPLOYMENT.md)
Production deployment procedures and checklist.
- Pre-deployment checklist
- File upload and permissions
- Database setup on production
- HTTPS configuration
- Domain configuration
- Post-deployment verification
- Production hardening
- Rollback procedures

### 🔌 [API_REFERENCE.md](API_REFERENCE.md)
Complete REST API endpoint documentation.
- API overview and conventions
- HTTP status codes
- Response structures
- Rate limiting
- Security headers
- All 8 API endpoints with examples
- Error handling
- JavaScript client usage

### 👤 [ADMIN_GUIDE.md](ADMIN_GUIDE.md)
Admin panel features and usage instructions.
- Accessing the admin panel
- Dashboard overview
- Orders management
- Services, portfolio, testimonials, FAQ management
- Content blocks editing
- Settings configuration
- Telegram setup
- Security best practices

### 🗄️ [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md)
Database tables, columns, indexes, and relationships.
- Complete schema for all 7 tables
- Column types and constraints
- Indexes and performance
- Initialization and seeding
- Backup and restore
- Maintenance procedures
- Schema validation

### 🛠️ [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
Common issues and their solutions.
- Quick diagnostics commands
- Database issues
- API issues
- Frontend issues
- Admin panel issues
- Telegram issues
- Performance issues
- Security issues
- Production issues

## Additional Documentation

### 📱 [TELEGRAM_INTEGRATION.md](TELEGRAM_INTEGRATION.md)
Telegram bot setup and configuration.
- Creating a Telegram bot
- Getting chat ID
- Configuration in admin panel
- Notification settings
- Troubleshooting Telegram issues
- Security considerations
- Code architecture

### 🔐 [ADMIN_AUTHENTICATION.md](ADMIN_AUTHENTICATION.md)
Security and authentication system details.
- Architecture overview
- Session security
- Login rate limiting
- CSRF protection
- API endpoint protection
- Integration with frontend
- Security best practices
- Troubleshooting auth issues

### ✅ [TEST_CHECKLIST.md](TEST_CHECKLIST.md)
Comprehensive testing procedures and checklist.
- Frontend testing
- Forms testing
- Calculator testing
- API testing
- Admin panel testing
- Security testing
- Mobile responsive testing
- Offline/online testing

## Quick Reference

### For New Installations
1. Read [SETUP_GUIDE.md](SETUP_GUIDE.md)
2. Follow [DEPLOYMENT.md](DEPLOYMENT.md)
3. Review [ADMIN_GUIDE.md](ADMIN_GUIDE.md)

### For API Development
1. Read [API_REFERENCE.md](API_REFERENCE.md)
2. Check [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md)
3. Review [ADMIN_AUTHENTICATION.md](ADMIN_AUTHENTICATION.md)

### For Troubleshooting
1. Start with [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
2. Run diagnostics: `php scripts/db_audit.php`
3. Check specific guide for your issue area

## Document Status

All documents reflect the current architecture:
- ✅ MySQL 8.0+ database
- ✅ PHP 7.4+ REST API
- ✅ Secure session-based admin authentication
- ✅ Telegram integration with database-driven config
- ✅ Rate limiting and security headers
- ✅ Complete CRUD operations for all resources

**Last Updated:** January 2025  
**Version:** 2.0  
**Status:** Production Ready

## Contributing

When updating documentation:
1. Keep guides focused and concise
2. Include code examples
3. Add troubleshooting sections
4. Cross-reference related docs
5. Update this README if adding new guides

## Archive

Legacy documentation has been moved to [archive/](archive/) for historical reference. These documents are superseded by the current guides but preserved for context.

## Support

For issues not covered in these guides:
1. Check [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
2. Run diagnostics: `php scripts/db_audit.php`
3. Check logs: `tail -f logs/api.log`
4. Review relevant guide sections
