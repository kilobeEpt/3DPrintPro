#!/bin/bash

echo "================================"
echo "Theme Toggle Verification Script"
echo "================================"
echo ""

# Check CSS selectors
echo "1. Checking CSS selectors..."
css_count=$(grep -c 'data-theme="dark"' css/style.css)
if [ "$css_count" -eq 7 ]; then
    echo "   ✅ CSS selectors: $css_count instances found (expected 7)"
else
    echo "   ❌ CSS selectors: $css_count instances found (expected 7)"
fi

# Check JavaScript
echo ""
echo "2. Checking JavaScript..."
if grep -q "systemPreference = window.matchMedia" js/main.js; then
    echo "   ✅ System preference detection: Found"
else
    echo "   ❌ System preference detection: Missing"
fi

if grep -q "document.documentElement.setAttribute('data-theme'" js/main.js; then
    echo "   ✅ HTML element theme application: Found"
else
    echo "   ❌ HTML element theme application: Missing"
fi

# Check FOUC prevention
echo ""
echo "3. Checking FOUC prevention..."
if grep -q "Theme initialization - Prevents FOUC" includes/head.php; then
    echo "   ✅ FOUC prevention script: Found in head.php"
else
    echo "   ❌ FOUC prevention script: Missing from head.php"
fi

# Check for old selectors
echo ""
echo "4. Checking for old selectors..."
old_count=$(grep -c 'body\.dark-theme' css/style.css 2>/dev/null || echo "0")
if [ "$old_count" -eq 0 ]; then
    echo "   ✅ Old selectors: None found (good!)"
else
    echo "   ❌ Old selectors: $old_count instances still present"
fi

# Check theme toggle button
echo ""
echo "5. Checking theme toggle button..."
if grep -q 'id="themeToggle"' includes/header.php; then
    echo "   ✅ Theme toggle button: Found in header"
else
    echo "   ❌ Theme toggle button: Missing from header"
fi

# Check test files
echo ""
echo "6. Checking test files..."
if [ -f "test-theme-toggle.html" ]; then
    echo "   ✅ Test page: test-theme-toggle.html exists"
else
    echo "   ❌ Test page: test-theme-toggle.html missing"
fi

if [ -f "THEME_TOGGLE_FIX.md" ]; then
    echo "   ✅ Documentation: THEME_TOGGLE_FIX.md exists"
else
    echo "   ❌ Documentation: THEME_TOGGLE_FIX.md missing"
fi

echo ""
echo "================================"
echo "Verification complete!"
echo "================================"
