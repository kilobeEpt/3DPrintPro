# Mobile Polish Testing Checklist

## Test Viewports
- ✓ 360px (smallest common mobile)
- ✓ 414px (iPhone Pro Max)
- ✓ 768px (tablet portrait)
- ✓ 1024px (tablet landscape)

## Navigation Tests

### Mobile Menu (≤968px)
- [ ] Hamburger button is ≥44px tap target
- [ ] Menu opens smoothly with left slide animation
- [ ] Menu covers full viewport height
- [ ] Background scrolling is prevented when menu is open
- [ ] Menu items have ≥48px tap targets
- [ ] Menu items are easily readable (18px font)
- [ ] Clicking a menu item closes the menu and scrolls to section
- [ ] Theme toggle button is ≥44px tap target
- [ ] No horizontal overflow when menu is open

## Hero Section Tests

### 360px - 480px
- [ ] Title is readable and doesn't overflow
- [ ] Description text is legible (16px)
- [ ] CTA buttons stack vertically
- [ ] CTA buttons are full-width with ≥44px height
- [ ] Feature items stack vertically
- [ ] 3D card scales down appropriately (250px max)
- [ ] No horizontal scrolling

### 481px - 768px
- [ ] All text remains readable
- [ ] Buttons maintain proper spacing
- [ ] Layout adjusts smoothly

## Calculator Section Tests

### 360px - 600px
- [ ] Form inputs are full-width
- [ ] Input labels have icons and are readable
- [ ] Form fields have ≥44px touch targets
- [ ] Weight/Quantity inputs stack vertically (form-row)
- [ ] Range slider is easy to drag (20px thumb)
- [ ] Checkboxes are ≥24px with proper spacing
- [ ] Checkbox labels wrap properly
- [ ] Calculate button is full-width with ≥44px height
- [ ] Result card stacks below form
- [ ] Result card padding is appropriate (16px)
- [ ] Price breakdown is readable (14px font on 360px)
- [ ] Total price is prominent and clear
- [ ] CTA buttons stack and span full width

## Services/Portfolio/Testimonials Tests

### Cards
- [ ] Service cards stack single-column at ≤600px
- [ ] Card shadows are subtle on mobile (shadow-sm)
- [ ] Images have constrained heights (220-300px)
- [ ] Text doesn't overflow cards
- [ ] Icons scale appropriately (24-32px)
- [ ] Card padding is comfortable (16-20px)
- [ ] Portfolio filters wrap properly
- [ ] Filter buttons have ≥44px touch targets
- [ ] Testimonial slider controls are ≥44px

## Contact Section Tests

### 360px - 600px
- [ ] Contact info cards stack vertically
- [ ] Contact form is single-column
- [ ] Form inputs are full-width
- [ ] Submit button spans full width with ≥44px height
- [ ] Social links are ≥44px tap targets
- [ ] No side-by-side layouts that cause overflow

## Footer Tests

### Mobile
- [ ] Footer columns stack vertically at ≤600px
- [ ] Links are easily tappable
- [ ] Text is readable
- [ ] No content overflow

## FAQ Section Tests

### Mobile
- [ ] FAQ items have proper touch targets
- [ ] Question buttons are ≥44px height
- [ ] Text is readable (16px questions, 15px answers)
- [ ] Expand/collapse works smoothly
- [ ] Icons rotate properly

## Modal Tests

### 360px - 600px
- [ ] Modal content has proper margins (20px)
- [ ] Close button is ≥36px with proper positioning
- [ ] Modal title is readable (24px)
- [ ] Content scrolls if needed
- [ ] Backdrop prevents interaction with page

## General Mobile Tests

### All Breakpoints
- [ ] No horizontal scrolling at any viewport
- [ ] All tap targets are ≥44px (40px minimum)
- [ ] Font sizes are readable (minimum 14px)
- [ ] Line heights provide comfortable reading (1.6-1.8)
- [ ] Spacing between interactive elements is sufficient
- [ ] Images scale properly without distortion
- [ ] Background animations disabled on mobile
- [ ] Page loads without layout shift
- [ ] Transitions are smooth (not janky)

## Performance Tests

### Mobile Devices
- [ ] No visible performance issues
- [ ] Smooth scrolling
- [ ] Animations don't cause jank
- [ ] Reduced motion preference respected
- [ ] CLS (Cumulative Layout Shift) < 0.1

## Accessibility Tests

### Keyboard Navigation
- [ ] Tab order is logical
- [ ] Focus indicators are visible
- [ ] Menu can be navigated with keyboard
- [ ] No keyboard traps

### Touch Gestures
- [ ] Swipe to scroll works
- [ ] Pinch to zoom works
- [ ] No accidental taps due to small targets

## Cross-Browser Tests

### Mobile Browsers
- [ ] Chrome Mobile
- [ ] Safari iOS
- [ ] Firefox Mobile
- [ ] Samsung Internet

## Key Improvements Made

1. **Navigation**
   - Full-height mobile menu (100vh)
   - Proper z-index layering
   - Body scroll prevention when menu open
   - Increased tap targets (≥44px)
   - Better spacing between menu items

2. **Typography**
   - Fluid font sizing with clamp()
   - Minimum readable sizes (14-16px)
   - Proper line heights for mobile

3. **Calculator**
   - Full-width inputs on mobile
   - Stacked form rows
   - Larger checkbox targets (24px)
   - Better spacing for touch interaction

4. **Cards & Images**
   - Constrained image heights
   - Single-column layouts on small screens
   - Reduced shadows for performance
   - Better padding scaling

5. **General**
   - Prevent horizontal overflow (overflow-x: hidden)
   - Disabled background animations on mobile
   - Better container padding at all breakpoints
   - Responsive gaps in grids
   - Full-width CTAs on mobile

## Viewport-Specific Breakpoints

- **1024px-1200px**: Smaller desktops/large tablets
- **768px-968px**: Tablets and small laptops
- **600px-768px**: Large phones and small tablets
- **480px-600px**: Standard phones
- **360px-480px**: Small phones
- **<360px**: Very small phones (minimum support)
