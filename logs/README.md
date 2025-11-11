# API Logs Directory

This directory contains API error logs for monitoring and debugging.

## Files

- **api.log** - All API errors, warnings, and info messages

## Log Format

Each log entry includes:
- Timestamp
- Log level (ERROR, WARNING, INFO, DEBUG)
- Request method and URI
- Error message
- Contextual information
- IP address and user agent

## Viewing Logs

```bash
# View last 50 lines
tail -n 50 logs/api.log

# Follow logs in real-time
tail -f logs/api.log

# Search for errors
grep ERROR logs/api.log
```

## Security

- Log files are excluded from git via .gitignore
- Sensitive data (passwords, tokens) are automatically sanitized
- Ensure proper file permissions: `chmod 644 api.log`

## Maintenance

Consider setting up log rotation to prevent files from growing too large.
See DEBUG_DATABASE_INTEGRATION.md for log rotation configuration.
