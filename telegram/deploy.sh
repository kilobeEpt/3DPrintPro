#!/bin/bash
################################################################################
# Telegram Bot Deployment Script
# 
# This script automates the deployment and setup of the Telegram bot system.
# Run this on your production server after uploading files.
#
# Usage: bash telegram/deploy.sh
################################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

echo -e "${BLUE}=====================================${NC}"
echo -e "${BLUE}Telegram Bot Deployment${NC}"
echo -e "${BLUE}=====================================${NC}"
echo ""

# Check if running from correct directory
if [ ! -f "$PROJECT_ROOT/.env" ]; then
    echo -e "${RED}✗ Error: .env file not found${NC}"
    echo -e "${YELLOW}Please run this script from the project root or ensure .env exists${NC}"
    exit 1
fi

echo -e "${GREEN}✓${NC} Found .env file"

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo -e "${RED}✗ Error: PHP is not installed or not in PATH${NC}"
    echo -e "${YELLOW}Please install PHP to continue${NC}"
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo -e "${GREEN}✓${NC} PHP $PHP_VERSION detected"

# Check required PHP extensions
echo ""
echo "Checking PHP extensions..."

REQUIRED_EXTENSIONS=("curl" "json" "mbstring")
MISSING_EXTENSIONS=()

for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if php -m | grep -q "^$ext$"; then
        echo -e "${GREEN}✓${NC} $ext"
    else
        echo -e "${RED}✗${NC} $ext (missing)"
        MISSING_EXTENSIONS+=("$ext")
    fi
done

if [ ${#MISSING_EXTENSIONS[@]} -ne 0 ]; then
    echo -e "${RED}✗ Error: Missing required PHP extensions: ${MISSING_EXTENSIONS[*]}${NC}"
    exit 1
fi

# Check required files
echo ""
echo "Checking required files..."

REQUIRED_FILES=(
    "php/TelegramBot.php"
    "telegram/webhook.php"
    "telegram/setup-webhook.php"
    "order-submit.php"
    ".env"
)

for file in "${REQUIRED_FILES[@]}"; do
    if [ -f "$PROJECT_ROOT/$file" ]; then
        echo -e "${GREEN}✓${NC} $file"
    else
        echo -e "${RED}✗${NC} $file (missing)"
        exit 1
    fi
done

# Create storage directories
echo ""
echo "Creating storage directories..."

STORAGE_DIRS=(
    "storage/data"
    "storage/logs"
    "storage/uploads/orders"
    "storage/cache/order_rate_limit"
)

for dir in "${STORAGE_DIRS[@]}"; do
    if [ ! -d "$PROJECT_ROOT/$dir" ]; then
        mkdir -p "$PROJECT_ROOT/$dir"
        chmod 755 "$PROJECT_ROOT/$dir"
        echo -e "${GREEN}✓${NC} Created $dir"
    else
        echo -e "${GREEN}✓${NC} $dir already exists"
    fi
done

# Set permissions
echo ""
echo "Setting file permissions..."

chmod 644 "$PROJECT_ROOT/php/TelegramBot.php"
chmod 644 "$PROJECT_ROOT/telegram/webhook.php"
chmod 755 "$PROJECT_ROOT/telegram/setup-webhook.php"
chmod 644 "$PROJECT_ROOT/order-submit.php"
chmod 600 "$PROJECT_ROOT/.env"

echo -e "${GREEN}✓${NC} Permissions set"

# Check .env configuration
echo ""
echo "Checking .env configuration..."

# Source .env file
if [ -f "$PROJECT_ROOT/.env" ]; then
    export $(grep -v '^#' "$PROJECT_ROOT/.env" | xargs)
fi

if [ -z "$TELEGRAM_BOT_TOKEN" ]; then
    echo -e "${RED}✗ Error: TELEGRAM_BOT_TOKEN not set in .env${NC}"
    exit 1
fi
echo -e "${GREEN}✓${NC} TELEGRAM_BOT_TOKEN configured"

if [ -z "$TELEGRAM_PASSWORD" ]; then
    echo -e "${RED}✗ Error: TELEGRAM_PASSWORD not set in .env${NC}"
    exit 1
fi
echo -e "${GREEN}✓${NC} TELEGRAM_PASSWORD configured"

if [ -z "$APP_URL" ]; then
    echo -e "${RED}✗ Error: APP_URL not set in .env${NC}"
    exit 1
fi
echo -e "${GREEN}✓${NC} APP_URL configured: $APP_URL"

# Setup webhook
echo ""
echo -e "${YELLOW}=====================================${NC}"
echo -e "${YELLOW}Setting up Telegram webhook...${NC}"
echo -e "${YELLOW}=====================================${NC}"
echo ""

cd "$PROJECT_ROOT"

# Check if webhook is already configured
if [ -n "$TELEGRAM_WEBHOOK_SECRET" ] && [ "$TELEGRAM_WEBHOOK_SECRET" != "" ]; then
    echo -e "${YELLOW}Webhook secret already exists in .env${NC}"
    echo -e "${YELLOW}Do you want to reconfigure? This will reset the webhook.${NC}"
    read -p "Continue? (yes/no): " -r
    if [[ ! $REPLY =~ ^[Yy]es$ ]]; then
        echo -e "${BLUE}Skipping webhook setup${NC}"
    else
        php telegram/setup-webhook.php
    fi
else
    echo -e "${BLUE}Running webhook setup (will generate secret)...${NC}"
    echo ""
    php telegram/setup-webhook.php
fi

# Run system tests
echo ""
echo -e "${YELLOW}=====================================${NC}"
echo -e "${YELLOW}Running system tests...${NC}"
echo -e "${YELLOW}=====================================${NC}"
echo ""

if php telegram/test-system.php; then
    echo ""
    echo -e "${GREEN}✓ All tests passed!${NC}"
else
    echo ""
    echo -e "${RED}✗ Some tests failed. Please check the output above.${NC}"
    exit 1
fi

# Create .htaccess for storage protection (Apache only)
echo ""
echo "Setting up storage protection..."

if [ ! -f "$PROJECT_ROOT/storage/.htaccess" ]; then
    cat > "$PROJECT_ROOT/storage/.htaccess" << 'EOF'
# Deny direct access to storage directory
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Deny from all
</IfModule>
EOF
    chmod 644 "$PROJECT_ROOT/storage/.htaccess"
    echo -e "${GREEN}✓${NC} Created storage/.htaccess"
else
    echo -e "${GREEN}✓${NC} storage/.htaccess already exists"
fi

# Display summary
echo ""
echo -e "${GREEN}=====================================${NC}"
echo -e "${GREEN}Deployment Complete!${NC}"
echo -e "${GREEN}=====================================${NC}"
echo ""
echo -e "${BLUE}Next steps:${NC}"
echo ""
echo -e "1. ${YELLOW}Open Telegram and find your bot${NC}"
echo -e "   (Search for bot username from @BotFather)"
echo ""
echo -e "2. ${YELLOW}Send /start command${NC}"
echo ""
echo -e "3. ${YELLOW}Enter password: $TELEGRAM_PASSWORD${NC}"
echo ""
echo -e "4. ${YELLOW}Send test notification:${NC}"
echo -e "   ${BLUE}php telegram/test-notification.php${NC}"
echo ""
echo -e "5. ${YELLOW}Test order form:${NC}"
echo -e "   ${BLUE}Visit: $APP_URL${NC}"
echo -e "   ${BLUE}Submit an order and check Telegram${NC}"
echo ""
echo -e "${GREEN}Webhook URL:${NC} $APP_URL/telegram/webhook.php"
echo ""
echo -e "${BLUE}Useful commands:${NC}"
echo -e "  php telegram/manage-users.php list      ${YELLOW}# List authorized users${NC}"
echo -e "  php telegram/test-notification.php      ${YELLOW}# Send test notification${NC}"
echo -e "  php telegram/test-system.php            ${YELLOW}# Run all tests${NC}"
echo -e "  tail -f storage/logs/telegram.log       ${YELLOW}# View telegram logs${NC}"
echo -e "  tail -f storage/logs/orders.log         ${YELLOW}# View order logs${NC}"
echo ""
echo -e "${GREEN}Documentation:${NC}"
echo -e "  See TELEGRAM_BOT_DEPLOYMENT.md for complete guide"
echo ""
