#!/bin/bash
#
# Hosting Audit Script Test
#
# Validates that the hosting-audit.php script is properly structured
# and executable. Since PHP may not be available in all environments,
# this performs basic validation checks.
#

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
AUDIT_SCRIPT="$SCRIPT_DIR/hosting-audit.php"

echo "================================"
echo "Hosting Audit Script Test"
echo "================================"
echo ""

# Check if script exists
if [ ! -f "$AUDIT_SCRIPT" ]; then
    echo "❌ FAIL: Script not found at $AUDIT_SCRIPT"
    exit 1
fi
echo "✓ Script file exists"

# Check if script is executable
if [ ! -x "$AUDIT_SCRIPT" ]; then
    echo "⚠ WARN: Script is not executable (chmod +x may be needed)"
else
    echo "✓ Script is executable"
fi

# Check shebang
if head -1 "$AUDIT_SCRIPT" | grep -q "#!/usr/bin/env php"; then
    echo "✓ Shebang is correct"
else
    echo "❌ FAIL: Invalid shebang"
    exit 1
fi

# Check for required functions
required_functions=(
    "checkPhpVersion"
    "checkPhpExtensions"
    "checkCliTools"
    "checkServices"
    "checkResources"
    "checkPermissions"
    "checkWriteAccess"
    "outputJson"
    "outputHuman"
)

for func in "${required_functions[@]}"; do
    if grep -q "function $func" "$AUDIT_SCRIPT"; then
        echo "✓ Function $func defined"
    else
        echo "❌ FAIL: Function $func not found"
        exit 1
    fi
done

# Check for command-line options parsing
if grep -q "parseArguments" "$AUDIT_SCRIPT"; then
    echo "✓ Command-line argument parsing implemented"
else
    echo "❌ FAIL: Argument parsing not found"
    exit 1
fi

# Check for help output
if grep -q "showHelp" "$AUDIT_SCRIPT"; then
    echo "✓ Help function defined"
else
    echo "❌ FAIL: Help function not found"
    exit 1
fi

# Validate PHP syntax (if PHP is available)
if command -v php >/dev/null 2>&1; then
    echo ""
    echo "PHP is available. Running syntax check..."
    if php -l "$AUDIT_SCRIPT" >/dev/null 2>&1; then
        echo "✓ PHP syntax is valid"
    else
        echo "❌ FAIL: PHP syntax errors detected"
        php -l "$AUDIT_SCRIPT"
        exit 1
    fi
    
    # Try to run help
    echo ""
    echo "Testing --help flag..."
    if php "$AUDIT_SCRIPT" --help >/dev/null 2>&1; then
        echo "✓ --help flag works"
    else
        echo "❌ FAIL: --help flag failed"
        exit 1
    fi
else
    echo ""
    echo "⚠ WARN: PHP not available, skipping syntax validation"
    echo "  (This is expected in environments without PHP)"
fi

# Count lines of code
lines=$(wc -l < "$AUDIT_SCRIPT")
echo ""
echo "Script statistics:"
echo "  - Total lines: $lines"
echo "  - Size: $(du -h "$AUDIT_SCRIPT" | cut -f1)"

echo ""
echo "================================"
echo "✅ All structural tests passed"
echo "================================"
echo ""
echo "To run the actual audit (requires PHP):"
echo "  php $AUDIT_SCRIPT"
echo ""

exit 0
