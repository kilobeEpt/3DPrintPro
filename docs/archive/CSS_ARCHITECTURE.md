# CSS Architecture Documentation

## Overview
The site uses a clean, simple CSS architecture focused on maintainability and responsive design.

## CSS Load Order
1. **style.css** - Base styles for all components
2. **mobile-polish.css** - Mobile-specific overrides and optimizations
3. **animations.css** - Animation definitions

## Core Principles

### 1. Container System
The foundation of the layout is the `.container` class:

```css
.container {
    max-width: var(--container-width); /* 1200px */
    margin: 0 auto;
    padding: 0 20px;
}
```

**Usage Pattern:**
```html
<section class="services">
    <div class="container">
        <!-- Content here -->
    </div>
</section>
```

- Sections (`<section>`) are full-width for backgrounds
- `.container` inside sections constrains content width
- Consistent max-width across all pages

### 2. Responsive Breakpoints
Defined in both style.css and mobile-polish.css:

- **1920px+**: Ultra-wide screens (max-width: 1600px for grids)
- **1200px-1920px**: Large desktop (standard 1200px container)
- **968px-1200px**: Medium desktop/laptop
- **768px-968px**: Tablet
- **600px-768px**: Large mobile
- **360px-600px**: Standard mobile
- **<360px**: Small mobile

### 3. Grid Layouts
All grids use responsive auto-fit pattern:

```css
grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
```

Key grids:
- `.stats-grid` - Statistics cards (250px min)
- `.services-grid` - Service cards (320px min)
- `.portfolio-grid` - Portfolio items (320px min)
- `.why-us-grid` - USP cards
- `.blog-grid` - Blog articles
- `.districts-grid` - District cards

### 4. Spacing System
CSS variables for consistent spacing:

```css
--section-padding: 100px;  /* Desktop */
--card-padding: 30px;
```

Mobile-polish.css progressively reduces these:
- 1024px: 70px
- 968px: 60px
- 768px: 50px
- 600px: 40px

### 5. Typography Scale
Using `clamp()` for fluid typography:

```css
.hero-title {
    font-size: clamp(40px, 5vw, 64px);
}

.section-title {
    font-size: clamp(32px, 4vw, 48px);
}
```

### 6. Overflow Prevention
Built-in at multiple levels:

```css
html, body {
    overflow-x: hidden;
}

section, header, footer {
    overflow-x: hidden;
}
```

### 7. Mobile Optimizations (mobile-polish.css)

#### Touch Targets
All interactive elements: **minimum 44x44px**

#### Navigation
- Desktop: Horizontal menu
- Mobile (<968px): Full-screen overlay menu
- Body scroll lock when mobile menu open

#### Performance
- Particles hidden on mobile (<768px)
- Reduced animation durations
- Hover effects disabled on touch devices

#### Forms
- Input font-size: 16px (prevents iOS zoom)
- Min-height: 48px for all inputs

### 8. Ultra-Wide Screen Strategy
On screens 1920px+:

```css
.hero-content,
.services-grid,
.portfolio-grid {
    max-width: 1600px;
}

.calculator-wrapper,
.contact-wrapper {
    max-width: 1400px;
}

.content-wrapper,
.testimonials-slider {
    max-width: 1000px;
}
```

Prevents content from stretching too wide while maintaining visual balance.

## Component Patterns

### Page Hero
```css
.page-hero {
    padding: 120px 0 60px;
    background: linear-gradient(...);
}

.page-hero .container {
    /* Content automatically constrained */
}
```

### Cards
```css
.service-card,
.portfolio-item,
.why-us-card {
    /* Cards are inside grid containers */
    /* Grid handles responsive layout */
}
```

### Forms
```css
.calculator-wrapper,
.contact-form {
    /* Forms respect container width */
    max-width: 100%;
}
```

## Adding New Sections

### Step 1: HTML Structure
```html
<section class="my-new-section">
    <div class="container">
        <div class="section-header">
            <span class="section-label">Label</span>
            <h2 class="section-title">Title</h2>
            <p class="section-description">Description</p>
        </div>
        
        <div class="my-grid">
            <!-- Grid items -->
        </div>
    </div>
</section>
```

### Step 2: CSS for Section
```css
.my-new-section {
    padding: var(--section-padding) 0;
    background: var(--bg);
}

.my-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
}
```

### Step 3: Mobile Override (if needed)
In mobile-polish.css:
```css
@media (max-width: 600px) {
    .my-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}
```

## Best Practices

### DO:
✅ Use `.container` inside all sections  
✅ Use CSS variables for colors, spacing  
✅ Use `repeat(auto-fit, minmax(...))` for grids  
✅ Use `clamp()` for fluid typography  
✅ Test at 360px, 768px, 1024px, 1440px  
✅ Ensure 44px minimum tap targets on mobile  

### DON'T:
❌ Add `width: 100%` unnecessarily  
❌ Use `!important` (except mobile-polish.css sparingly)  
❌ Hard-code widths in pixels  
❌ Create page-specific CSS files  
❌ Override container max-width without reason  
❌ Use `flex: 1` causing unwanted stretch  

## Testing Checklist

- [ ] Test at 360px width (no horizontal scroll)
- [ ] Test at 768px (tablet layout)
- [ ] Test at 1024px (desktop layout)
- [ ] Test at 1440px (large desktop)
- [ ] Test at 1920px+ (ultra-wide)
- [ ] Verify all interactive elements are 44px+ on mobile
- [ ] Check that text is readable (min 16px font-size)
- [ ] Ensure smooth transitions between breakpoints
- [ ] Validate no overflow-x at any breakpoint
- [ ] Check mobile menu opens/closes properly
- [ ] Verify calculator, forms work on mobile
- [ ] Test with browser dev tools device emulation

## File Structure

```
css/
├── style.css           # Base styles, all components
├── mobile-polish.css   # Mobile overrides, responsive
├── animations.css      # Animation definitions
└── admin.css           # Admin dashboard (separate)
```

## Maintenance Notes

- Keep CSS simple and readable
- Comment complex sections
- Follow existing patterns
- Test across breakpoints
- Update this document when adding major features

## Performance

- Load order: style → mobile-polish → animations
- Font Awesome from CDN (cached)
- CSS variables for easy theming
- Minimal specificity for fast rendering
- Reduced animations on mobile for performance

## Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- CSS Grid and Flexbox required
- CSS custom properties (variables) required
- CSS clamp() for fluid typography

## Summary

The CSS architecture is intentionally simple:
1. **style.css**: Provides solid foundation
2. **mobile-polish.css**: Adds responsive behavior
3. **.container**: Constrains content consistently
4. **CSS Grid**: Handles responsive layouts automatically
5. **CSS Variables**: Maintains consistency

No complicated build steps, no preprocessors, no page-specific CSS. Just clean, maintainable CSS that works.
