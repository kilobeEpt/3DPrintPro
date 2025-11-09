/**
 * Layout Validation Script
 * 
 * This script checks for common layout issues that could cause
 * horizontal scrolling or element stretching.
 * 
 * Run in browser console on any page.
 */

(function validateLayout() {
    console.log('🔍 Starting Layout Validation...\n');
    
    const issues = [];
    const warnings = [];
    
    // 1. Check viewport width
    const viewportWidth = document.documentElement.clientWidth;
    console.log(`✓ Viewport Width: ${viewportWidth}px`);
    
    // 2. Check for horizontal overflow
    const bodyScrollWidth = document.body.scrollWidth;
    const bodyClientWidth = document.body.clientWidth;
    
    if (bodyScrollWidth > bodyClientWidth) {
        issues.push(`❌ Horizontal overflow detected: body scrollWidth (${bodyScrollWidth}px) > clientWidth (${bodyClientWidth}px)`);
    } else {
        console.log(`✓ No horizontal overflow detected`);
    }
    
    // 3. Check if layout-fix.css is loaded
    const stylesheets = Array.from(document.styleSheets);
    const layoutFixLoaded = stylesheets.some(sheet => 
        sheet.href && sheet.href.includes('layout-fix.css')
    );
    
    if (layoutFixLoaded) {
        console.log('✓ layout-fix.css is loaded');
    } else {
        issues.push('❌ layout-fix.css is NOT loaded');
    }
    
    // 4. Check container max-width
    const containers = document.querySelectorAll('.container');
    console.log(`\n📦 Found ${containers.length} containers:`);
    
    containers.forEach((container, index) => {
        const styles = window.getComputedStyle(container);
        const maxWidth = styles.maxWidth;
        const width = container.offsetWidth;
        
        console.log(`   Container ${index + 1}: width=${width}px, max-width=${maxWidth}`);
        
        if (maxWidth === 'none') {
            warnings.push(`⚠️  Container ${index + 1} has no max-width constraint`);
        }
        
        if (width > viewportWidth) {
            issues.push(`❌ Container ${index + 1} exceeds viewport width: ${width}px > ${viewportWidth}px`);
        }
    });
    
    // 5. Check major sections
    const sections = document.querySelectorAll('section');
    console.log(`\n📋 Checking ${sections.length} sections:`);
    
    let overflowingSections = 0;
    sections.forEach((section, index) => {
        const width = section.offsetWidth;
        if (width > viewportWidth) {
            overflowingSections++;
            issues.push(`❌ Section ${index + 1} (${section.className}) exceeds viewport: ${width}px > ${viewportWidth}px`);
        }
    });
    
    if (overflowingSections === 0) {
        console.log('✓ No sections exceeding viewport width');
    }
    
    // 6. Check for elements wider than viewport
    console.log('\n🔍 Scanning for wide elements...');
    const allElements = document.querySelectorAll('*');
    const wideElements = [];
    
    allElements.forEach(el => {
        const rect = el.getBoundingClientRect();
        if (rect.width > viewportWidth + 10) { // 10px tolerance for rounding
            wideElements.push({
                tag: el.tagName.toLowerCase(),
                class: el.className,
                width: Math.round(rect.width)
            });
        }
    });
    
    if (wideElements.length > 0) {
        console.log(`⚠️  Found ${wideElements.length} elements wider than viewport:`);
        wideElements.slice(0, 5).forEach(el => {
            console.log(`   - ${el.tag}.${el.class}: ${el.width}px`);
        });
        if (wideElements.length > 5) {
            console.log(`   ... and ${wideElements.length - 5} more`);
        }
    } else {
        console.log('✓ No elements exceed viewport width');
    }
    
    // 7. Check grid max-widths
    const grids = [
        '.hero-content',
        '.stats-grid',
        '.services-grid',
        '.portfolio-grid',
        '.why-us-grid',
        '.blog-grid',
        '.calculator-wrapper',
        '.footer-content'
    ];
    
    console.log('\n🎯 Checking grid constraints:');
    grids.forEach(selector => {
        const element = document.querySelector(selector);
        if (element) {
            const styles = window.getComputedStyle(element);
            const maxWidth = styles.maxWidth;
            const width = element.offsetWidth;
            
            if (maxWidth === 'none') {
                warnings.push(`⚠️  ${selector} has no max-width constraint`);
            } else {
                console.log(`   ✓ ${selector}: max-width=${maxWidth}, actual=${width}px`);
            }
        }
    });
    
    // 8. Check images
    const images = document.querySelectorAll('img');
    console.log(`\n🖼️  Checking ${images.length} images:`);
    
    let oversizedImages = 0;
    images.forEach(img => {
        if (img.naturalWidth > 0 && img.offsetWidth > viewportWidth) {
            oversizedImages++;
        }
    });
    
    if (oversizedImages === 0) {
        console.log('✓ All images properly constrained');
    } else {
        warnings.push(`⚠️  ${oversizedImages} images may be too wide`);
    }
    
    // 9. Check for fixed width elements
    console.log('\n📏 Checking for fixed-width elements...');
    const fixedWidthElements = [];
    
    allElements.forEach(el => {
        const styles = window.getComputedStyle(el);
        const width = styles.width;
        
        // Check for pixel-based widths greater than viewport
        if (width.endsWith('px')) {
            const widthValue = parseInt(width);
            if (widthValue > viewportWidth) {
                fixedWidthElements.push({
                    tag: el.tagName.toLowerCase(),
                    class: el.className,
                    width: width
                });
            }
        }
    });
    
    if (fixedWidthElements.length > 0) {
        warnings.push(`⚠️  Found ${fixedWidthElements.length} elements with fixed width > viewport`);
    } else {
        console.log('✓ No problematic fixed-width elements');
    }
    
    // 10. Final Report
    console.log('\n' + '='.repeat(50));
    console.log('📊 VALIDATION REPORT');
    console.log('='.repeat(50));
    
    if (issues.length === 0 && warnings.length === 0) {
        console.log('\n✅ ALL CHECKS PASSED!');
        console.log('   Layout is properly constrained.');
        console.log('   No horizontal scrolling detected.');
        console.log('   All elements within viewport bounds.');
    } else {
        if (issues.length > 0) {
            console.log('\n❌ ISSUES FOUND:');
            issues.forEach(issue => console.log(issue));
        }
        
        if (warnings.length > 0) {
            console.log('\n⚠️  WARNINGS:');
            warnings.forEach(warning => console.log(warning));
        }
    }
    
    console.log('\n' + '='.repeat(50));
    
    // Return summary object
    return {
        passed: issues.length === 0,
        issues,
        warnings,
        viewport: { width: viewportWidth, height: window.innerHeight },
        containers: containers.length,
        sections: sections.length
    };
})();
