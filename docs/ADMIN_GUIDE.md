# Admin Guide

Complete guide for using the 3D Print Pro admin panel.

## Accessing the Admin Panel

### Login

1. Navigate to: `https://your-domain.com/admin/login.php`
2. Enter credentials:
   - **Username:** Your admin username
   - **Password:** Your admin password
3. Click **Login**

**Default Credentials** (if just set up):
- Created via: `php scripts/setup-admin-credentials.php`
- Change immediately after first login!

### Login Security

The admin panel uses secure PHP session-based authentication:
- **HttpOnly cookies** - JavaScript cannot access session cookies
- **SameSite protection** - CSRF attack prevention
- **30-minute timeout** - Automatic logout after inactivity
- **Login rate limiting** - 5 attempts max, 15-minute lockout
- **Session regeneration** - ID regenerated every 15 minutes

### Logout

Click the **Logout** button in the navigation bar, or navigate to:
```
https://your-domain.com/admin/logout.php
```

## Dashboard

The dashboard provides an overview of your business:

### Statistics Cards

- **Total Orders** - All orders ever submitted
- **New Orders** - Orders with status "new"
- **Completed Orders** - Orders with status "completed"
- **Total Revenue** - Sum of all order amounts

### Orders Chart

Line chart showing orders over the last 30 days by status:
- New (blue)
- Processing (yellow)
- Completed (green)

### Recent Orders

Table showing last 10 orders with:
- Order number
- Customer name
- Amount
- Status badge
- Created date
- Quick view button

## Orders Management

**URL:** `/admin/orders.php`

### Orders List

View all orders with filtering and search:

**Features:**
- **Status filter** - All, New, Processing, Completed, Cancelled
- **Type filter** - All, Orders, Contact forms
- **Search** - By name, phone, email, order number
- **Pagination** - 20 orders per page
- **Export CSV** - Download orders as spreadsheet

### Order Details

Click on an order to view full details:

**Customer Information:**
- Name
- Email
- Phone
- Telegram username

**Order Information:**
- Order number (unique ID)
- Type (order or contact)
- Service requested
- Message/details
- Amount (if calculated)

**Calculator Data** (if order from calculator):
- Technology (FDM, SLA, etc.)
- Material (PLA, ABS, etc.)
- Weight
- Quantity
- Fill percentage
- Quality
- Timeline
- Support structures

**Status Tracking:**
- Current status
- Telegram notification status
- Created date
- Last updated date

### Change Order Status

1. Click **Edit** on an order
2. Select new status:
   - **New** - Just submitted
   - **Processing** - Being worked on
   - **Completed** - Finished
   - **Cancelled** - Cancelled
3. Click **Save**

**Note:** Changing status sends Telegram notification (if configured)

### Delete Orders

1. Click **Delete** on an order
2. Confirm deletion
3. Order permanently removed

**Warning:** This cannot be undone!

### Export Orders

Click **Export CSV** button to download all orders as spreadsheet.

**Contains:**
- Order number, type, status
- Customer details
- Order details
- Dates

## Services Management

**URL:** `/admin/services.php`

### Services List

View and manage all services offered:

**Table columns:**
- Icon preview
- Service name
- Price
- Category
- Featured badge
- Active status
- Actions (Edit, Delete)

### Add New Service

1. Click **Add Service** button
2. Fill in form:
   - **Name** - Service name (e.g., "FDM печать")
   - **Slug** - URL-friendly name (e.g., "fdm-printing")
   - **Icon** - Font Awesome class (e.g., "fa-print")
   - **Description** - Service description
   - **Features** - List of features (one per line)
   - **Price** - Price text (e.g., "от 50₽/г")
   - **Category** - Service category
   - **Sort Order** - Display order (lower = first)
   - **Active** - Show on website
   - **Featured** - Highlight on homepage
3. Click **Save**

### Edit Service

1. Click **Edit** on a service
2. Modify fields
3. Click **Save**

### Delete Service

1. Click **Delete** on a service
2. Confirm deletion
3. Service removed from database

## Portfolio Management

**URL:** `/admin/portfolio.php`

### Portfolio List

Showcase your completed projects:

**Features:**
- Image preview
- Project title
- Category
- Active status

### Add Portfolio Item

1. Click **Add Portfolio Item**
2. Fill in:
   - **Title** - Project name
   - **Description** - Project details
   - **Image URL** - Path to image
   - **Category** - Project category
   - **Tags** - Comma-separated tags
   - **Sort Order** - Display order
   - **Active** - Show on website
3. Click **Save**

### Upload Images

Images should be uploaded via FTP to `/images/portfolio/` directory, then reference in Image URL field:
```
/images/portfolio/project1.jpg
```

## Testimonials Management

**URL:** `/admin/testimonials.php`

### Testimonials List

Manage customer reviews:

**Table shows:**
- Customer name
- Rating (1-5 stars)
- Review text (excerpt)
- Approved status
- Active status

### Add Testimonial

1. Click **Add Testimonial**
2. Fill in:
   - **Name** - Customer name
   - **Position** - Job title/company
   - **Avatar** - Image URL
   - **Text** - Review text
   - **Rating** - 1-5 stars
   - **Sort Order** - Display order
   - **Approved** - Moderation approval
   - **Active** - Show on website
3. Click **Save**

### Moderate Testimonials

- Check **Approved** to show on website
- Uncheck to hide without deleting

## FAQ Management

**URL:** `/admin/faq.php`

### FAQ List

Manage frequently asked questions:

**Table shows:**
- Question
- Answer (excerpt)
- Active status

### Add FAQ Item

1. Click **Add FAQ**
2. Fill in:
   - **Question** - The question
   - **Answer** - Detailed answer
   - **Sort Order** - Display order
   - **Active** - Show on website
3. Click **Save**

### Organize FAQ

- Use **Sort Order** to arrange questions
- Lower numbers appear first
- Group related questions together

## Content Blocks

**URL:** `/admin/content.php`

### Content Blocks List

Manage dynamic page content:

**Examples:**
- `home_hero` - Homepage hero section
- `home_features` - Features section
- `about_intro` - About page intro

### Edit Content Block

1. Click **Edit** on a block
2. Modify:
   - **Title** - Block title
   - **Content** - HTML/text content
   - **Data** - JSON data (advanced)
3. Click **Save**

**Note:** Changes appear immediately on website

## User Management

**URL:** `/admin/users.php`

**Access:** Super Administrator only ⚠️

### Overview

The User Management interface allows super administrators to manage admin accounts, roles, and access control. This is a critical security feature that should only be accessed by trusted super administrators.

### First-Time Setup (Onboarding)

When you first install 3D Print Pro, no admin users exist. The system automatically detects this and displays an **onboarding banner** prompting you to create the first super administrator:

1. Navigate to `/admin/users.php`
2. The modal will open automatically
3. Fill in:
   - **Email** - Admin email address (used for login)
   - **Name** - Full name
   - **Password** - Strong password (min 8 characters, must include letters and numbers)
   - **Role** - Automatically set to Super Administrator
4. Click **Save**
5. You'll be redirected to the login page
6. Login with your new credentials

**Security Note:** The first user is always created as a super administrator to ensure system access.

### User Roles

3D Print Pro implements role-based access control (RBAC) with three levels:

#### Super Administrator
- **Full access** to all features
- Can manage other admin users
- Can create, edit, and delete accounts
- Can change user roles and status
- Cannot be restricted

#### Administrator
- **Standard access** to content management
- Can manage orders, services, portfolio, testimonials, FAQ, content blocks
- Can modify settings
- **Cannot** access user management

#### Editor
- **Limited access** to content
- Can view and edit content
- **Cannot** delete items
- **Cannot** modify settings
- **Cannot** access user management

### Users List

The users page displays all admin accounts with:

**Search & Filters:**
- **Search** - Find users by email or name
- **Role filter** - Filter by super_admin, admin, or editor
- **Status filter** - Filter by active, inactive, or locked

**Table columns:**
- **Name / Email** - User identification
- **Role** - Badge showing user role
- **Status** - Active (green), Inactive (gray), or Locked (red)
- **Last Login** - When user last logged in
- **Active Sessions** - Number of active sessions (security monitoring)
- **Actions** - Edit, View History, Delete buttons

### Add New User

1. Click **Add User** button
2. Fill in the form:
   - **Email** *(required)* - Unique email address
   - **Name** *(required)* - Full name
   - **Password** *(required)* - Must be at least 8 characters with letters and numbers
   - **Role** *(required)* - Select super_admin, admin, or editor
   - **Status** *(required)* - Select active, inactive, or locked
3. Click **Save**

**Validation:**
- Email must be unique
- Password must meet complexity requirements
- All required fields must be filled

### Edit User

1. Click **Edit** (pencil icon) on a user
2. Modify fields:
   - **Email** - Change email (must remain unique)
   - **Name** - Update name
   - **Password** - Leave empty to keep current password, or enter new one
   - **Role** - Change role (affects permissions immediately)
   - **Status** - Change status
3. Click **Save**

**Important:**
- Changing password **forces logout** of all user's sessions
- Changing role **forces logout** of all user's sessions
- Deactivating or locking **forces logout** immediately
- You **cannot** change your own role or deactivate yourself (safety feature)

### Delete User

1. Click **Delete** (trash icon) on a user
2. Confirm deletion
3. User is permanently removed
4. All user's sessions are terminated

**Restrictions:**
- You **cannot** delete yourself
- Deletion is permanent and cannot be undone
- All user's action logs are preserved for audit trail

### View Audit History

Click **View History** (clock icon) to see a user's action log:

**Displayed information:**
- **Action type** - Login, Logout, Create, Update, Delete
- **Entity** - What was affected (admin_user, service, order, etc.)
- **Timestamp** - When action occurred
- **IP address** - Where action originated
- **Payload** - Detailed changes (JSON format)

**Use cases:**
- Security auditing
- Compliance tracking
- Troubleshooting user actions
- Investigating suspicious activity

### User Status Management

#### Active
- User can login normally
- All features accessible based on role
- Default status for new users

#### Inactive
- User **cannot** login
- Existing sessions terminated immediately
- Use for temporary suspension

#### Locked
- User **cannot** login
- Automatically set after 5 failed login attempts
- Lock duration: 15 minutes
- Can be manually set for security reasons

### Session Management

The **Active Sessions** column shows how many sessions each user has:

- **Green badge** - User has active sessions
- **Gray "0"** - User not currently logged in

**Security implications:**
- Multiple sessions may indicate account sharing (security risk)
- Sessions expire after 30 minutes of inactivity
- Password changes terminate all sessions (security feature)

### Password Security

**Requirements:**
- Minimum 8 characters
- At least one letter (a-z, A-Z)
- At least one number (0-9)
- Maximum 255 characters

**Best practices:**
- Use strong, unique passwords
- Enable password manager
- Change passwords regularly
- Never share credentials

**Password Reset Flow:**
1. Edit user
2. Enter new password
3. Save
4. User is logged out of all sessions
5. User must login with new password

### Security & Access Control

**Super Admin Only:**
- Only super administrators see the "Users" link in sidebar
- Other roles receive 403 Forbidden if they try to access
- All API endpoints enforce super_admin role check

**Audit Trail:**
- All user management actions are logged
- Logs include: create, update, delete, password changes
- Logs cannot be deleted (compliance)
- Logs include IP address and user agent

**Rate Limiting:**
- API endpoints rate-limited to prevent abuse
- Login attempts limited to 5 per 15 minutes
- Automatic lockout after failed attempts

### Common Workflows

#### Onboard New Team Member
1. Click **Add User**
2. Enter their email and name
3. Generate strong password (share securely)
4. Select appropriate role (usually "admin")
5. Set status to "active"
6. Send credentials securely (not via email!)
7. Ask them to change password on first login

#### Offboard Team Member
1. Find user in list
2. Option A: Set status to "inactive" (soft delete)
3. Option B: Click **Delete** (permanent removal)
4. All sessions terminated immediately
5. User cannot login

#### Promote User
1. Click **Edit** on user
2. Change role to higher level
3. Save
4. User logged out and must login again
5. New permissions take effect

#### Handle Security Incident
1. Identify compromised account
2. Click **Edit** on user
3. Set status to "locked"
4. Change password
5. Save (terminates all sessions)
6. Review audit history for suspicious activity
7. Unlock and reset password when resolved

## Settings

**URL:** `/admin/settings.php`

### Site Settings

**General:**
- Site name
- Site description
- Keywords
- Company info (name, address, phone, email, hours)

### Telegram Configuration

**Bot Settings:**
- **Bot Token** - From @BotFather
- **Chat ID** - Your chat/group ID
- **Contact URL** - Public bot link (e.g., `https://t.me/YourBot`)

**Notification Settings:**
- ☑ **Notify about new orders** - Send notification when customer submits order
- ☑ **Notify about status changes** - Send notification when order status changes

**Test Connection:**
- Click **Send Test Message** button
- Check Telegram for message
- If successful, configuration is correct ✅

See [TELEGRAM_INTEGRATION.md](TELEGRAM_INTEGRATION.md) for setup guide.

### Email Settings

Configure email notifications (if implemented):
- SMTP host
- SMTP port
- SMTP username
- SMTP password

### Save Settings

1. Modify settings
2. Click **Save Changes**
3. Settings saved to database
4. Changes take effect immediately

## Admin UI Features

### Navigation

**Sidebar menu:**
- Dashboard
- Orders (with badge showing new count)
- Services
- Portfolio
- Testimonials
- FAQ
- Content
- Settings

### Notifications

Toast notifications appear for:
- Successful saves ✅
- Errors ❌
- Warnings ⚠️
- Info messages ℹ️

### Modal Dialogs

Forms open in modal dialogs:
- Add/edit services
- Add/edit portfolio items
- Add/edit testimonials
- Add/edit FAQ items

### Loading States

Spinners show during:
- Data fetching
- Form submission
- API requests

### Empty States

Helpful messages when:
- No data exists
- Filters return no results
- Errors occur

## Security Best Practices

### Password Management

1. **Use strong passwords:**
   - At least 12 characters
   - Mix of uppercase, lowercase, numbers, symbols
   - Avoid common words

2. **Change password regularly:**
   ```bash
   php scripts/setup-admin-credentials.php
   ```

3. **Never share credentials:**
   - Don't email passwords
   - Don't write on paper
   - Use password manager

### Session Security

- **30-minute timeout** - Logout after inactivity
- **HTTPS only** - Never use HTTP for admin
- **Secure cookies** - HttpOnly, SameSite, Secure flags

### CSRF Protection

All state-changing operations require CSRF token:
- Automatically added by JavaScript
- Validated on server
- Regenerated on login

### IP Restrictions (Optional)

Consider restricting admin access to specific IPs via hosting panel.

## Troubleshooting

### Cannot Login

**Problem:** "Invalid credentials" error

**Solutions:**
1. Verify username is correct (case-sensitive)
2. Reset password:
   ```bash
   php scripts/setup-admin-credentials.php admin NewPassword123
   ```
3. Check database:
   ```sql
   SELECT * FROM settings WHERE setting_key LIKE 'admin%';
   ```

### Session Expired

**Problem:** Logged out immediately

**Solutions:**
1. Check cookies enabled in browser
2. Use HTTPS (not HTTP)
3. Clear browser cache
4. Try incognito/private mode

### CSRF Token Invalid

**Problem:** 403 error on form submit

**Solutions:**
1. Refresh page to get new token
2. Check if cookies blocked
3. Verify session not expired
4. Clear browser cache

### Data Not Loading

**Problem:** Empty lists or errors

**Solutions:**
1. Check browser console (F12)
2. Check Network tab for API errors
3. Verify database connection:
   ```
   https://your-domain.com/api/test.php
   ```
4. Check admin authentication

### Cannot Send Test Message

**Problem:** Telegram test fails

**Solutions:**
1. Verify bot token is correct
2. Verify chat ID is correct
3. Check bot not blocked
4. See [TELEGRAM_INTEGRATION.md](TELEGRAM_INTEGRATION.md)

## Advanced Features

### Bulk Operations

Select multiple items to:
- Delete multiple orders
- Activate/deactivate multiple services
- Export selected orders

### Keyboard Shortcuts

- `Esc` - Close modal
- `Ctrl+S` - Save form (in modals)
- `Ctrl+F` - Focus search

### Dark Mode

Toggle theme in settings or profile menu:
- Light theme (default)
- Dark theme
- Auto (system preference)

## Mobile Admin

Admin panel is responsive and works on mobile:

**Features:**
- Collapsible sidebar
- Touch-friendly buttons
- Swipe gestures
- Mobile-optimized forms

**Tips:**
- Use landscape for tables
- Pinch to zoom charts
- Pull to refresh lists

## Maintenance

### Regular Tasks

**Daily:**
- Check new orders
- Respond to inquiries
- Update order statuses

**Weekly:**
- Review statistics
- Check error logs
- Update content

**Monthly:**
- Change admin password
- Review services/pricing
- Backup database

### Database Backup

1. Navigate to: `https://your-domain.com/database/backup.php?token=YOUR_BACKUP_TOKEN`
2. Download backup file
3. Store securely offline

Or via SSH:
```bash
cd /path/to/site
php database/backup.php
```

### Logs

Check logs for errors:
```bash
tail -f logs/api.log
grep -i "error\|failed" logs/api.log
```

## Support

For help:
- [TROUBLESHOOTING.md](TROUBLESHOOTING.md) - Common issues
- [API_REFERENCE.md](API_REFERENCE.md) - API documentation
- [TELEGRAM_INTEGRATION.md](TELEGRAM_INTEGRATION.md) - Telegram setup
- Check logs: `logs/api.log`
- Database audit: `php scripts/db_audit.php`
