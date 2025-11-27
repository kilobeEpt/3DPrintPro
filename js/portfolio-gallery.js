// ========================================
// PORTFOLIO GALLERY MODULE
// Handles filtering, modal, keyboard nav, and swipe gestures
// ========================================

class PortfolioGallery {
    constructor() {
        this.modal = document.getElementById('portfolioModal');
        this.filterBtns = document.querySelectorAll('.filter-btn');
        this.portfolioCards = document.querySelectorAll('.portfolio-card');
        this.currentIndex = 0;
        this.visibleCards = [];
        this.touchStartX = 0;
        this.touchEndX = 0;
        this.focusableElements = [];
        this.lastFocusedElement = null;
    }

    init() {
        if (!this.modal || this.portfolioCards.length === 0) {
            return; // Not on portfolio page
        }

        this.initFilters();
        this.initCardClicks();
        this.initModalControls();
        this.initKeyboardNav();
        this.initTouchGestures();
        this.updateVisibleCards();
    }

    // ========================================
    // FILTERING
    // ========================================

    initFilters() {
        this.filterBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const filter = btn.getAttribute('data-filter');
                this.applyFilter(filter, btn);
            });

            // Keyboard support
            btn.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    btn.click();
                }
            });
        });
    }

    applyFilter(filter, activeBtn) {
        // Update active button state
        this.filterBtns.forEach(btn => {
            btn.classList.remove('active');
            btn.setAttribute('aria-pressed', 'false');
        });
        activeBtn.classList.add('active');
        activeBtn.setAttribute('aria-pressed', 'true');

        // Filter cards with animation
        this.portfolioCards.forEach((card, index) => {
            const category = card.getAttribute('data-category');
            const shouldShow = filter === 'all' || category === filter;

            if (shouldShow) {
                // Show card with stagger animation
                setTimeout(() => {
                    card.classList.remove('portfolio-card--hidden');
                    card.classList.add('portfolio-card--visible');
                }, index * 50); // Stagger effect
            } else {
                // Hide card
                card.classList.remove('portfolio-card--visible');
                card.classList.add('portfolio-card--hidden');
            }
        });

        // Update visible cards list
        this.updateVisibleCards();
    }

    updateVisibleCards() {
        this.visibleCards = Array.from(this.portfolioCards).filter(card => 
            !card.classList.contains('portfolio-card--hidden')
        );
    }

    // ========================================
    // CARD INTERACTIONS
    // ========================================

    initCardClicks() {
        this.portfolioCards.forEach(card => {
            // Click on card or view button
            const viewBtn = card.querySelector('.portfolio-view-btn');
            
            const openModal = (e) => {
                e.preventDefault();
                const index = parseInt(card.getAttribute('data-index'));
                this.openModal(index);
            };

            card.addEventListener('click', openModal);
            if (viewBtn) {
                viewBtn.addEventListener('click', (e) => {
                    e.stopPropagation(); // Prevent double trigger
                    openModal(e);
                });
            }

            // Keyboard support
            card.setAttribute('tabindex', '0');
            card.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    openModal(e);
                }
            });
        });
    }

    // ========================================
    // MODAL CONTROLS
    // ========================================

    initModalControls() {
        const closeBtn = this.modal.querySelector('.portfolio-modal-close');
        const backdrop = this.modal.querySelector('.modal-backdrop');
        const prevBtn = this.modal.querySelector('.portfolio-nav-prev');
        const nextBtn = this.modal.querySelector('.portfolio-nav-next');

        // Close modal
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closeModal());
        }
        if (backdrop) {
            backdrop.addEventListener('click', () => this.closeModal());
        }

        // Navigation
        if (prevBtn) {
            prevBtn.addEventListener('click', () => this.navigateModal(-1));
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', () => this.navigateModal(1));
        }
    }

    openModal(index) {
        this.currentIndex = index;
        this.lastFocusedElement = document.activeElement;
        
        this.populateModal(index);
        this.modal.classList.add('modal--active');
        document.body.style.overflow = 'hidden';
        
        // Focus trap
        this.setupFocusTrap();
        
        // Focus close button
        const closeBtn = this.modal.querySelector('.portfolio-modal-close');
        if (closeBtn) {
            setTimeout(() => closeBtn.focus(), 100);
        }
    }

    closeModal() {
        this.modal.classList.remove('modal--active');
        document.body.style.overflow = '';
        
        // Restore focus
        if (this.lastFocusedElement) {
            this.lastFocusedElement.focus();
        }
    }

    populateModal(index) {
        const card = Array.from(this.portfolioCards).find(c => 
            parseInt(c.getAttribute('data-index')) === index
        );
        
        if (!card) return;

        const title = card.getAttribute('data-title');
        const description = card.getAttribute('data-description');
        const technology = card.getAttribute('data-technology');
        const completion = card.getAttribute('data-completion');
        const image = card.getAttribute('data-image');

        // Update modal content
        const modalImage = document.getElementById('portfolioModalImage');
        const modalTitle = document.getElementById('portfolioModalTitle');
        const modalDescription = document.getElementById('portfolioModalDescription');
        const modalTech = document.getElementById('portfolioModalTech');
        const modalTime = document.getElementById('portfolioModalTime');
        const modalCounter = document.getElementById('portfolioModalCounter');

        if (modalImage) {
            modalImage.src = image;
            modalImage.alt = title;
        }
        if (modalTitle) modalTitle.textContent = title;
        if (modalDescription) modalDescription.textContent = description;
        if (modalTech) modalTech.textContent = technology;
        if (modalTime) modalTime.textContent = completion;
        if (modalCounter) {
            modalCounter.textContent = `${index + 1} / ${this.portfolioCards.length}`;
        }

        // Update navigation buttons state
        this.updateNavButtons();
    }

    navigateModal(direction) {
        this.currentIndex += direction;
        
        // Wrap around
        if (this.currentIndex < 0) {
            this.currentIndex = this.portfolioCards.length - 1;
        } else if (this.currentIndex >= this.portfolioCards.length) {
            this.currentIndex = 0;
        }

        this.populateModal(this.currentIndex);
    }

    updateNavButtons() {
        const prevBtn = this.modal.querySelector('.portfolio-nav-prev');
        const nextBtn = this.modal.querySelector('.portfolio-nav-next');

        // Show/hide based on position (or keep visible for wrap-around)
        // For now, always show since we wrap around
        if (prevBtn) prevBtn.style.display = 'flex';
        if (nextBtn) nextBtn.style.display = 'flex';
    }

    // ========================================
    // KEYBOARD NAVIGATION
    // ========================================

    initKeyboardNav() {
        document.addEventListener('keydown', (e) => {
            if (!this.modal.classList.contains('modal--active')) {
                return;
            }

            switch(e.key) {
                case 'Escape':
                    e.preventDefault();
                    this.closeModal();
                    break;
                case 'ArrowLeft':
                    e.preventDefault();
                    this.navigateModal(-1);
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    this.navigateModal(1);
                    break;
            }
        });
    }

    // ========================================
    // TOUCH GESTURES
    // ========================================

    initTouchGestures() {
        const imageWrapper = this.modal.querySelector('.portfolio-modal-image-wrapper');
        if (!imageWrapper) return;

        imageWrapper.addEventListener('touchstart', (e) => {
            this.touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        imageWrapper.addEventListener('touchend', (e) => {
            this.touchEndX = e.changedTouches[0].screenX;
            this.handleSwipe();
        }, { passive: true });

        // Pointer events for desktop drag (optional enhancement)
        let isDragging = false;
        let startX = 0;

        imageWrapper.addEventListener('pointerdown', (e) => {
            isDragging = true;
            startX = e.clientX;
            imageWrapper.style.cursor = 'grabbing';
        });

        imageWrapper.addEventListener('pointerup', (e) => {
            if (!isDragging) return;
            isDragging = false;
            imageWrapper.style.cursor = 'grab';
            
            const endX = e.clientX;
            const diff = startX - endX;
            
            if (Math.abs(diff) > 50) { // Minimum swipe distance
                if (diff > 0) {
                    this.navigateModal(1); // Swipe left = next
                } else {
                    this.navigateModal(-1); // Swipe right = previous
                }
            }
        });

        imageWrapper.addEventListener('pointermove', (e) => {
            if (!isDragging) return;
            e.preventDefault();
        });
    }

    handleSwipe() {
        const swipeDistance = this.touchStartX - this.touchEndX;
        const minSwipeDistance = 50;

        if (Math.abs(swipeDistance) < minSwipeDistance) {
            return; // Not a swipe
        }

        if (swipeDistance > 0) {
            // Swipe left = next
            this.navigateModal(1);
        } else {
            // Swipe right = previous
            this.navigateModal(-1);
        }
    }

    // ========================================
    // FOCUS TRAP
    // ========================================

    setupFocusTrap() {
        const modalContent = this.modal.querySelector('.portfolio-modal-content');
        if (!modalContent) return;

        this.focusableElements = Array.from(
            modalContent.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            )
        );

        if (this.focusableElements.length === 0) return;

        const firstElement = this.focusableElements[0];
        const lastElement = this.focusableElements[this.focusableElements.length - 1];

        modalContent.addEventListener('keydown', (e) => {
            if (e.key !== 'Tab') return;

            if (e.shiftKey) {
                // Shift + Tab
                if (document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                }
            } else {
                // Tab
                if (document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        });
    }
}

// ========================================
// INITIALIZE
// ========================================

document.addEventListener('DOMContentLoaded', () => {
    const portfolioGallery = new PortfolioGallery();
    portfolioGallery.init();
});
