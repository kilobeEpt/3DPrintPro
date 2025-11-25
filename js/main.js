// ========================================
// LIGHTWEIGHT MAIN.JS - UI INTERACTIONS ONLY
// ========================================

class SiteUI {
    constructor() {
        this.mobileBreakpoint = 768;
        this.currentTestimonial = 0;
        this.currentFilter = 'all';
    }

    init() {
        this.initPreloader();
        this.initNavigation();
        this.initThemeToggle();
        this.initPhoneMasks();
        this.initScrollAnimations();
        this.initStats();
        this.initTestimonials();
        this.initForms();
        this.initPortfolioFilter();
        
        console.log('✅ SiteUI initialized');
    }

    // ========================================
    // PRELOADER
    // ========================================

    initPreloader() {
        window.addEventListener('load', () => {
            const preloader = document.getElementById('preloader');
            if (preloader) {
                setTimeout(() => {
                    preloader.style.display = 'none';
                }, 300);
            }
        });
    }

    // ========================================
    // NAVIGATION
    // ========================================

    initNavigation() {
        const hamburger = document.getElementById('hamburger');
        const navMenu = document.getElementById('navMenu');
        const header = document.getElementById('header');

        // Mobile menu toggle
        if (hamburger && navMenu) {
            hamburger.addEventListener('click', () => {
                hamburger.classList.toggle('active');
                navMenu.classList.toggle('active');
                document.body.style.overflow = navMenu.classList.contains('active') ? 'hidden' : '';
            });

            // Close menu when clicking on a link
            const navLinks = navMenu.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth <= this.mobileBreakpoint) {
                        hamburger.classList.remove('active');
                        navMenu.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });
            });

            // Close menu when clicking outside
            document.addEventListener('click', (e) => {
                if (window.innerWidth <= this.mobileBreakpoint &&
                    navMenu.classList.contains('active') &&
                    !navMenu.contains(e.target) &&
                    !hamburger.contains(e.target)) {
                    hamburger.classList.remove('active');
                    navMenu.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        }

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href === '#' || href === '') return;
                
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    const headerHeight = header ? header.offsetHeight : 80;
                    const targetPosition = target.offsetTop - headerHeight;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Header scroll behavior
        if (header) {
            let lastScroll = 0;
            window.addEventListener('scroll', () => {
                const currentScroll = window.pageYOffset;
                
                if (currentScroll > 100) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
                
                lastScroll = currentScroll;
            });
        }
    }

    // ========================================
    // THEME TOGGLE
    // ========================================

    initThemeToggle() {
        const themeToggle = document.getElementById('themeToggle');
        if (!themeToggle) return;

        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        this.updateThemeIcon(themeToggle, savedTheme);

        // Toggle theme
        themeToggle.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            this.updateThemeIcon(themeToggle, newTheme);
        });
    }

    updateThemeIcon(button, theme) {
        const icon = button.querySelector('i');
        if (icon) {
            icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    }

    // ========================================
    // PHONE MASKS
    // ========================================

    initPhoneMasks() {
        const phoneInputs = document.querySelectorAll('input[type="tel"]');
        phoneInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                
                if (value.length > 0) {
                    if (value[0] === '8') value = '7' + value.slice(1);
                    if (value[0] !== '7') value = '7' + value;
                }
                
                let formatted = '';
                if (value.length > 0) {
                    formatted = '+7';
                    if (value.length > 1) formatted += ' (' + value.slice(1, 4);
                    if (value.length > 4) formatted += ') ' + value.slice(4, 7);
                    if (value.length > 7) formatted += '-' + value.slice(7, 9);
                    if (value.length > 9) formatted += '-' + value.slice(9, 11);
                }
                
                e.target.value = formatted;
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && e.target.value === '+7 (') {
                    e.target.value = '';
                }
            });
        });
    }

    // ========================================
    // SCROLL ANIMATIONS
    // ========================================

    initScrollAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all cards and sections
        const elements = document.querySelectorAll('.service-card, .portfolio-card, .why-us-card, .faq-item, .contact-card');
        elements.forEach((el, index) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
            observer.observe(el);
        });
    }

    // ========================================
    // STATS COUNTER
    // ========================================

    initStats() {
        const stats = document.querySelectorAll('.stat-number');
        if (stats.length === 0) return;

        const animateStats = () => {
            stats.forEach(stat => {
                const target = parseInt(stat.getAttribute('data-target'));
                const duration = 2000;
                const increment = target / (duration / 16);
                let current = 0;

                const updateCounter = () => {
                    current += increment;
                    if (current < target) {
                        stat.textContent = Math.floor(current);
                        requestAnimationFrame(updateCounter);
                    } else {
                        stat.textContent = target;
                    }
                };

                updateCounter();
            });
        };

        // Trigger animation when stats section is visible
        const statsSection = document.querySelector('.stats');
        if (statsSection) {
            const observer = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting) {
                    animateStats();
                    observer.disconnect();
                }
            }, { threshold: 0.5 });
            observer.observe(statsSection);
        }
    }

    // ========================================
    // TESTIMONIALS SLIDER
    // ========================================

    initTestimonials() {
        const slider = document.getElementById('testimonialsSlider');
        if (!slider) return;

        const cards = slider.querySelectorAll('.testimonial-card');
        if (cards.length === 0) return;

        const prevBtn = document.getElementById('prevTestimonial');
        const nextBtn = document.getElementById('nextTestimonial');

        const showTestimonial = (index) => {
            cards.forEach((card, i) => {
                card.classList.toggle('active', i === index);
            });
            this.currentTestimonial = index;
        };

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                const newIndex = (this.currentTestimonial - 1 + cards.length) % cards.length;
                showTestimonial(newIndex);
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                const newIndex = (this.currentTestimonial + 1) % cards.length;
                showTestimonial(newIndex);
            });
        }

        // Auto-advance every 5 seconds
        setInterval(() => {
            const newIndex = (this.currentTestimonial + 1) % cards.length;
            showTestimonial(newIndex);
        }, 5000);
    }

    // ========================================
    // FORMS
    // ========================================

    initForms() {
        // Contact form
        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
            contactForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.handleFormSubmit(contactForm, 'contact');
            });
        }

        // Subscribe form
        const subscribeForm = document.getElementById('subscribeForm');
        if (subscribeForm) {
            subscribeForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.handleFormSubmit(subscribeForm, 'subscribe');
            });
        }
    }

    async handleFormSubmit(form, type) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправка...';

        try {
            // Check if Telegram integration is available
            if (typeof sendToTelegram === 'function') {
                const message = this.formatTelegramMessage(data, type);
                const result = await sendToTelegram(message);
                
                if (result.success) {
                    this.showNotification('Сообщение успешно отправлено!', 'success');
                    form.reset();
                } else {
                    throw new Error(result.error || 'Ошибка отправки');
                }
            } else {
                // Fallback: show success message (for static demo)
                this.showNotification('Форма заполнена корректно. Для реальной отправки настройте Telegram бота.', 'info');
                form.reset();
            }
        } catch (error) {
            console.error('Form submission error:', error);
            this.showNotification('Ошибка при отправке. Попробуйте позже или свяжитесь с нами по телефону.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    formatTelegramMessage(data, type) {
        if (type === 'contact') {
            return `🔔 Новое сообщение с сайта\n\n` +
                   `👤 Имя: ${data.name || 'Не указано'}\n` +
                   `📞 Телефон: ${data.phone || 'Не указан'}\n` +
                   `📧 Email: ${data.email || 'Не указан'}\n` +
                   `📝 Тема: ${data.subject || 'Общий вопрос'}\n` +
                   `💬 Сообщение:\n${data.message || 'Нет текста'}`;
        } else if (type === 'subscribe') {
            return `📬 Новая подписка на рассылку\n\n📧 Email: ${data.email}`;
        }
        return '';
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;

        // Add to page
        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => notification.classList.add('show'), 10);

        // Remove after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }

    // ========================================
    // PORTFOLIO FILTER
    // ========================================

    initPortfolioFilter() {
        const filterBtns = document.querySelectorAll('.filter-btn');
        const portfolioCards = document.querySelectorAll('[data-category]');

        if (filterBtns.length === 0 || portfolioCards.length === 0) return;

        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const filter = btn.getAttribute('data-filter');
                
                // Update active button
                filterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                // Filter cards
                portfolioCards.forEach(card => {
                    const category = card.getAttribute('data-category');
                    if (filter === 'all' || category === filter) {
                        card.style.display = '';
                        card.style.opacity = '0';
                        setTimeout(() => card.style.opacity = '1', 10);
                    } else {
                        card.style.opacity = '0';
                        setTimeout(() => card.style.display = 'none', 300);
                    }
                });

                this.currentFilter = filter;
            });
        });
    }
}

// ========================================
// GLOBAL HELPER FUNCTIONS
// ========================================

// Calculator price calculation
function calculatePrice() {
    if (typeof window.calculator !== 'undefined' && window.calculator.calculate) {
        const result = window.calculator.calculate();
        if (result) {
            // Display result
            document.getElementById('materialCost').textContent = Math.round(result.materialCost) + '₽';
            document.getElementById('laborCost').textContent = Math.round(result.laborCost) + '₽';
            document.getElementById('additionalCost').textContent = Math.round(result.additionalCost) + '₽';
            document.getElementById('totalPrice').textContent = Math.round(result.total) + '₽';
            document.getElementById('estimateTime').textContent = result.estimateTime;
            
            if (result.discount > 0) {
                document.getElementById('discountAmount').textContent = '-' + Math.round(result.discount) + '₽';
                document.getElementById('discountItem').style.display = 'flex';
            } else {
                document.getElementById('discountItem').style.display = 'none';
            }
        }
    } else {
        console.warn('Calculator not initialized');
    }
}

// Scroll to contact form
function scrollToContactForm() {
    const contactSection = document.getElementById('contact');
    if (contactSection) {
        const header = document.getElementById('header');
        const headerHeight = header ? header.offsetHeight : 80;
        const targetPosition = contactSection.offsetTop - headerHeight;
        
        window.scrollTo({
            top: targetPosition,
            behavior: 'smooth'
        });
    }
}

// Toggle FAQ item
function toggleFAQ(button) {
    const faqItem = button.parentElement;
    const answer = faqItem.querySelector('.faq-answer');
    const icon = button.querySelector('i');
    
    faqItem.classList.toggle('active');
    
    if (faqItem.classList.contains('active')) {
        answer.style.maxHeight = answer.scrollHeight + 'px';
        icon.style.transform = 'rotate(180deg)';
    } else {
        answer.style.maxHeight = '0';
        icon.style.transform = 'rotate(0deg)';
    }
}

// Show portfolio details
function showPortfolioDetails(id) {
    if (typeof window.PORTFOLIO_DATA === 'undefined') {
        console.warn('Portfolio data not loaded');
        return;
    }

    const item = window.PORTFOLIO_DATA.find(p => p.id === id);
    if (!item) return;

    const modal = document.getElementById('portfolioModal');
    const content = document.getElementById('portfolioModalContent');
    
    if (!modal || !content) return;

    content.innerHTML = `
        <div class="portfolio-modal-content">
            <img src="${item.image}" alt="${item.title}" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22800%22 height=%22600%22%3E%3Crect fill=%22%23e0e0e0%22 width=%22800%22 height=%22600%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-size=%2224%22 text-anchor=%22middle%22 fill=%22%23999%22%3E${item.title}%3C/text%3E%3C/svg%3E'">
            <h2>${item.title}</h2>
            <div class="portfolio-modal-meta">
                <span><i class="fas fa-print"></i> ${item.technology}</span>
                <span><i class="fas fa-clock"></i> ${item.duration}</span>
                <span><i class="fas fa-calendar"></i> ${item.year}</span>
            </div>
            <p>${item.description}</p>
            <div class="portfolio-modal-details">
                <div>
                    <h4><i class="fas fa-layer-group"></i> Материалы</h4>
                    <p>${item.materials.join(', ')}</p>
                </div>
                <div>
                    <h4><i class="fas fa-user"></i> Клиент</h4>
                    <p>${item.client}</p>
                </div>
            </div>
            <div class="portfolio-modal-tags">
                ${item.tags.map(tag => `<span class="tag">${tag}</span>`).join('')}
            </div>
        </div>
    `;
    
    openModal('portfolioModal');
}

// Modal functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close modal when clicking outside
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal')) {
        closeModal(e.target.id);
    }
});

// Close modal on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.active').forEach(modal => {
            closeModal(modal.id);
        });
    }
});

// ========================================
// INITIALIZATION
// ========================================

const app = new SiteUI();

document.addEventListener('DOMContentLoaded', () => {
    app.init();
    
    // Initialize calculator if on page with calculator
    if (typeof Calculator !== 'undefined' && document.getElementById('printTechnology')) {
        window.calculator = new Calculator();
        window.calculator.init();
    }
});
