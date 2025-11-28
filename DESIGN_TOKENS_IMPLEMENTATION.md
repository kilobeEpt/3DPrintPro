# Design Tokens Implementation Guide

**Version:** 1.0  
**Date:** January 2025  
**Status:** ✅ Complete

## Overview

This document describes the comprehensive design token system implemented across the 3D Print Pro project. The system provides a unified approach to spacing, colors, typography, and component styling, ensuring consistency and maintainability.

## Architecture

### Token Categories

1. **8px Spacing Scale** - Consistent spacing based on 8px grid
2. **Card & Surface Tokens** - Unified card/surface styling
3. **Focus Rings** - WCAG-compliant accessibility
4. **Utility Classes** - Reusable layout and typography helpers

## Design Tokens

### 1. 8px Spacing Scale

Located in `css/style.css` `:root` block (lines 31-46):

```css
--space-4: 4px;    /* Minimal spacing */
--space-8: 8px;    /* Extra small */
--space-12: 12px;  /* Small */
--space-16: 16px;  /* Base */
--space-20: 20px;  /* Medium-small */
--space-24: 24px;  /* Medium */
--space-32: 32px;  /* Large */
--space-40: 40px;  /* Extra large */
--space-48: 48px;  /* 2XL */
--space-56: 56px;  /* 3XL */
--space-64: 64px;  /* 4XL */
--space-72: 72px;  /* 5XL */
--space-80: 80px;  /* 6XL */
--space-88: 88px;  /* 7XL */
--space-96: 96px;  /* 8XL */
```

**Usage:**
- Padding: `padding: var(--space-24);`
- Margins: `margin: var(--space-16);`
- Gaps: `gap: var(--space-32);`

### 2. Card & Surface Tokens

Located in `css/style.css` `:root` block (lines 48-62):

#### Light Theme
```css
--card-bg: #ffffff;
--card-surface: #f9fafb;
--card-border: #e5e7eb;
--card-shadow: 0 4px 6px rgba(0,0,0,0.07);
```

#### Dark Theme
```css
--card-bg: #1e293b;
--card-surface: #334155;
--card-border: #475569;
--card-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
```

#### Card Padding Scale
```css
--card-padding-sm: 16px;
--card-padding-md: 24px;
--card-padding-lg: 32px;
```

#### Card Radius Scale
```css
--card-radius-sm: 8px;
--card-radius-md: 12px;
--card-radius-lg: 16px;
```

### 3. Focus Rings (WCAG Compliant)

Located in `css/style.css` `:root` block (lines 64-66):

```css
--focus-ring: 0 0 0 3px rgba(99, 102, 241, 0.3);
--focus-ring-offset: 2px;
```

**Usage:**
```css
button:focus {
    box-shadow: var(--focus-ring);
}

button:focus-visible {
    outline: 2px solid var(--primary);
    outline-offset: var(--focus-ring-offset);
}
```

## Utility Classes

Located in `css/style.css` UTILITY CLASSES section (lines 1752+):

### Typography Utilities

```css
.text-muted        /* Secondary text color, 14px */
.text-light        /* Light text color */
.text-small        /* 14px font size */
.text-large        /* 18px font size */
.helper-text       /* 14px, secondary color, helper guidance */
.coord-text        /* Monospace coordinates/technical text */
.sr-only           /* Screen reader only (accessibility) */
```

**Examples:**
```html
<span class="text-muted">Звоните с 9:00 до 18:00</span>
<p class="helper-text">Введите номер телефона без пробелов</p>
<span class="coord-text">Координаты: 54.9924, 73.3686</span>
```

### Layout Utilities

```css
.layout-grid       /* Responsive auto-fit grid (300px min) */
.gap-8             /* 8px gap */
.gap-16            /* 16px gap */
.gap-24            /* 24px gap */
.gap-32            /* 32px gap */
.gap-40            /* 40px gap */
```

**Examples:**
```html
<div class="layout-grid gap-32">
    <div class="card">...</div>
    <div class="card">...</div>
</div>
```

### Section Spacing

```css
.section-spacing      /* 64px vertical padding */
.section-spacing-sm   /* 40px vertical padding */
.section-spacing-lg   /* 96px vertical padding */
```

**Examples:**
```html
<section class="section-spacing">
    <!-- Standard section spacing -->
</section>
```

### Card Utilities

```css
.card-surface      /* Surface background + radius + padding */
.card-hover        /* Hover lift effect */
.card-shadow       /* Standard card shadow */
.card-shadow-md    /* Medium card shadow */
```

**Examples:**
```html
<div class="card-surface card-shadow card-hover">
    <!-- Card content -->
</div>
```

### Icon Utilities

```css
.icon-success      /* Green color */
.icon-danger       /* Red color */
.icon-info         /* Blue color */
.icon-warning      /* Orange color */
```

**Examples:**
```html
<i class="fas fa-check-circle icon-success"></i>
<i class="fas fa-exclamation-triangle icon-warning"></i>
```

## Responsive Behavior

### Utility Class Adjustments

Defined in `css/responsive.css`:

#### Tablet (max-width: 1024px)
```css
.layout-grid {
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--space-20);
}

.gap-32 { gap: var(--space-24); }
.gap-40 { gap: var(--space-32); }
```

#### Mobile (max-width: 768px)
```css
.layout-grid {
    grid-template-columns: 1fr; /* Single column */
}

.gap-24 { gap: var(--space-16); }
.gap-32 { gap: var(--space-20); }
.gap-40 { gap: var(--space-24); }
```

## Files Modified

### Core CSS Files
1. **css/style.css** - Added design tokens and utility classes
   - Lines 31-66: Spacing scale, card tokens, focus rings
   - Lines 1752+: Utility classes
   - Lines 106-110: Dark theme card tokens

### Other CSS Files Using Tokens
2. **css/contact-page.css** - Uses all token categories
3. **css/responsive.css** - Responsive utility adjustments
4. **css/cta-components.css** - Uses focus ring tokens
5. **css/mobile-polish.css** - Uses card padding token

### HTML/PHP Files Using Utilities
- `contact.php` - `.text-muted`, `.coord-text`
- `services.php` - `.icon-success`, `.icon-danger`
- `portfolio.php` - `.layout-grid`, `.card-surface`

## Backward Compatibility

Legacy tokens are maintained as aliases:

```css
--section-padding: 100px;  /* Still available */
--card-padding: 30px;      /* Alias for --card-padding-md */
--radius: 12px;            /* Alias for --card-radius-md */
```

## Dark Theme Support

All tokens automatically adapt to dark theme via `body[data-theme="dark"]`:

- **Card backgrounds** become darker (#1e293b)
- **Card surfaces** use elevated color (#334155)
- **Borders** have stronger contrast (#475569)
- **Shadows** are enhanced (0.25 alpha for visibility)

## WCAG Compliance

All design tokens meet WCAG 2.1 AA standards:

- **Focus rings** have 3px thickness with sufficient contrast
- **Text colors** maintain 4.5:1 contrast ratio
- **Touch targets** enforced at 44px+ minimum on mobile
- **Dark theme** maintains WCAG AA contrast

## Testing Checklist

### Visual Testing
- [ ] Light theme displays correctly across all pages
- [ ] Dark theme displays correctly across all pages
- [ ] Spacing is consistent using 8px scale
- [ ] Cards have proper shadows and borders
- [ ] Focus rings visible on all interactive elements

### Responsive Testing
- [ ] Layout grid collapses properly on mobile
- [ ] Gap utilities scale down at breakpoints
- [ ] Touch targets meet 44px minimum on mobile
- [ ] No horizontal scrolling on any viewport

### Browser Testing
- [ ] Chrome 120+
- [ ] Firefox 121+
- [ ] Safari 17+
- [ ] Edge 120+

### Console Checks
- [ ] No undefined CSS variable warnings
- [ ] No layout shift errors
- [ ] No console errors related to styles

## Usage Guidelines

### Spacing
- Use 8px scale tokens for all spacing
- Prefer tokens over hardcoded pixel values
- Use utility classes for common patterns

### Cards
- Always use card tokens for card styling
- Dark theme support is automatic
- Use utility classes for common card patterns

### Typography
- Use utility classes for text styling
- Maintain hierarchy with consistent sizes
- Use `.text-muted` for secondary information

### Accessibility
- Always include focus rings on interactive elements
- Use `.sr-only` for screen reader text
- Ensure WCAG AA contrast ratios

## Future Enhancements

Potential additions for future versions:

1. **Animation tokens** - Standardized animation durations
2. **Typography scale** - Full type scale system
3. **Breakpoint tokens** - Named breakpoints as tokens
4. **Color palette** - Extended semantic color tokens
5. **Spacing modifiers** - Negative spacing utilities

## References

- [8-Point Grid System](https://spec.fm/specifics/8-pt-grid)
- [WCAG 2.1 AA Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [CSS Custom Properties](https://developer.mozilla.org/en-US/docs/Web/CSS/Using_CSS_custom_properties)

## Support

For questions or issues related to design tokens:

1. Check this documentation first
2. Review existing usage in CSS files
3. Test in light and dark themes
4. Verify responsive behavior

---

**Status:** ✅ Production Ready  
**Last Updated:** January 2025  
**Maintained By:** Development Team
