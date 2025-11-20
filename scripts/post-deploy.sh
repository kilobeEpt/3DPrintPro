#!/usr/bin/env bash
# ========================================
# Post-Deployment Operations Script
# ========================================
# Handles release directory setup, shared directory linking, and rollback preparation
#
# Usage:
#   bash scripts/post-deploy.sh [options]
#
# Options:
#   --release-dir DIR    Release directory name (default: auto-detect or create new)
#   --deploy-path PATH   Base deployment path (default: /var/www/3dprint-omsk.ru)
#   --setup-shared       Setup shared directories (uploads, backups)
#   --list-releases      List available releases
#   --cleanup            Remove old releases (keep last 5)
#   --help               Show this help message
#
# Examples:
#   # Setup shared directories (run once on new server)
#   bash scripts/post-deploy.sh --setup-shared --deploy-path /var/www/3dprint-omsk.ru
#
#   # Link current release to shared directories
#   bash scripts/post-deploy.sh --release-dir release_20240120_143022
#
#   # Cleanup old releases
#   bash scripts/post-deploy.sh --cleanup --deploy-path /var/www/3dprint-omsk.ru
#
#   # List available releases
#   bash scripts/post-deploy.sh --list-releases --deploy-path /var/www/3dprint-omsk.ru
# ========================================

set -euo pipefail

# ========================================
# Configuration
# ========================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

# Default values
DEPLOY_PATH="${DEPLOY_PATH:-/var/www/3dprint-omsk.ru}"
RELEASE_DIR=""
SETUP_SHARED=false
LIST_RELEASES=false
CLEANUP=false
KEEP_RELEASES=5

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# ========================================
# Functions
# ========================================

log() {
    local level="$1"
    shift
    local message="$*"
    
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
    esac
}

show_help() {
    cat << EOF
Post-Deployment Operations Script

USAGE:
    bash scripts/post-deploy.sh [options]

OPTIONS:
    --release-dir DIR    Release directory name (e.g., release_20240120_143022)
    --deploy-path PATH   Base deployment path (default: /var/www/3dprint-omsk.ru)
    --setup-shared       Setup shared directories (run once on new server)
    --list-releases      List available releases
    --cleanup            Remove old releases (keep last ${KEEP_RELEASES})
    --help               Show this help message

EXAMPLES:
    # Setup shared directories (first-time server setup)
    bash scripts/post-deploy.sh --setup-shared --deploy-path /var/www/3dprint-omsk.ru

    # Link release to shared directories
    bash scripts/post-deploy.sh --release-dir release_20240120_143022

    # Cleanup old releases
    bash scripts/post-deploy.sh --cleanup

    # List all releases
    bash scripts/post-deploy.sh --list-releases

DOCUMENTATION:
    See docs/CI_CD.md for complete CI/CD documentation
EOF
}

setup_shared_directories() {
    log INFO "Setting up shared directories at ${DEPLOY_PATH}..."
    
    # Create base structure
    mkdir -p "${DEPLOY_PATH}"/{releases,shared,shared/uploads,shared/backups}
    
    # Create upload subdirectories
    mkdir -p "${DEPLOY_PATH}/shared/uploads"/{portfolio,testimonials}
    
    # Set permissions
    chmod 755 "${DEPLOY_PATH}"
    chmod 755 "${DEPLOY_PATH}/releases"
    chmod 755 "${DEPLOY_PATH}/shared"
    chmod 755 "${DEPLOY_PATH}/shared/uploads"
    chmod 755 "${DEPLOY_PATH}/shared/uploads/portfolio"
    chmod 755 "${DEPLOY_PATH}/shared/uploads/testimonials"
    chmod 755 "${DEPLOY_PATH}/shared/backups"
    
    # Create .gitkeep files
    touch "${DEPLOY_PATH}/shared/uploads/.gitkeep"
    touch "${DEPLOY_PATH}/shared/backups/.gitkeep"
    
    log SUCCESS "Shared directories created:"
    log INFO "  ${DEPLOY_PATH}/releases/       - Release directories"
    log INFO "  ${DEPLOY_PATH}/shared/uploads/ - Shared uploads (persistent)"
    log INFO "  ${DEPLOY_PATH}/shared/backups/ - Shared backups (persistent)"
    log INFO "  ${DEPLOY_PATH}/current         - Symlink to active release (will be created on first deploy)"
    
    log WARN "Note: Copy .env file to ${DEPLOY_PATH}/shared/.env for centralized configuration (optional)"
}

list_releases() {
    log INFO "Available releases in ${DEPLOY_PATH}/releases:"
    
    if [[ ! -d "${DEPLOY_PATH}/releases" ]]; then
        log ERROR "Releases directory not found: ${DEPLOY_PATH}/releases"
        return 1
    fi
    
    # Check if there are any releases
    if [[ -z "$(ls -A "${DEPLOY_PATH}/releases" 2>/dev/null)" ]]; then
        log WARN "No releases found"
        return 0
    fi
    
    # Show current release
    if [[ -L "${DEPLOY_PATH}/current" ]]; then
        local current=$(readlink "${DEPLOY_PATH}/current" | xargs basename)
        log INFO "Current release: ${GREEN}${current}${NC}"
        echo ""
    fi
    
    # List all releases with details
    echo "Release Directory          | Size    | Modified"
    echo "-------------------------------------------------------------------"
    
    cd "${DEPLOY_PATH}/releases"
    for release in $(ls -1t); do
        if [[ -d "$release" ]]; then
            local size=$(du -sh "$release" 2>/dev/null | cut -f1)
            local modified=$(stat -c %y "$release" 2>/dev/null | cut -d'.' -f1)
            
            # Highlight current release
            if [[ -L "${DEPLOY_PATH}/current" ]] && [[ "$(readlink "${DEPLOY_PATH}/current" | xargs basename)" == "$release" ]]; then
                echo -e "${GREEN}${release}${NC} | ${size} | ${modified} ${GREEN}← CURRENT${NC}"
            else
                echo "${release} | ${size} | ${modified}"
            fi
        fi
    done
    
    echo ""
    local count=$(ls -1 "${DEPLOY_PATH}/releases" 2>/dev/null | wc -l)
    log INFO "Total releases: ${count}"
}

cleanup_old_releases() {
    log INFO "Cleaning up old releases (keeping last ${KEEP_RELEASES})..."
    
    if [[ ! -d "${DEPLOY_PATH}/releases" ]]; then
        log ERROR "Releases directory not found: ${DEPLOY_PATH}/releases"
        return 1
    fi
    
    cd "${DEPLOY_PATH}/releases"
    
    local total=$(ls -1t 2>/dev/null | wc -l)
    
    if [[ $total -le $KEEP_RELEASES ]]; then
        log INFO "Only ${total} releases found (keeping all)"
        return 0
    fi
    
    # Get current release to protect it
    local current=""
    if [[ -L "${DEPLOY_PATH}/current" ]]; then
        current=$(readlink "${DEPLOY_PATH}/current" | xargs basename)
    fi
    
    log INFO "Current release: ${current}"
    
    # Get releases to delete (older than last N)
    local releases_to_delete=$(ls -1t | tail -n +$((KEEP_RELEASES + 1)))
    
    if [[ -z "$releases_to_delete" ]]; then
        log INFO "No releases to delete"
        return 0
    fi
    
    log WARN "Releases to be deleted:"
    echo "$releases_to_delete" | while read -r release; do
        # Don't delete current release (safety check)
        if [[ "$release" == "$current" ]]; then
            log WARN "  Skipping current release: ${release}"
        else
            log INFO "  - ${release}"
        fi
    done
    
    echo ""
    read -p "Continue with deletion? (y/N) " -n 1 -r
    echo
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "$releases_to_delete" | while read -r release; do
            if [[ "$release" != "$current" ]]; then
                rm -rf "$release"
                log SUCCESS "Deleted: ${release}"
            fi
        done
        
        log SUCCESS "Cleanup completed"
    else
        log INFO "Cleanup cancelled"
    fi
}

link_shared_directories() {
    if [[ -z "$RELEASE_DIR" ]]; then
        log ERROR "Release directory not specified. Use --release-dir option."
        return 1
    fi
    
    local release_path="${DEPLOY_PATH}/releases/${RELEASE_DIR}"
    
    if [[ ! -d "$release_path" ]]; then
        log ERROR "Release directory not found: ${release_path}"
        return 1
    fi
    
    log INFO "Linking shared directories for release: ${RELEASE_DIR}"
    
    # Remove existing upload/backup directories if they exist
    if [[ -d "${release_path}/storage/uploads" ]] && [[ ! -L "${release_path}/storage/uploads" ]]; then
        log WARN "Removing existing uploads directory"
        rm -rf "${release_path}/storage/uploads"
    fi
    
    if [[ -d "${release_path}/storage/backups" ]] && [[ ! -L "${release_path}/storage/backups" ]]; then
        log WARN "Removing existing backups directory"
        rm -rf "${release_path}/storage/backups"
    fi
    
    # Create symlinks to shared directories
    ln -sfn "${DEPLOY_PATH}/shared/uploads" "${release_path}/storage/uploads"
    log SUCCESS "Linked: storage/uploads → shared/uploads"
    
    ln -sfn "${DEPLOY_PATH}/shared/backups" "${release_path}/storage/backups"
    log SUCCESS "Linked: storage/backups → shared/backups"
    
    # Copy .env from shared if exists
    if [[ -f "${DEPLOY_PATH}/shared/.env" ]] && [[ ! -f "${release_path}/.env" ]]; then
        cp "${DEPLOY_PATH}/shared/.env" "${release_path}/.env"
        chmod 600 "${release_path}/.env"
        log SUCCESS "Copied: shared/.env → release/.env"
    fi
    
    # Verify symlinks
    log INFO "Verifying symlinks..."
    ls -la "${release_path}/storage/" | grep -E "(uploads|backups)"
    
    log SUCCESS "Shared directories linked successfully"
}

# ========================================
# Main Script
# ========================================

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --release-dir)
            RELEASE_DIR="$2"
            shift 2
            ;;
        --deploy-path)
            DEPLOY_PATH="$2"
            shift 2
            ;;
        --setup-shared)
            SETUP_SHARED=true
            shift
            ;;
        --list-releases)
            LIST_RELEASES=true
            shift
            ;;
        --cleanup)
            CLEANUP=true
            shift
            ;;
        --help)
            show_help
            exit 0
            ;;
        *)
            log ERROR "Unknown option: $1"
            echo ""
            show_help
            exit 1
            ;;
    esac
done

# Execute requested operations
if [[ "$SETUP_SHARED" = true ]]; then
    setup_shared_directories
    exit 0
fi

if [[ "$LIST_RELEASES" = true ]]; then
    list_releases
    exit 0
fi

if [[ "$CLEANUP" = true ]]; then
    cleanup_old_releases
    exit 0
fi

# Default: link shared directories
if [[ -n "$RELEASE_DIR" ]]; then
    link_shared_directories
    exit 0
fi

# No operation specified
log ERROR "No operation specified"
echo ""
show_help
exit 1
