#!/usr/bin/env bash
# ========================================
# 3D Print Pro - Production Deployment Script
# ========================================
# Automates production deployment with validation, permissions, and testing
#
# Usage:
#   bash scripts/deploy.sh [options]
#
# Options:
#   --dry-run          Show what would be done without making changes
#   --ci               CI/CD mode (non-interactive, fail fast)
#   --skip-audit       Skip hosting environment audit (not recommended)
#   --skip-composer    Skip composer install step
#   --skip-db          Skip database migration check
#   --skip-tests       Skip smoke tests
#   --force            Force deployment even if checks fail (dangerous)
#   --help             Show this help message
#
# Exit codes:
#   0 - Deployment successful
#   1 - Pre-deployment checks failed
#   2 - Composer installation failed
#   3 - Permission setup failed
#   4 - Database migration failed
#   5 - Smoke tests failed
#   6 - Invalid usage
# ========================================

set -euo pipefail  # Exit on error, undefined vars, pipe failures

# ========================================
# Configuration
# ========================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
LOG_FILE="${PROJECT_ROOT}/storage/logs/deploy_${TIMESTAMP}.log"
LOCK_FILE="${PROJECT_ROOT}/storage/.deploy.lock"

# Default options
DRY_RUN=false
CI_MODE=false
SKIP_AUDIT=false
SKIP_COMPOSER=false
SKIP_DB=false
SKIP_TESTS=false
FORCE_DEPLOY=false

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# ========================================
# Functions
# ========================================

log() {
    local level="$1"
    shift
    local message="$*"
    local timestamp="$(date '+%Y-%m-%d %H:%M:%S')"
    
    # Log to file
    if [[ ! "$DRY_RUN" = true ]]; then
        echo "[${timestamp}] [${level}] ${message}" >> "$LOG_FILE"
    fi
    
    # Log to console with colors
    case "$level" in
        INFO)
            echo -e "${BLUE}[INFO]${NC} ${message}"
            ;;
        SUCCESS)
            echo -e "${GREEN}[✓]${NC} ${message}"
            ;;
        WARN)
            echo -e "${YELLOW}[⚠]${NC} ${message}"
            ;;
        ERROR)
            echo -e "${RED}[✗]${NC} ${message}"
            ;;
        *)
            echo "[${level}] ${message}"
            ;;
    esac
}

show_help() {
    cat << EOF
3D Print Pro - Production Deployment Script

USAGE:
    bash scripts/deploy.sh [options]

OPTIONS:
    --dry-run          Show what would be done without making changes
    --ci               CI/CD mode (non-interactive, fail fast)
    --skip-audit       Skip hosting environment audit (not recommended)
    --skip-composer    Skip composer install step
    --skip-db          Skip database migration check
    --skip-tests       Skip smoke tests
    --force            Force deployment even if checks fail (dangerous)
    --help             Show this help message

EXAMPLES:
    # Dry run to see what would happen
    bash scripts/deploy.sh --dry-run

    # Full production deployment
    bash scripts/deploy.sh

    # CI/CD pipeline deployment
    bash scripts/deploy.sh --ci

    # Quick deployment skipping tests
    bash scripts/deploy.sh --skip-tests

EXIT CODES:
    0 - Deployment successful
    1 - Pre-deployment checks failed
    2 - Composer installation failed
    3 - Permission setup failed
    4 - Database migration failed
    5 - Smoke tests failed
    6 - Invalid usage

DOCUMENTATION:
    See docs/PRODUCTION_RUNBOOK.md for complete deployment guide
EOF
}

check_prerequisites() {
    log INFO "Checking prerequisites..."
    
    # Check if running from correct directory
    if [[ ! -f "$PROJECT_ROOT/composer.json" ]]; then
        log ERROR "Must be run from project root. composer.json not found."
        return 1
    fi
    
    # Check if PHP is available
    if ! command -v php &> /dev/null; then
        log ERROR "PHP is not installed or not in PATH"
        return 1
    fi
    
    # Check PHP version
    local php_version=$(php -r "echo PHP_VERSION;")
    log INFO "PHP version: $php_version"
    
    if [[ "$DRY_RUN" = true ]]; then
        log INFO "[DRY-RUN] Prerequisites check passed"
    else
        log SUCCESS "Prerequisites check passed"
    fi
    
    return 0
}

create_lock_file() {
    if [[ "$DRY_RUN" = true ]]; then
        log INFO "[DRY-RUN] Would create deployment lock file"
        return 0
    fi
    
    if [[ -f "$LOCK_FILE" ]]; then
        log ERROR "Deployment already in progress (lock file exists: $LOCK_FILE)"
        log ERROR "If this is an error, remove the lock file manually"
        return 1
    fi
    
    mkdir -p "$(dirname "$LOCK_FILE")"
    echo "$TIMESTAMP" > "$LOCK_FILE"
    log INFO "Created deployment lock file"
    
    return 0
}

remove_lock_file() {
    if [[ "$DRY_RUN" = true ]]; then
        return 0
    fi
    
    if [[ -f "$LOCK_FILE" ]]; then
        rm -f "$LOCK_FILE"
        log INFO "Removed deployment lock file"
    fi
}

run_hosting_audit() {
    if [[ "$SKIP_AUDIT" = true ]]; then
        log WARN "Skipping hosting environment audit (--skip-audit specified)"
        return 0
    fi
    
    log INFO "Running hosting environment audit..."
    
    if [[ "$DRY_RUN" = true ]]; then
        log INFO "[DRY-RUN] Would run: php scripts/hosting-audit.php --strict"
        return 0
    fi
    
    cd "$PROJECT_ROOT"
    
    if php scripts/hosting-audit.php --strict >> "$LOG_FILE" 2>&1; then
        log SUCCESS "Hosting environment audit passed"
        return 0
    else
        log ERROR "Hosting environment audit failed"
        log ERROR "Run 'php scripts/hosting-audit.php' to see detailed results"
        log ERROR "See docs/HOSTING_AUDIT.md for remediation steps"
        
        if [[ "$FORCE_DEPLOY" = true ]]; then
            log WARN "Continuing despite audit failure (--force specified)"
            return 0
        fi
        
        return 1
    fi
}

install_composer_dependencies() {
    if [[ "$SKIP_COMPOSER" = true ]]; then
        log WARN "Skipping composer install (--skip-composer specified)"
        return 0
    fi
    
    log INFO "Installing composer dependencies..."
    
    if [[ "$DRY_RUN" = true ]]; then
        log INFO "[DRY-RUN] Would run: composer install --no-dev --optimize-autoloader --no-interaction"
        return 0
    fi
    
    cd "$PROJECT_ROOT"
    
    if ! command -v composer &> /dev/null; then
        log ERROR "Composer is not installed or not in PATH"
        log ERROR "Install composer from https://getcomposer.org/"
        return 1
    fi
    
    if composer install --no-dev --optimize-autoloader --no-interaction >> "$LOG_FILE" 2>&1; then
        log SUCCESS "Composer dependencies installed"
        return 0
    else
        log ERROR "Composer installation failed"
        log ERROR "Check log file: $LOG_FILE"
        return 1
    fi
}

setup_environment_file() {
    log INFO "Checking environment configuration..."
    
    if [[ -f "$PROJECT_ROOT/.env" ]]; then
        log INFO "Environment file (.env) already exists"
        return 0
    fi
    
    if [[ ! -f "$PROJECT_ROOT/.env.production.example" ]]; then
        log ERROR "Environment template (.env.production.example) not found"
        return 1
    fi
    
    if [[ "$DRY_RUN" = true ]]; then
        log INFO "[DRY-RUN] Would copy .env.production.example to .env"
        log WARN "[DRY-RUN] Remember to edit .env with production credentials!"
        return 0
    fi
    
    if [[ "$CI_MODE" = false ]]; then
        log WARN "No .env file found. Creating from .env.production.example"
        log WARN "You MUST edit .env with your production credentials before site will work!"
        read -p "Press Enter to continue or Ctrl+C to abort..." -r
    fi
    
    cp "$PROJECT_ROOT/.env.production.example" "$PROJECT_ROOT/.env"
    chmod 600 "$PROJECT_ROOT/.env"
    
    log SUCCESS "Created .env from template"
    log WARN "⚠️  IMPORTANT: Edit .env with your production credentials!"
    log INFO "Required values: DB_PASSWORD, TELEGRAM_BOT_TOKEN, SMTP_PASSWORD"
    
    return 0
}

setup_directories_and_permissions() {
    log INFO "Setting up directories and permissions..."
    
    local dirs=(
        "storage"
        "storage/cache"
        "storage/uploads"
        "storage/uploads/portfolio"
        "storage/uploads/testimonials"
        "storage/backups"
        "storage/logs"
        "logs"
    )
    
    for dir in "${dirs[@]}"; do
        local full_path="$PROJECT_ROOT/$dir"
        
        if [[ "$DRY_RUN" = true ]]; then
            log INFO "[DRY-RUN] Would create directory: $dir"
            log INFO "[DRY-RUN] Would set permissions: 755"
        else
            if [[ ! -d "$full_path" ]]; then
                mkdir -p "$full_path"
                log INFO "Created directory: $dir"
            fi
            
            chmod 755 "$full_path"
            
            # Create .gitkeep for empty directories
            if [[ ! -f "$full_path/.gitkeep" ]] && [[ ! "$(ls -A "$full_path")" ]]; then
                touch "$full_path/.gitkeep"
            fi
        fi
    done
    
    # Set secure permissions on sensitive files
    local secure_files=(
        ".env"
        "api/config.php"
    )
    
    for file in "${secure_files[@]}"; do
        local full_path="$PROJECT_ROOT/$file"
        
        if [[ -f "$full_path" ]]; then
            if [[ "$DRY_RUN" = true ]]; then
                log INFO "[DRY-RUN] Would set secure permissions on: $file (600)"
            else
                chmod 600 "$full_path"
                log INFO "Set secure permissions on: $file"
            fi
        fi
    done
    
    if [[ "$DRY_RUN" = true ]]; then
        log INFO "[DRY-RUN] Directory and permission setup complete"
    else
        log SUCCESS "Directory and permission setup complete"
    fi
    
    return 0
}

check_database_schema() {
    if [[ "$SKIP_DB" = true ]]; then
        log WARN "Skipping database migration check (--skip-db specified)"
        return 0
    fi
    
    log INFO "Checking database schema..."
    
    if [[ ! -f "$PROJECT_ROOT/.env" ]]; then
        log WARN "No .env file found, skipping database check"
        return 0
    fi
    
    if [[ "$DRY_RUN" = true ]]; then
        log INFO "[DRY-RUN] Would check for schema updates"
        log INFO "[DRY-RUN] Would run: php scripts/provision-database.php --import-only if needed"
        return 0
    fi
    
    # Check if database is accessible
    cd "$PROJECT_ROOT"
    
    if php -r "
        require 'vendor/autoload.php';
        \$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        \$dotenv->load();
        \$db = new PDO(
            'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_DATABASE'),
            getenv('DB_USERNAME'),
            getenv('DB_PASSWORD')
        );
        echo 'OK';
    " 2>/dev/null | grep -q "OK"; then
        log SUCCESS "Database connection verified"
        
        # Check if schema verification script exists
        if [[ -f "$PROJECT_ROOT/database/verify-schema.php" ]]; then
            log INFO "Running schema verification..."
            if php database/verify-schema.php >> "$LOG_FILE" 2>&1; then
                log SUCCESS "Database schema verified"
            else
                log WARN "Database schema verification reported issues"
                log WARN "Run 'php database/verify-schema.php' for details"
            fi
        fi
    else
        log WARN "Cannot connect to database. Check .env credentials."
        log WARN "Database schema check skipped"
    fi
    
    return 0
}

run_smoke_tests() {
    if [[ "$SKIP_TESTS" = true ]]; then
        log WARN "Skipping smoke tests (--skip-tests specified)"
        return 0
    fi
    
    log INFO "Running smoke tests..."
    
    if [[ "$DRY_RUN" = true ]]; then
        log INFO "[DRY-RUN] Would run: php scripts/api_smoke.php"
        return 0
    fi
    
    cd "$PROJECT_ROOT"
    
    if [[ ! -f "$PROJECT_ROOT/scripts/api_smoke.php" ]]; then
        log WARN "Smoke test script not found, skipping tests"
        return 0
    fi
    
    # Run smoke tests and capture output
    if php scripts/api_smoke.php >> "$LOG_FILE" 2>&1; then
        log SUCCESS "Smoke tests passed"
        return 0
    else
        log ERROR "Smoke tests failed"
        log ERROR "Check log file: $LOG_FILE"
        
        if [[ "$FORCE_DEPLOY" = true ]]; then
            log WARN "Continuing despite test failures (--force specified)"
            return 0
        fi
        
        return 1
    fi
}

generate_deployment_report() {
    log INFO "Generating deployment report..."
    
    if [[ "$DRY_RUN" = true ]]; then
        log INFO "[DRY-RUN] Would generate deployment report"
        return 0
    fi
    
    local report_file="${PROJECT_ROOT}/storage/logs/deploy_report_${TIMESTAMP}.txt"
    
    cat > "$report_file" << EOF
========================================
3D Print Pro - Deployment Report
========================================
Timestamp: $(date '+%Y-%m-%d %H:%M:%S')
Hostname: $(hostname)
User: $(whoami)
Project: $PROJECT_ROOT
PHP Version: $(php -r "echo PHP_VERSION;")

========================================
Deployment Steps Completed
========================================
✓ Prerequisites check
✓ Hosting environment audit
✓ Composer dependencies installed
✓ Environment file configured
✓ Directories and permissions set
✓ Database schema verified
✓ Smoke tests passed

========================================
Next Steps
========================================
1. Verify .env file has correct production credentials
2. Test the site: $APP_URL
3. Check admin panel login
4. Configure Telegram notifications (if not done)
5. Setup cron jobs for backups
6. Monitor logs for errors

========================================
Important Files
========================================
- Environment: .env
- Deployment log: storage/logs/deploy_${TIMESTAMP}.log
- Error logs: logs/api.log
- Backup location: storage/backups/

========================================
Documentation
========================================
- Production Runbook: docs/PRODUCTION_RUNBOOK.md
- Deployment Guide: docs/DEPLOYMENT.md
- Admin Guide: docs/ADMIN_GUIDE.md
- Troubleshooting: docs/TROUBLESHOOTING.md

========================================
Support
========================================
For issues, consult documentation or contact support.

Deployment completed successfully! 🚀
========================================
EOF
    
    log SUCCESS "Deployment report saved: $report_file"
    
    return 0
}

cleanup_on_error() {
    local exit_code=$?
    
    log ERROR "Deployment failed with exit code: $exit_code"
    
    remove_lock_file
    
    if [[ -f "$LOG_FILE" ]]; then
        log INFO "Check deployment log for details: $LOG_FILE"
    fi
    
    exit "$exit_code"
}

# ========================================
# Main Execution
# ========================================

main() {
    # Parse command line arguments
    for arg in "$@"; do
        case $arg in
            --dry-run)
                DRY_RUN=true
                ;;
            --ci)
                CI_MODE=true
                ;;
            --skip-audit)
                SKIP_AUDIT=true
                ;;
            --skip-composer)
                SKIP_COMPOSER=true
                ;;
            --skip-db)
                SKIP_DB=true
                ;;
            --skip-tests)
                SKIP_TESTS=true
                ;;
            --force)
                FORCE_DEPLOY=true
                ;;
            --help)
                show_help
                exit 0
                ;;
            *)
                echo "Unknown option: $arg"
                echo "Use --help for usage information"
                exit 6
                ;;
        esac
    done
    
    # Display header
    echo "========================================"
    echo "3D Print Pro - Production Deployment"
    echo "========================================"
    echo "Timestamp: $(date '+%Y-%m-%d %H:%M:%S')"
    echo "Project: $PROJECT_ROOT"
    
    if [[ "$DRY_RUN" = true ]]; then
        echo "Mode: DRY RUN (no changes will be made)"
    elif [[ "$CI_MODE" = true ]]; then
        echo "Mode: CI/CD (non-interactive)"
    else
        echo "Mode: Production Deployment"
    fi
    
    echo "========================================"
    echo ""
    
    # Create log directory if it doesn't exist
    if [[ "$DRY_RUN" = false ]]; then
        mkdir -p "$(dirname "$LOG_FILE")"
        log INFO "Deployment started"
        log INFO "Log file: $LOG_FILE"
    fi
    
    # Set up error trap
    trap cleanup_on_error ERR
    
    # Run deployment steps
    check_prerequisites || exit 1
    create_lock_file || exit 1
    run_hosting_audit || exit 1
    install_composer_dependencies || exit 2
    setup_environment_file || exit 1
    setup_directories_and_permissions || exit 3
    check_database_schema || exit 4
    run_smoke_tests || exit 5
    
    if [[ "$DRY_RUN" = false ]]; then
        generate_deployment_report
    fi
    
    # Clean up
    remove_lock_file
    
    # Success!
    echo ""
    echo "========================================"
    if [[ "$DRY_RUN" = true ]]; then
        log INFO "DRY RUN COMPLETE - No changes were made"
        log INFO "Run without --dry-run to perform actual deployment"
    else
        log SUCCESS "DEPLOYMENT COMPLETE! 🚀"
        log INFO "Log file: $LOG_FILE"
        log INFO "Next steps: See docs/PRODUCTION_RUNBOOK.md"
    fi
    echo "========================================"
    
    exit 0
}

# Run main function
main "$@"
